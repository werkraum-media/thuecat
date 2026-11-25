<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS extension "thuecat".
 *
 * Copyright (C) werkraum-media <https://werkraum-media.de/>
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * For the full license text, see the LICENSE file distributed with this
 * extension.
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 */

namespace WerkraumMedia\ThueCat\Import;

use Psr\Http\Client\ClientExceptionInterface;
use RuntimeException;
use Throwable;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfiguration;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfigurationInterface;
use WerkraumMedia\ThueCat\Import\Importer\FetchData;
use WerkraumMedia\ThueCat\Import\Importer\FetchData\InvalidResponseException;
use WerkraumMedia\ThueCat\Import\Parser\Parser;
use WerkraumMedia\ThueCat\Import\Parser\ParserContext;
use WerkraumMedia\ThueCat\Import\Progress\ImportPhase;
use WerkraumMedia\ThueCat\Import\Progress\ImportProgress;
use WerkraumMedia\ThueCat\Import\Progress\ImportProgressListener;
use WerkraumMedia\ThueCat\Import\Progress\NullProgressListener;
use WerkraumMedia\ThueCat\Import\Settings\ImportSetting;
use WerkraumMedia\ThueCat\Import\Settings\ImportSettings;
use WerkraumMedia\ThueCat\Import\SyncChannel\SyncChannel;
use WerkraumMedia\ThueCat\Import\UrlProvider\UrlProviders;
use WerkraumMedia\ThueCat\Import\Watchdog\RunBudgetExhaustedException;
use WerkraumMedia\ThueCat\Import\Watchdog\RunDeadline;
use WerkraumMedia\ThueCat\Service\SitePageIds;

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
        protected readonly EffectiveSettingsReport $effectiveSettingsReport,
        protected readonly SitePageIds $sitePageIds,
        protected readonly EventPlaceMatcher $eventPlaceMatcher,
        protected readonly UrlProviders $urlProviders
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
        $deadline ??= $this->deadlineFor($configuration);

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

        $this->configureFetching($configuration, $bypassCache);

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
            if ($stagingFolder instanceof Folder) {
                $this->mediaFileStaging->discard($stagingFolder);
            }
        }
    }

    /**
     * Imports one root on DataHandler runs that cover only it and what it references.
     */
    public function importRoot(
        ImportConfigurationInterface $configuration,
        string $url,
        bool $bypassCache,
        bool $skipMedia
    ): RootOutcome {
        $this->configurationValidator->validate($configuration);
        $targetFolder = null;
        $stagingFolder = null;
        if ($skipMedia === false) {
            $targetFolder = $this->fileFolderAccess->resolveFolder($configuration->getFileFolder());
            $stagingFolder = $this->mediaFileStaging->createForRun($targetFolder);
        }

        $this->configureFetching($configuration, $bypassCache);

        try {
            $site = $this->siteFinder->getSiteByPageId($configuration->getStoragePid());
            $outcome = $this->persistRoot(
                $configuration,
                $url,
                $site,
                $this->sitePageIds->forRootPage($site->getRootPageId()),
                $targetFolder,
                $stagingFolder,
                new NullProgressListener(),
                $this->deadlineFor($configuration),
                $skipMedia
            );
            $this->finishRun($targetFolder, $stagingFolder);

            return $outcome;
        } finally {
            $this->fetchData->resetRunConfiguration();
            if ($stagingFolder instanceof Folder) {
                $this->mediaFileStaging->discard($stagingFolder);
            }
        }
    }

    protected function deadlineFor(ImportConfigurationInterface $configuration): RunDeadline
    {
        return new RunDeadline($this->settings->resolve(ImportSetting::RunBudget, $configuration->getRunBudget()));
    }

    protected function configureFetching(ImportConfigurationInterface $configuration, bool $bypassCache): void
    {
        $this->fetchData->configureForRun(
            $bypassCache,
            $this->settings->resolve(ImportSetting::FetchCacheLifetime, $configuration->getFetchCacheLifetime())
        );
        if ($configuration instanceof ImportConfiguration && $configuration->getType() === 'syncChannel') {
            $this->fetchData->authenticateRunAgainstSyncHost($configuration->getSyncApiDomain(), $configuration->getApiKey());
        }
    }

    protected function runImport(
        ImportConfigurationInterface $configuration,
        ?Folder $targetFolder,
        ?Folder $stagingFolder,
        ImportProgressListener $listener,
        RunDeadline $deadline,
        bool $skipMedia
    ): string {
        $urlProvider = $this->urlProviders->forConfiguration($configuration);
        $site = $this->siteFinder->getSiteByPageId($configuration->getStoragePid());
        $this->reportEffectiveSettings($configuration, $site, $listener);
        // Once per run: the record lookup consults this per record and per
        // relation, so deriving it there would rewalk the page tree for
        // every one of them.
        $sitePageIds = $this->sitePageIds->forRootPage($site->getRootPageId());

        $loggerPayload = [];
        $substNEWwithIDs = [];
        $urls = $urlProvider->getUrls();
        $urlCount = count($urls);
        $urlNumber = 0;
        foreach ($urls as $url) {
            if ($deadline->isExpired()) {
                $this->recordAbort($deadline, ImportPhase::Fetch);
                break;
            }

            $urlNumber++;
            $listener->progressed(new ImportProgress(ImportPhase::Fetch, $url, $urlNumber, $urlCount));
            $outcome = $this->persistRoot(
                $configuration,
                $url,
                $site,
                $sitePageIds,
                $targetFolder,
                $stagingFolder,
                $listener,
                $deadline,
                $skipMedia
            );
            foreach ($outcome->loggerPayload as $table => $rows) {
                $loggerPayload[$table] = ($loggerPayload[$table] ?? []) + $rows;
            }

            $substNEWwithIDs += $outcome->substNEWwithIDs;
            if ($outcome->aborted) {
                break;
            }
        }

        $listener->progressed(new ImportProgress(ImportPhase::Log, 'Writing import log'));
        $this->importLogger->writeLog($configuration->getUid(), $loggerPayload, $substNEWwithIDs);
        $listener->progressed(new ImportProgress(ImportPhase::Finish, 'Finishing'));

        return $this->finishRun($targetFolder, $stagingFolder);
    }

    /**
     * Fetches, resolves and persists one root with its translations, relations and files. Every map lives on a
     * resolver context of this root alone; what an earlier root stored is found in the database.
     *
     * @param list<int> $sitePageIds
     */
    protected function persistRoot(
        ImportConfigurationInterface $configuration,
        string $url,
        Site $site,
        array $sitePageIds,
        ?Folder $targetFolder,
        ?Folder $stagingFolder,
        ImportProgressListener $listener,
        RunDeadline $deadline,
        bool $skipMedia
    ): RootOutcome {
        $translationLanguages = [];
        $defaultLanguage = 'de'; // fallback
        foreach ($site->getLanguages() as $siteLanguage) {
            if ($siteLanguage->getLanguageId() === 0) {
                $defaultLanguage = $siteLanguage->getLocale()->getLanguageCode();
            } else {
                $translationLanguages[$siteLanguage->getLocale()->getLanguageCode()] = $siteLanguage->getLanguageId();
            }
        }

        $parserContext = new ParserContext((int)$configuration->getUid());
        $resolverContext = new ResolverContext(
            $configuration->getStoragePid(),
            $parserContext,
            $defaultLanguage,
            $configuration->getApiKey(),
            $translationLanguages,
            $targetFolder,
            $stagingFolder,
            $listener,
            $skipMedia,
            $sitePageIds,
            $site,
            $configuration instanceof ImportConfiguration && $configuration->getType() === 'syncChannel'
                ? SyncChannel::recordUrlPrefix($configuration->getSyncApiDomain(), $configuration->getSyncChannelId())
                : null,
            [parse_url(FetchData::ASSET_HOST, PHP_URL_HOST)],
            $configuration instanceof ImportConfiguration ? ImportOrigin::fromConfiguration($configuration) : null,
        );

        $this->phaseReached = ImportPhase::Fetch;
        // A broken root is logged and costs only itself.
        try {
            $inputData = $this->fetchDataFromApi($url, $configuration->getApiKey());
        } catch (InvalidResponseException | ClientExceptionInterface $e) {
            $this->importLogger->recordException('fetchingError', $e, $url);
            return new RootOutcome();
        }

        try {
            $this->parser->parse($inputData, $parserContext, $defaultLanguage, $translationLanguages);
            $payload = $this->resolver->resolve($this->parser->getDataHandlerPayload(), $resolverContext);
        } catch (Throwable $e) {
            $this->importLogger->recordException('mappingError', $e, $url);
            return new RootOutcome();
        }

        // Not during resolution: only now is the produced-set complete.
        $this->resolver->flushCollectedMedia($payload, $resolverContext);
        $this->resolver->flushCollectedKeywords($payload, $resolverContext);

        // Snapshot before the loop drains the datamap. Translation rows added
        // by the resolver are excluded so the logger reports only the
        // default-language records the user expects to see counted.
        $loggerPayload = $payload->getDefaultLanguageDataMap();

        // Snapshot before the loop drains it; recorded after the loop so matched
        // entries can carry the uids promoted once persisting has run.
        $matchReports = $payload->getMatchReports();

        $iterations = 0;
        $aborted = false;
        // DataHandler's cmdMap is keyed [table][uid][command] = value, so
        // two localize commands on the same uid (one per target language)
        // collapse to a single entry — only the last survives. Each
        // additional language therefore needs its own iteration: round N
        // stages localize for one outstanding language, round N+1 fills
        // the just-created translation row's fields. Budget: iter 0 for
        // defaults, 2 iters per translation language, plus one trailing
        // iter where the loop notices nothing is left and exits.
        // The +1 is for categories: they are created during the first pass, so
        // their localize can only be staged from the pass after, one behind the
        // records they hang on.
        $maxIterations = count($translationLanguages) * 2 + 3;
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
        while ($payload->getDataMap() !== [] || $payload->getCmdMap() !== []) {
            $this->phaseReached = ImportPhase::Persist;
            // Between passes: a pass under way is never interrupted mid-write.
            if ($deadline->isExpired()) {
                $this->recordAbort($deadline, ImportPhase::Persist);
                $aborted = true;
                break;
            }

            if ($iterations >= $maxIterations) {
                throw new RuntimeException(
                    'Importer loop exceeded ' . $maxIterations . ' iterations; translations bucket: '
                    . json_encode($payload->getTranslations()),
                    1777148664
                );
            }

            $listener->progressed(new ImportProgress(
                ImportPhase::Persist,
                'Writing records',
                $iterations + 1,
                $maxIterations
            ));
            $cmd = $this->fanOutCmdMap($payload->getCmdMap());
            $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
            // Logging stays on: DataHandler fills errorLog only while it logs.
            $dataHandler->start($payload->getDataMap(), $cmd);
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
            $substNEWwithIDs += $passSubst;
            $payload->clearDataMap();
            $payload->clearCmdMap();
            // Rewrite NEW… entries in the resolver's remote_id→key map to
            // the uids DataHandler just assigned, so the next round wires
            // FKs against real uids instead of stale placeholders.
            $resolverContext->promoteNewKeys($passSubst);
            $this->resolver->resolve($payload, $resolverContext);
            $iterations++;
        }

        // Only now do the events have uids the places can point at. Staged as
        // ordinary update rows, so a final pass writes them.
        $this->eventPlaceMatcher->flush($payload, $resolverContext);
        if ($payload->getDataMap() !== []) {
            $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
            $dataHandler->start($payload->getDataMap(), []);
            $dataHandler->process_datamap();
            /** @var list<string> $matchErrorLog */
            $matchErrorLog = $dataHandler->errorLog;
            $this->importLogger->recordDataHandlerErrors($matchErrorLog, $iterations);
            $payload->clearDataMap();
        }

        // The category map now holds real uids, so matched entries can point at them.
        $this->importLogger->recordMatchReports($matchReports, $resolverContext->categoryKeyByIdentifier());
        $this->importLogger->recordTableMismatches(
            $resolverContext->tableMismatches,
            $resolverContext->remoteIdToKey,
            $resolverContext->remoteIdToTable
        );
        $this->importLogger->recordCategoriesFieldMissing($resolverContext->categoriesFieldMissing);
        $this->importLogger->recordUnusableScheduleDays($parserContext->unusableScheduleDays);
        $this->importLogger->recordDroppedScheduleDays($parserContext->droppedScheduleDays);
        $this->importLogger->recordUnresolvableEventDates($parserContext->unresolvableEventDates);
        $this->importLogger->recordEventsWithoutDates($parserContext->eventsWithoutDates);
        $this->importLogger->recordUnmatchedValues($parserContext->unmatchedValues);

        return new RootOutcome($loggerPayload, $substNEWwithIDs, $aborted);
    }

    /**
     * Promote staged media unconditionally: a staged file downloaded successfully,
     * so a failure elsewhere in the run says nothing about it.
     */
    protected function finishRun(?Folder $targetFolder, ?Folder $stagingFolder): string
    {
        // Null only without media: nothing was staged, nothing to promote.
        if ($targetFolder instanceof Folder && $stagingFolder instanceof Folder) {
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

    protected function reportEffectiveSettings(
        ImportConfigurationInterface $configuration,
        Site $site,
        ImportProgressListener $listener
    ): void {
        $settings = $this->effectiveSettingsReport->record($configuration, $site);
        if ($settings === []) {
            return;
        }

        try {
            $listener->settingsResolved($settings);
        } catch (Throwable) {
            // Diagnostics must never cost a run.
        }
    }

    /**
     * Staged, not thrown: the run must still reach writeLog(), or an aborted
     * run leaves nothing behind — which is the failure this exists to fix.
     */
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
