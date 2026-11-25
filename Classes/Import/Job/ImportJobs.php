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
use WerkraumMedia\ThueCat\Import\Job\Message\DeleteRoot;
use WerkraumMedia\ThueCat\Import\Job\Message\ImportFinished;
use WerkraumMedia\ThueCat\Import\Job\Message\ImportRoot;
use WerkraumMedia\ThueCat\Import\Job\Message\ImportStarted;
use WerkraumMedia\ThueCat\Service\WaitingMessages;

#[Autoconfigure(public: true)]
class ImportJobs
{
    protected const TABLE = 'tx_thuecat_import_job';

    protected const OPEN_STATUSES = ['queued', 'running', 'stopping'];

    public function __construct(
        protected readonly ConnectionPool $connectionPool,
        protected readonly ClockInterface $clock,
        protected readonly MessageBusInterface $messageBus,
        protected readonly ImportJobItems $importJobItems,
        protected readonly WaitingMessages $waitingMessages,
    ) {
    }

    /**
     * @return int|null the job's uid, or null while the configuration has an open job
     */
    public function queueManual(int $configurationUid, bool $skipMedia, bool $bypassCache, int $backendUser): ?int
    {
        return $this->queue($configurationUid, ImportTrigger::Manual, $skipMedia, $bypassCache, $backendUser);
    }

    /**
     * @return int|null the job's uid, or null while the configuration has an open job
     */
    public function queueScheduled(int $configurationUid): ?int
    {
        $skipMedia = $this->connectionPool->getConnectionForTable('tx_thuecat_import_configuration')
            ->select(['skip_media'], 'tx_thuecat_import_configuration', ['uid' => $configurationUid])
            ->fetchOne()
        ;

        return $this->queue($configurationUid, ImportTrigger::Schedule, (bool)$this->intOf($skipMedia), false, 0);
    }

    protected function queue(
        int $configurationUid,
        ImportTrigger $trigger,
        bool $skipMedia,
        bool $bypassCache,
        int $backendUser,
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
            ->select(['uid', 'configuration', 'status', 'skip_media', 'bypass_cache', 'import_log', 'started_at', 'backend_user', 'progressed_at'], self::TABLE, ['uid' => $uid])
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
            $this->intOf($row['progressed_at']),
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

    /**
     * An item has begun. The supervisor takes a job without progress for twice the message budget as a broken chain.
     */
    public function markProgressed(int $uid): void
    {
        $this->connectionPool->getConnectionForTable(self::TABLE)
            ->update(self::TABLE, ['progressed_at' => $this->clock->now()->getTimestamp()], ['uid' => $uid])
        ;
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

    /**
     * The item being handled finishes. The job's next message gives up the rest, or the stop itself does when no
     * message of the job is waiting.
     */
    public function stop(int $uid): void
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE);
        $stopping = $queryBuilder
            ->update(self::TABLE)
            ->set('status', 'stopping')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)),
                $queryBuilder->expr()->in('status', $queryBuilder->createNamedParameter(['queued', 'running'], ArrayParameterType::STRING))
            )
            ->executeStatement()
        ;
        if ($stopping === 0 || $this->hasWaitingMessage($uid)) {
            return;
        }

        $this->abandonPendingItems($uid);
    }

    /**
     * Skips what a stopping job has left and sends its finishing message.
     */
    public function abandonPendingItems(int $uid): void
    {
        $this->importJobItems->skipPending($uid);
        $this->messageBus->dispatch(new ImportFinished($uid));
    }

    /**
     * @return list<int>
     */
    public function openJobUids(): array
    {
        return $this->uidsWithStatus(...self::OPEN_STATUSES);
    }

    /**
     * @return list<int>
     */
    public function uidsWithStatus(string ...$statuses): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE);

        return array_map(
            $this->intOf(...),
            $queryBuilder
                ->select('uid')
                ->from(self::TABLE)
                ->where(
                    $queryBuilder->expr()->in('status', $queryBuilder->createNamedParameter($statuses, ArrayParameterType::STRING))
                )
                ->executeQuery()
                ->fetchFirstColumn()
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

    /**
     * A message being handled is no longer waiting.
     */
    public function hasWaitingMessage(int $uid): bool
    {
        return $this->waitingMessages->exists(fn (object $message): bool => $this->isMessageOf($message, $uid));
    }

    protected function isMessageOf(object $message, int $uid): bool
    {
        return ($message instanceof ImportStarted
            || $message instanceof ImportRoot
            || $message instanceof DeleteRoot
            || $message instanceof ImportFinished
        ) && $message->jobUid === $uid;
    }

    protected function intOf(mixed $value): int
    {
        return is_numeric($value) ? (int)$value : 0;
    }
}
