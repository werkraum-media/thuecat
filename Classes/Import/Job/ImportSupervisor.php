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

namespace WerkraumMedia\ThueCat\Import\Job;

use Psr\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Messenger\MessageBusInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use WerkraumMedia\ThueCat\Import\Job\Message\ImportDue;
use WerkraumMedia\ThueCat\Import\Job\Message\ImportStarted;
use WerkraumMedia\ThueCat\Import\Settings\ImportSetting;
use WerkraumMedia\ThueCat\Import\Settings\ImportSettings;
use WerkraumMedia\ThueCat\Service\WaitingMessages;

/**
 * Recovers what a lost message or a killed consumer leaves behind. It assumes to run every minute and relies on no
 * other timing.
 */
#[Autoconfigure(public: true)]
final readonly class ImportSupervisor
{
    private const string CONFIGURATION_TABLE = 'tx_thuecat_import_configuration';

    public function __construct(
        private ConnectionPool $connectionPool,
        private ClockInterface $clock,
        private MessageBusInterface $messageBus,
        private WaitingMessages $waitingMessages,
        private ImportJobs $importJobs,
        private ImportJobItems $importJobItems,
        private ImportSettings $settings,
    ) {
    }

    public function supervise(): void
    {
        $now = $this->clock->now()->getTimestamp();
        $this->startDueConfigurations($now);
        $this->resumeBrokenChains($now);
        $this->resendLostStarts();
    }

    /**
     * Covers a lost due message and a schedule an editor has just set.
     */
    private function startDueConfigurations(int $now): void
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::CONFIGURATION_TABLE);
        $due = $queryBuilder
            ->select('uid', 'next_run')
            ->from(self::CONFIGURATION_TABLE)
            ->where(
                $queryBuilder->expr()->gt('schedule_interval', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                $queryBuilder->expr()->lte('next_run', $queryBuilder->createNamedParameter($now, Connection::PARAM_INT))
            )
            ->executeQuery()
            ->fetchAllAssociative()
        ;
        foreach ($due as $configuration) {
            $uid = filter_var($configuration['uid'], FILTER_VALIDATE_INT);
            $nextRun = filter_var($configuration['next_run'], FILTER_VALIDATE_INT);
            if ($uid === false || $nextRun === false || $this->importJobs->hasOpenJob($uid) || $this->hasWaitingDueMessage($uid)) {
                continue;
            }

            $this->messageBus->dispatch(new ImportDue($uid, $nextRun));
        }
    }

    private function hasWaitingDueMessage(int $configurationUid): bool
    {
        return $this->waitingMessages->exists(
            static fn (object $message): bool => $message instanceof ImportDue && $message->configurationUid === $configurationUid
        );
    }

    /**
     * An item runs one budget at most, so a chain without progress for twice as long lost its message: a consumer
     * died before sending the successor, or a failed message was never sent again. A stopping job's item gives up
     * the rest when its message arrives.
     */
    private function resumeBrokenChains(int $now): void
    {
        foreach ($this->importJobs->uidsWithStatus('running', 'stopping') as $uid) {
            $job = $this->importJobs->find($uid);
            if (!$job instanceof ImportJob) {
                continue;
            }

            $budget = $this->settings->resolve(ImportSetting::MessageBudget, $this->messageBudgetOf($job->configurationUid));
            if ($job->progressedAt >= $now - 2 * $budget || $this->importJobs->hasWaitingMessage($job->uid)) {
                continue;
            }

            $this->messageBus->dispatch($this->importJobItems->nextMessage($job->uid));
        }
    }

    private function resendLostStarts(): void
    {
        foreach ($this->importJobs->uidsWithStatus('queued') as $uid) {
            if (!$this->importJobs->hasWaitingMessage($uid)) {
                $this->messageBus->dispatch(new ImportStarted($uid));
            }
        }
    }

    private function messageBudgetOf(int $configurationUid): mixed
    {
        return $this->connectionPool->getConnectionForTable(self::CONFIGURATION_TABLE)
            ->select(['message_budget'], self::CONFIGURATION_TABLE, ['uid' => $configurationUid])
            ->fetchOne()
        ;
    }
}
