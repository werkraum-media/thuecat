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

namespace WerkraumMedia\ThueCat\Import\Job\Handler;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use TYPO3\CMS\Core\Site\SiteFinder;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfiguration;
use WerkraumMedia\ThueCat\Domain\Repository\Backend\ImportConfigurationRepository;
use WerkraumMedia\ThueCat\Import\EffectiveSettingsReport;
use WerkraumMedia\ThueCat\Import\Importer\FetchData;
use WerkraumMedia\ThueCat\Import\ImportLogger;
use WerkraumMedia\ThueCat\Import\Job\ImportJob;
use WerkraumMedia\ThueCat\Import\Job\ImportJobItems;
use WerkraumMedia\ThueCat\Import\Job\ImportJobs;
use WerkraumMedia\ThueCat\Import\Job\Message\ImportStarted;
use WerkraumMedia\ThueCat\Import\Settings\ImportSetting;
use WerkraumMedia\ThueCat\Import\Settings\ImportSettings;
use WerkraumMedia\ThueCat\Import\UrlProvider\SyncChannelUrlProvider;
use WerkraumMedia\ThueCat\Import\UrlProvider\UrlProviders;

#[AsMessageHandler]
final readonly class ImportStartedHandler
{
    public function __construct(
        private ImportJobs $importJobs,
        private ImportJobItems $importJobItems,
        private ImportConfigurationRepository $configurationRepository,
        private UrlProviders $urlProviders,
        private FetchData $fetchData,
        private ImportSettings $settings,
        private SiteFinder $siteFinder,
        private EffectiveSettingsReport $effectiveSettingsReport,
        private ImportLogger $importLogger,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function __invoke(ImportStarted $message): void
    {
        $job = $this->importJobs->find($message->jobUid);
        // A redelivered start finds its job running already.
        if (!$job instanceof ImportJob || $job->status !== 'queued') {
            return;
        }

        $configuration = $this->configurationRepository->findOneByUid($job->configurationUid);
        // Ended at once: left queued, it would be started again and again.
        if (!$configuration instanceof ImportConfiguration) {
            $this->importJobs->markFailed($job->uid);
            return;
        }

        // The consumer is long-lived and the logger shared: entries of an earlier message must not reach this log.
        $this->importLogger->reset();
        $this->effectiveSettingsReport->record(
            $configuration,
            $this->siteFinder->getSiteByPageId($configuration->getStoragePid())
        );
        $this->importJobs->markRunning($job->uid, $this->importLogger->openLog($job->configurationUid));

        $this->fetchData->configureForRun(
            $job->bypassCache,
            $this->settings->resolve(ImportSetting::FetchCacheLifetime, $configuration->getFetchCacheLifetime())
        );
        if ($configuration->getType() === 'syncChannel') {
            $this->fetchData->authenticateRunAgainstSyncHost($configuration->getSyncApiDomain(), $configuration->getApiKey());
        }

        try {
            $urlProvider = $this->urlProviders->forConfiguration($configuration);
            $targets = $urlProvider instanceof SyncChannelUrlProvider
                ? $urlProvider->getTargets()
                : ['import' => array_values($urlProvider->getUrls()), 'delete' => []];
        } finally {
            $this->fetchData->resetRunConfiguration();
        }

        $this->importJobItems->store($job->uid, $targets['import'], $targets['delete']);
        $this->messageBus->dispatch($this->importJobItems->nextMessage($job->uid));
    }
}
