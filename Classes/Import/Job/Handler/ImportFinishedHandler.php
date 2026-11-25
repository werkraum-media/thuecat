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
use Symfony\Component\Messenger\Stamp\DelayStamp;
use TYPO3\CMS\Core\Database\ConnectionPool;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfiguration;
use WerkraumMedia\ThueCat\Domain\Repository\Backend\ImportConfigurationRepository;
use WerkraumMedia\ThueCat\Import\ImportLogger;
use WerkraumMedia\ThueCat\Import\Job\ImportJob;
use WerkraumMedia\ThueCat\Import\Job\ImportJobItems;
use WerkraumMedia\ThueCat\Import\Job\ImportJobs;
use WerkraumMedia\ThueCat\Import\Job\Message\ImportFinished;
use WerkraumMedia\ThueCat\Import\MediaStagingSweep;
use WerkraumMedia\ThueCat\Import\SyncChannel\SyncWindow;

/**
 * Ends a job with its final state and records the run on its configuration. Every outcome is the configuration's last
 * run; only a job that is done without a failed run is its last successful run, which the sync window starts from.
 */
#[AsMessageHandler]
final readonly class ImportFinishedHandler
{
    private const int WAIT_MILLISECONDS = 60000;

    public function __construct(
        private ImportJobs $importJobs,
        private ImportJobItems $importJobItems,
        private ImportConfigurationRepository $configurationRepository,
        private SyncWindow $syncWindow,
        private ImportLogger $importLogger,
        private ConnectionPool $connectionPool,
        private MessageBusInterface $messageBus,
        private MediaStagingSweep $mediaStagingSweep,
    ) {
    }

    public function __invoke(ImportFinished $message): void
    {
        $job = $this->importJobs->find($message->jobUid);
        if (!$job instanceof ImportJob) {
            return;
        }

        // An item awaiting its retry still decides the job's outcome.
        if ($this->importJobItems->hasPending($job->uid)) {
            $this->messageBus->dispatch($message, [new DelayStamp(self::WAIT_MILLISECONDS)]);
            return;
        }

        $counts = $this->importJobItems->countByState($job->uid);
        $status = match (true) {
            $job->status === 'stopping' => 'stopped',
            $job->status === 'failed', $counts['failed'] > 0 => 'failed',
            default => 'done',
        };

        $configuration = $this->configurationRepository->findOneByUid($job->configurationUid);
        if ($configuration instanceof ImportConfiguration) {
            $this->warnOfEmptyStorageFolder($job, $configuration);
        }

        $this->recordRun($job, $status === 'done' && !$this->logHoldsAFailedRun($job));
        $this->importJobs->finish($job->uid, $status, $counts);

        // A consumer killed mid-item leaves its staging folder behind.
        if ($configuration instanceof ImportConfiguration && !$job->skipMedia) {
            $this->mediaStagingSweep->sweepJob($configuration->getFileFolder(), $job->uid);
        }
    }

    /**
     * A job can succeed and still leave its folder empty, which restarts the five-year window on every job.
     */
    private function warnOfEmptyStorageFolder(ImportJob $job, ImportConfiguration $configuration): void
    {
        if ($configuration->getType() !== 'syncChannel' || $this->syncWindow->holdsRecord($configuration->getStoragePid())) {
            return;
        }

        // The consumer is long-lived and the logger shared: entries of an earlier message must not reach this log.
        $this->importLogger->reset();
        $this->importLogger->recordStorageFolderEmpty($configuration->getStoragePid());
        $this->importLogger->writeLogInto($job->importLog, [], []);
    }

    private function logHoldsAFailedRun(ImportJob $job): bool
    {
        return $this->connectionPool->getConnectionForTable('tx_thuecat_import_log_entry')
            ->count('uid', 'tx_thuecat_import_log_entry', ['import_log' => $job->importLog, 'type' => 'runFailed']) > 0
        ;
    }

    private function recordRun(ImportJob $job, bool $successful): void
    {
        $runs = ['last_run' => $job->startedAt];
        if ($successful) {
            $runs['last_successful_run'] = $job->startedAt;
        }

        $this->connectionPool->getConnectionForTable('tx_thuecat_import_configuration')
            ->update('tx_thuecat_import_configuration', $runs, ['uid' => $job->configurationUid])
        ;
    }
}
