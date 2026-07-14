<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Import;

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
use WerkraumMedia\ThueCat\Import\UrlProvider\InvalidUrlProviderException;
use WerkraumMedia\ThueCat\Import\UrlProvider\UrlProvider;

class Importer
{
    public function __construct(
        protected readonly Parser $parser,
        protected readonly FetchData $fetchData,
        protected readonly SiteFinder $siteFinder,
        protected readonly Resolver $resolver,
        protected readonly FileFolderAccess $fileFolderAccess,
        protected readonly MediaFileStaging $mediaFileStaging,
        protected readonly ImportLogger $importLogger,
        protected readonly ImportConfigurationValidator $configurationValidator,
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
    public function importConfiguration(ImportConfigurationInterface $configuration): string
    {
        // Pre-flight: abort on a misconfiguration before touching the API.
        $this->configurationValidator->validate($configuration);

        // Resolve the target folder (this runs the write-access probe) and
        // create a fresh per-run staging folder under it before touching the
        // API. Media is downloaded into staging and only promoted into the
        // target on a clean run; the staging folder is always discarded in
        // the finally, so a failed run leaves no orphaned files behind.
        $targetFolder = $this->fileFolderAccess->resolveFolder($configuration->getFileFolder());
        $stagingFolder = $this->mediaFileStaging->createForRun($targetFolder);

        try {
            return $this->runImport($configuration, $targetFolder, $stagingFolder);
        } finally {
            $this->mediaFileStaging->discard($stagingFolder);
        }
    }

    protected function runImport(
        ImportConfigurationInterface $configuration,
        Folder $targetFolder,
        Folder $stagingFolder
    ): string {
        $urlProvider = $this->getProviderForConfiguration($configuration);
        if (!$urlProvider instanceof UrlProvider) {
            throw new InvalidUrlProviderException('No URL Provider available for given configuration.', 1629296635);
        }

        $apiKey = $configuration->getApiKey();
        $apiDomain = $configuration->getApiDomain();
        $translationLanguages = [];
        $defaultLanguage = 'de'; // fallback
        foreach ($this->siteFinder->getSiteByPageId($configuration->getStoragePid())->getLanguages() as $siteLanguage) {
            if ($siteLanguage->getLanguageId() === 0) {
                $defaultLanguage = $siteLanguage->getLocale()->getLanguageCode();
            } else {
                $translationLanguages[$siteLanguage->getLocale()->getLanguageCode()] = $siteLanguage->getLanguageId();
            }
        }
        $parserContext = new ParserContext((int)$configuration->getUid(), $apiDomain);
        $resolverContext = new ResolverContext(
            $configuration->getStoragePid(),
            $parserContext,
            $defaultLanguage,
            $configuration->getApiKey(),
            $translationLanguages,
            $targetFolder,
            $stagingFolder,
            $configuration->getCategoryParent(),
            $configuration->getCategoryStoragePid(),
        );
        $accumulatedPayload = new DataHandlerPayload();
        foreach ($urlProvider->getUrls($apiDomain) as $url) {
            // Per-URL try/catch so a single broken root doesn't abort the
            // run. The exception is staged into the import log and the
            // loop moves on; the run finishes with severity 'error' so the
            // command surfaces a non-zero exit code.
            try {
                $inputData = $this->fetchDataFromApi($url, $apiKey);
            } catch (InvalidResponseException $e) {
                $this->importLogger->recordException('fetchingError', $e);
                continue;
            }
            try {
                $this->parser->parse($inputData, $parserContext, $defaultLanguage, $translationLanguages);
                $resolved = $this->resolver->resolve($this->parser->getDataHandlerPayload(), $resolverContext);
            } catch (Throwable $e) {
                $this->importLogger->recordException('mappingError', $e);
                continue;
            }
            $accumulatedPayload->mergeFrom($resolved);
        }

        // Snapshot before the loop drains the datamap. Translation rows added
        // by the resolver are excluded so the logger reports only the
        // default-language records the user expects to see counted.
        $loggerPayload = $accumulatedPayload->getDefaultLanguageDataMap();

        // Snapshot before the loop drains it; recorded after the loop so matched
        // entries can carry the uids promoted once persisting has run.
        $matchReports = $accumulatedPayload->getMatchReports();

        if ($accumulatedPayload->getDataMap() === [] && $accumulatedPayload->getCmdMap() === []) {
            // Nothing persisted; still report the types seen.
            $this->importLogger->recordMatchReports($matchReports);
            $this->importLogger->writeLog(
                $configuration->getUid(),
                $loggerPayload,
                []
            );
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
            if ($iterations >= $maxIterations) {
                throw new RuntimeException(
                    'Importer loop exceeded ' . $maxIterations . ' iterations; translations bucket: '
                    . json_encode($accumulatedPayload->getTranslations()),
                    1777148664
                );
            }
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

        // The category map now holds real uids, so matched entries can point at them.
        $this->importLogger->recordMatchReports($matchReports, $resolverContext->categoryKeyByRemoteId);
        $this->importLogger->recordCategoriesFieldMissing($resolverContext->categoriesFieldMissing);
        $this->importLogger->writeLog(
            $configuration->getUid(),
            $loggerPayload,
            $substNEWwithIDs
        );

        return $this->finishRun($targetFolder, $stagingFolder);
    }

    /**
     * Promote staged media into the target folder when the run is clean. Else, staged
     * file remain and will be discarded.
     */
    protected function finishRun(Folder $targetFolder, Folder $stagingFolder): string
    {
        $severity = $this->importLogger->getMaxSeverity();
        if ($severity !== ImportLogger::SEVERITY_ERROR) {
            $this->mediaFileStaging->promote($stagingFolder, $targetFolder);
        }

        return $severity;
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

    protected function fetchDataFromApi(string $url, string $apiKey): array
    {
        $response = $this->fetchData->jsonLDFromUrl($url, $apiKey === '' ? null : $apiKey);
        $graph = $response['@graph'] ?? [];
        return is_array($graph) ? $graph : [];
    }
}
