<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Import;

use Psr\Http\Client\ClientExceptionInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Throwable;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfigurationInterface;
use WerkraumMedia\ThueCat\Import\Importer\FetchData;
use WerkraumMedia\ThueCat\Import\Importer\FetchData\InvalidResponseException;
use WerkraumMedia\ThueCat\Import\Parser\DataHandlerPayload;
use WerkraumMedia\ThueCat\Import\Parser\Parser;
use WerkraumMedia\ThueCat\Import\Parser\ParserContext;
use WerkraumMedia\ThueCat\Import\Progress\ImportPhase;
use WerkraumMedia\ThueCat\Import\Progress\ImportProgress;
use WerkraumMedia\ThueCat\Import\Progress\ImportProgressListener;
use WerkraumMedia\ThueCat\Import\Progress\NullProgressListener;
use WerkraumMedia\ThueCat\Import\Settings\CategoryAnchorResolver;
use WerkraumMedia\ThueCat\Import\Settings\CategoryAnchors;
use WerkraumMedia\ThueCat\Import\Settings\CategoryAnchorSetting;
use WerkraumMedia\ThueCat\Import\Settings\ImportSetting;
use WerkraumMedia\ThueCat\Import\Settings\ImportSettings;
use WerkraumMedia\ThueCat\Import\Settings\ImportTarget;
use WerkraumMedia\ThueCat\Import\UrlProvider\InvalidUrlProviderException;
use WerkraumMedia\ThueCat\Import\UrlProvider\UrlProvider;
use WerkraumMedia\ThueCat\Import\Watchdog\RunBudgetExhaustedException;
use WerkraumMedia\ThueCat\Import\Watchdog\RunDeadline;

class Importer
{
    // Last phase entered, so a run-ending throwable can say where it died.
    protected ImportPhase $phaseReached = ImportPhase::Fetch;

    public function __construct(
        protected readonly Parser $parser,
        protected readonly FetchData $fetchData,
        protected readonly SiteFinder $siteFinder,
        protected readonly Resolver $resolver,
        protected readonly FileFolderAccess $fileFolderAccess,
        protected readonly MediaFileStaging $mediaFileStaging,
        protected readonly ImportLogger $importLogger,
        protected readonly ImportConfigurationValidator $configurationValidator,
        protected readonly ImportSettings $settings,
        protected readonly CategoryAnchorResolver $anchorResolver,
        #[AutowireLocator(services: 'import.url.provider')]
        protected readonly ServiceLocator $urlProviders
    ) {
    }

    /**
     * Runs one full import. Returns the highest severity recorded in the
     * import log for this run, in PSR-3 vocabulary (`info` for a clean run,
     * `error` if any DataHandler call complained or the URL loop swallowed
     * an exception). Callers (Command) decide on an exit code from that.
     */
    public function importConfiguration(
        ImportConfigurationInterface $configuration,
        ?ImportProgressListener $listener = null,
        ?RunDeadline $deadline = null,
        bool $bypassCache = false,
        bool $skipMedia = false
    ): string {
        $listener ??= new NullProgressListener();
        $deadline ??= new RunDeadline(
            $this->settings->resolve(ImportSetting::RunBudget, $configuration->getRunBudget())
        );

        // Pre-flight: abort on a misconfiguration before touching the API.
        $this->configurationValidator->validate($configuration);

        // Resolve the target folder (this runs the write-access probe) and
        // create a fresh per-run staging folder under it before touching the
        // API. Media is downloaded into staging and only promoted into the
        // target on a clean run; the staging folder is always discarded in
        // the finally, so a failed run leaves no orphaned files behind.
        //
        // Skipped entirely without media: nothing gets downloaded, so neither
        // folder is needed and a run stays possible on an unwritable one.
        $targetFolder = null;
        $stagingFolder = null;
        if ($skipMedia === false) {
            $targetFolder = $this->fileFolderAccess->resolveFolder($configuration->getFileFolder());
            $stagingFolder = $this->mediaFileStaging->createForRun($targetFolder);
        }

        $this->fetchData->configureForRun(
            $bypassCache,
            $this->settings->resolve(ImportSetting::FetchCacheLifetime, $configuration->getFetchCacheLifetime())
        );

        try {
            return $this->runImport($configuration, $targetFolder, $stagingFolder, $listener, $deadline, $skipMedia);
        } catch (Throwable $failure) {
            // Rethrown after logging: the caller still sees the failure, but the
            // run no longer disappears without a trace.
            $this->importLogger->recordRunFailed($failure, $this->phaseReached);
            $this->importLogger->writeLog($configuration->getUid(), [], []);

            throw $failure;
        } finally {
            $this->fetchData->resetRunConfiguration();
            if ($stagingFolder !== null) {
                $this->mediaFileStaging->discard($stagingFolder);
            }
        }
    }

    protected function runImport(
        ImportConfigurationInterface $configuration,
        ?Folder $targetFolder,
        ?Folder $stagingFolder,
        ?ImportProgressListener $listener = null,
        ?RunDeadline $deadline = null,
        bool $skipMedia = false
    ): string {
        $listener ??= new NullProgressListener();
        $deadline ??= new RunDeadline(0);
        $urlProvider = $this->getProviderForConfiguration($configuration);
        if (!$urlProvider instanceof UrlProvider) {
            throw new InvalidUrlProviderException('No URL Provider available for given configuration.', 1629296635);
        }

        $apiKey = $configuration->getApiKey();
        $apiDomain = $configuration->getApiDomain();
        $translationLanguages = [];
        $defaultLanguage = 'de'; // fallback
        $site = $this->siteFinder->getSiteByPageId($configuration->getStoragePid());
        foreach ($site->getLanguages() as $siteLanguage) {
            if ($siteLanguage->getLanguageId() === 0) {
                $defaultLanguage = $siteLanguage->getLocale()->getLanguageCode();
            } else {
                $translationLanguages[$siteLanguage->getLocale()->getLanguageCode()] = $siteLanguage->getLanguageId();
            }
        }
        // resolveFor() carries the import target, so the anchors are the ones
        // of this run's own category tree.
        $anchors = $this->anchorResolver->resolveFor($configuration);
        $this->reportEffectiveSettings($configuration, $anchors, $listener);
        $parserContext = new ParserContext((int)$configuration->getUid(), $apiDomain);
        $resolverContext = new ResolverContext(
            $configuration->getStoragePid(),
            $parserContext,
            $defaultLanguage,
            $configuration->getApiKey(),
            $translationLanguages,
            $targetFolder,
            $stagingFolder,
            $anchors->categoryParent,
            $anchors->categoryStoragePid,
            $anchors->keywordParent,
            $anchors->keywordStoragePid,
            $listener,
            $skipMedia,
        );
        $accumulatedPayload = new DataHandlerPayload();
        $urls = $urlProvider->getUrls($apiDomain);
        $urlCount = count($urls);
        $urlNumber = 0;
        $aborted = false;
        foreach ($urls as $url) {
            // Between roots: whatever is accumulated so far still gets written.
            if ($deadline->isExpired()) {
                $this->recordAbort($deadline, ImportPhase::Fetch);
                $aborted = true;
                break;
            }
            $urlNumber++;
            $this->phaseReached = ImportPhase::Fetch;
            $listener->progressed(new ImportProgress(
                ImportPhase::Fetch,
                $url,
                $urlNumber,
                $urlCount
            ));
            // Per-URL try/catch so a single broken root doesn't abort the
            // run. The exception is staged into the import log and the
            // loop moves on; the run finishes with severity 'error' so the
            // command surfaces a non-zero exit code.
            try {
                $inputData = $this->fetchDataFromApi($url, $apiKey);
            } catch (InvalidResponseException | ClientExceptionInterface $e) {
                $this->importLogger->recordException('fetchingError', $e, $url);
                continue;
            }
            try {
                $this->parser->parse($inputData, $parserContext, $defaultLanguage, $translationLanguages);
                $resolved = $this->resolver->resolve($this->parser->getDataHandlerPayload(), $resolverContext);
            } catch (Throwable $e) {
                $this->importLogger->recordException('mappingError', $e, $url);
                continue;
            }
            $accumulatedPayload->mergeFrom($resolved);
        }

        // Not during resolution: only now is the produced-set complete.
        $this->resolver->flushCollectedMedia($accumulatedPayload, $resolverContext);
        $this->resolver->flushCollectedKeywords($accumulatedPayload, $resolverContext);

        // Snapshot before the loop drains the datamap. Translation rows added
        // by the resolver are excluded so the logger reports only the
        // default-language records the user expects to see counted.
        $loggerPayload = $accumulatedPayload->getDefaultLanguageDataMap();

        // Snapshot before the loop drains it; recorded after the loop so matched
        // entries can carry the uids promoted once persisting has run.
        $matchReports = $accumulatedPayload->getMatchReports();

        if ($accumulatedPayload->getDataMap() === [] && $accumulatedPayload->getCmdMap() === []) {
            $listener->progressed(new ImportProgress(ImportPhase::Log, 'Writing import log'));
            // Nothing persisted; still report the types seen.
            $this->importLogger->recordMatchReports($matchReports);
            $this->importLogger->writeLog(
                $configuration->getUid(),
                $loggerPayload,
                []
            );
            $listener->progressed(new ImportProgress(ImportPhase::Finish, 'Finishing'));

            return $this->finishRun($targetFolder, $stagingFolder);
        }

        $iterations = 0;
        // DataHandler's cmdMap is keyed [table][uid][command] = value, so
        // two localize commands on the same uid (one per target language)
        // collapse to a single entry — only the last survives. Each
        // additional language therefore needs its own iteration: round N
        // stages localize for one outstanding language, round N+1 fills
        // the just-created translation row's fields. Budget: iter 0 for
        // defaults, 2 iters per translation language, plus one trailing
        // iter where the loop notices nothing is left and exits.
        $maxIterations = count($translationLanguages) * 2 + 2;
        // DataHandler carries state across calls (substNEWwithIDs, datamap,
        // cmdmap, errors, …); reusing one instance across passes mixes state.
        // Each pass gets a fresh instance and the substNEWwithIDs maps get
        // merged so the logger sees every NEW→uid mapping the loop produced.
        //
        // The loop survives because translation scenario 2 needs two passes:
        // pass 1 stages a localize cmdMap (creating the translation row),
        // pass 2 picks up the new translation uid and writes its translated
        // fields. Default-language rows and already-resolved transients are
        // idempotent across passes via ResolverContext::defaultStatus and
        // translationStatus — re-resolving a drained payload short-circuits
        // instead of re-querying or re-fetching.
        $substNEWwithIDs = [];
        while ($accumulatedPayload->getDataMap() !== [] || $accumulatedPayload->getCmdMap() !== []) {
            $this->phaseReached = ImportPhase::Persist;
            // Between passes: a pass under way is never interrupted mid-write.
            if (!$aborted && $deadline->isExpired()) {
                $this->recordAbort($deadline, ImportPhase::Persist);
                $aborted = true;
                break;
            }
            if ($iterations >= $maxIterations) {
                throw new RuntimeException(
                    'Importer loop exceeded ' . $maxIterations . ' iterations; translations bucket: '
                    . json_encode($accumulatedPayload->getTranslations()),
                    1777148664
                );
            }
            $this->phaseReached = ImportPhase::Persist;
            $listener->progressed(new ImportProgress(
                ImportPhase::Persist,
                'Writing records',
                $iterations + 1,
                $maxIterations
            ));
            $cmd = $this->fanOutCmdMap($accumulatedPayload->getCmdMap());
            $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
            $dataHandler->enableLogging = false;
            $dataHandler->start($accumulatedPayload->getDataMap(), $cmd);
            $dataHandler->process_datamap();
            $dataHandler->process_cmdmap();
            // DataHandler accumulates user/system errors into errorLog when
            // log() is called with $error > 0. Forward whatever it captured
            // this pass into our import log so editors see why a row failed
            // to land instead of having to grep sys_log.
            /** @var list<string> $passErrorLog */
            $passErrorLog = $dataHandler->errorLog;
            $this->importLogger->recordDataHandlerErrors($passErrorLog, $iterations);
            /** @var array<string, int|string> $passSubst */
            $passSubst = $dataHandler->substNEWwithIDs;
            $substNEWwithIDs = $substNEWwithIDs + $passSubst;
            $accumulatedPayload->clearDataMap();
            $accumulatedPayload->clearCmdMap();
            // Rewrite NEW… entries in the resolver's remote_id→key map to
            // the uids DataHandler just assigned, so the next round wires
            // FKs against real uids instead of stale placeholders.
            $resolverContext->promoteNewKeys($passSubst);
            $this->resolver->resolve($accumulatedPayload, $resolverContext);
            $iterations++;
        }

        $listener->progressed(new ImportProgress(ImportPhase::Log, 'Writing import log'));

        // The category map now holds real uids, so matched entries can point at them.
        $this->importLogger->recordMatchReports($matchReports, $resolverContext->categoryKeyByRemoteId);
        $this->importLogger->recordCategoriesFieldMissing($resolverContext->categoriesFieldMissing);
        $this->importLogger->recordUnusableScheduleDays($parserContext->unusableScheduleDays);
        $this->importLogger->recordDroppedScheduleDays($parserContext->droppedScheduleDays);
        $this->importLogger->recordUnresolvableEventDates($parserContext->unresolvableEventDates);
        $this->importLogger->recordEventsWithoutDates($parserContext->eventsWithoutDates);
        $this->importLogger->writeLog(
            $configuration->getUid(),
            $loggerPayload,
            $substNEWwithIDs
        );

        $listener->progressed(new ImportProgress(ImportPhase::Finish, 'Finishing'));

        return $this->finishRun($targetFolder, $stagingFolder);
    }

    /**
     * Promote staged media unconditionally: a staged file downloaded successfully,
     * so a failure elsewhere in the run says nothing about it.
     */
    protected function finishRun(?Folder $targetFolder, ?Folder $stagingFolder): string
    {
        // Null only without media: nothing was staged, nothing to promote.
        if ($targetFolder !== null && $stagingFolder !== null) {
            $this->mediaFileStaging->promote($stagingFolder, $targetFolder);
        }

        return $this->importLogger->getMaxSeverity();
    }

    /**
     * Fan the staged cmdmap entries out into the nested shape DataHandler::start()
     * consumes: $cmd[$table][$uid][$command] = $value.
     *
     * @param array<string, array<int|string, list<array{0: string, 1: int|string}>>> $cmdMap
     *
     * @return array<string, array<int|string, array<string, int|string>>>
     */
    protected function fanOutCmdMap(array $cmdMap): array
    {
        $result = [];
        foreach ($cmdMap as $table => $entriesByKey) {
            foreach ($entriesByKey as $key => $entries) {
                foreach ($entries as $entry) {
                    $result[$table][$key][$entry[0]] = $entry[1];
                }
            }
        }
        return $result;
    }

    protected function getProviderForConfiguration(ImportConfigurationInterface $configuration): ?UrlProvider
    {
        foreach ($this->urlProviders as $provider) {
            if (!$provider instanceof UrlProvider) {
                continue;
            }
            if ($provider->canProvideForConfiguration($configuration)) {
                return $provider->createWithConfiguration($configuration);
            }
        }

        return null;
    }

    /**
     * Staged, not thrown: the run must still reach writeLog(), or an aborted
     * run leaves nothing behind — which is the failure this exists to fix.
     */
    /**
     * What actually governs this run, assembled once before the first fetch.
     * The values come from three levels (import configuration, site settings,
     * extension configuration), so a run that behaves unexpectedly can be
     * explained without tracing the chain by hand.
     *
     * There is deliberately no apiKey entry: excluding it by construction
     * leaves no masking step to forget and no rendering path that could leak
     * it. Reporting is wrapped because it is diagnostics, not a gate — a
     * failure here must not abort an otherwise healthy run.
     *
     * Only the run's own target's anchors are named. Listing the other one's
     * would suggest it had a say in this run.
     */
    protected function reportEffectiveSettings(
        ImportConfigurationInterface $configuration,
        CategoryAnchors $anchors,
        ImportProgressListener $listener
    ): void {
        try {
            $target = ImportTarget::tryFromConfigured($configuration->getImportTarget());
            if ($target === null) {
                // Unreachable in a run: resolveFor() rejected it already.
                return;
            }

            $settings = [
                'storagePid' => $configuration->getStoragePid(),
                'fileFolder' => $configuration->getFileFolder(),
                'apiDomain' => $configuration->getApiDomain(),
                'importTarget' => $target->value,
            ];
            foreach (CategoryAnchorSetting::cases() as $anchor) {
                $uid = $anchors->for($anchor);
                $settings[$anchor->settingsPath($target)] = $uid > 0 ? $uid : 'unset';
            }
            $settings += [
                'readTimeout' => $this->settings->resolve(ImportSetting::ReadTimeout, 0),
                'connectTimeout' => $this->settings->resolve(ImportSetting::ConnectTimeout, 0),
                'maxAttempts' => $this->settings->resolve(ImportSetting::MaxAttempts, 0),
                'runBudget' => $this->settings->resolve(ImportSetting::RunBudget, $configuration->getRunBudget()),
                'fetchCacheLifetime' => $this->settings->resolve(
                    ImportSetting::FetchCacheLifetime,
                    $configuration->getFetchCacheLifetime()
                ),
            ];

            $this->importLogger->recordEffectiveSettings($settings);
            $listener->settingsResolved($settings);
        } catch (Throwable) {
            // Diagnostics must never cost a run.
        }
    }

    protected function recordAbort(RunDeadline $deadline, ImportPhase $phase): void
    {
        try {
            $deadline->assertNotExpired($phase);
        } catch (RunBudgetExhaustedException $exception) {
            $this->importLogger->recordRunAborted($exception);
        }
    }

    protected function fetchDataFromApi(string $url, string $apiKey): array
    {
        $response = $this->fetchData->jsonLDFromUrl($url, $apiKey === '' ? null : $apiKey);
        $graph = $response['@graph'] ?? [];
        return is_array($graph) ? $graph : [];
    }
}
