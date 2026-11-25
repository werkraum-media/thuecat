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

use Doctrine\DBAL\ArrayParameterType;
use Psr\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Messenger\MessageBusInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use WerkraumMedia\ThueCat\Import\Job\Message\ImportStarted;

#[Autoconfigure(public: true)]
class ImportJobs
{
    protected const TABLE = 'tx_thuecat_import_job';

    protected const OPEN_STATUSES = ['queued', 'running', 'stopping'];

    public function __construct(
        protected readonly ConnectionPool $connectionPool,
        protected readonly ClockInterface $clock,
        protected readonly MessageBusInterface $messageBus,
    ) {
    }

    /**
     * @return int|null the job's uid, or null while the configuration has an open job
     */
    public function queue(
        int $configurationUid,
        ImportTrigger $trigger,
        bool $skipMedia,
        bool $bypassCache,
        int $backendUser = 0,
    ): ?int {
        if ($this->hasOpenJob($configurationUid)) {
            return null;
        }

        $now = $this->clock->now()->getTimestamp();
        $connection = $this->connectionPool->getConnectionForTable(self::TABLE);
        $connection->insert(self::TABLE, [
            'crdate' => $now,
            'progressed_at' => $now,
            'configuration' => $configurationUid,
            'job_trigger' => $trigger->value,
            'backend_user' => $backendUser,
            'skip_media' => (int)$skipMedia,
            'bypass_cache' => (int)$bypassCache,
            'status' => 'queued',
        ]);
        $job = (int)$connection->lastInsertId();
        $this->messageBus->dispatch(new ImportStarted($job));

        return $job;
    }

    public function find(int $uid): ?ImportJob
    {
        $row = $this->connectionPool->getConnectionForTable(self::TABLE)
            ->select(['uid', 'configuration', 'status', 'skip_media', 'bypass_cache', 'import_log', 'started_at', 'backend_user'], self::TABLE, ['uid' => $uid])
            ->fetchAssociative()
        ;
        if ($row === false) {
            return null;
        }

        return new ImportJob(
            $this->intOf($row['uid']),
            $this->intOf($row['configuration']),
            is_string($row['status']) ? $row['status'] : '',
            (bool)$this->intOf($row['skip_media']),
            (bool)$this->intOf($row['bypass_cache']),
            $this->intOf($row['import_log']),
            $this->intOf($row['started_at']),
            $this->intOf($row['backend_user']),
        );
    }

    public function markRunning(int $uid, int $importLog): void
    {
        $now = $this->clock->now()->getTimestamp();
        $this->connectionPool->getConnectionForTable(self::TABLE)->update(
            self::TABLE,
            ['status' => 'running', 'started_at' => $now, 'progressed_at' => $now, 'import_log' => $importLog],
            ['uid' => $uid]
        );
    }

    public function markFailed(int $uid): void
    {
        $now = $this->clock->now()->getTimestamp();
        $this->connectionPool->getConnectionForTable(self::TABLE)->update(
            self::TABLE,
            ['status' => 'failed', 'ended_at' => $now, 'progressed_at' => $now],
            ['uid' => $uid]
        );
    }

    /**
     * @param array{done: int, failed: int, skipped: int} $counts
     */
    public function finish(int $uid, string $status, array $counts): void
    {
        $now = $this->clock->now()->getTimestamp();
        $this->connectionPool->getConnectionForTable(self::TABLE)->update(
            self::TABLE,
            [
                'status' => $status,
                'ended_at' => $now,
                'progressed_at' => $now,
                'done_items' => $counts['done'],
                'failed_items' => $counts['failed'],
                'skipped_items' => $counts['skipped'],
            ],
            ['uid' => $uid]
        );
    }

    public function hasOpenJob(int $configurationUid): bool
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE);

        $count = $queryBuilder
            ->count('uid')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq('configuration', $queryBuilder->createNamedParameter($configurationUid, Connection::PARAM_INT)),
                $queryBuilder->expr()->in('status', $queryBuilder->createNamedParameter(self::OPEN_STATUSES, ArrayParameterType::STRING))
            )
            ->executeQuery()
            ->fetchOne()
        ;

        return is_numeric($count) && (int)$count > 0;
    }

    protected function intOf(mixed $value): int
    {
        return is_numeric($value) ? (int)$value : 0;
    }
}
