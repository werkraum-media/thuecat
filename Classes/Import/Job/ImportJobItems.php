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

use Doctrine\DBAL\ParameterType;
use Psr\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use WerkraumMedia\ThueCat\Import\Job\Message\DeleteRoot;
use WerkraumMedia\ThueCat\Import\Job\Message\ImportFinished;
use WerkraumMedia\ThueCat\Import\Job\Message\ImportMessage;
use WerkraumMedia\ThueCat\Import\Job\Message\ImportRoot;

/**
 * The work list of a job, stored once at its start so no message asks the URL provider again.
 */
#[Autoconfigure(public: true)]
class ImportJobItems
{
    protected const TABLE = 'tx_thuecat_import_job_item';

    public function __construct(
        protected readonly ConnectionPool $connectionPool,
        protected readonly ClockInterface $clock,
    ) {
    }

    /**
     * Deletes follow all imports.
     *
     * @param list<string> $importTargets
     * @param list<string> $deleteTargets
     */
    public function store(int $job, array $importTargets, array $deleteTargets): void
    {
        $now = $this->clock->now()->getTimestamp();
        $rows = [];
        foreach (['import' => $importTargets, 'delete' => $deleteTargets] as $action => $targets) {
            foreach (array_unique($targets) as $target) {
                $rows[] = [$now, $job, $target, $action, count($rows), 'pending'];
            }
        }

        if ($rows === []) {
            return;
        }

        $this->connectionPool->getConnectionForTable(self::TABLE)->bulkInsert(
            self::TABLE,
            $rows,
            ['crdate', 'job', 'target', 'action', 'position', 'state'],
            [ParameterType::INTEGER, ParameterType::INTEGER, ParameterType::STRING, ParameterType::STRING, ParameterType::INTEGER, ParameterType::STRING]
        );
    }

    /**
     * Starts the item's clock and counts the attempt. A redelivered message finds its item no longer pending.
     */
    public function begin(int $uid): ?ImportJobItem
    {
        $pending = $this->findPending($uid);
        if (!$pending instanceof ImportJobItem) {
            return null;
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE);
        $queryBuilder
            ->update(self::TABLE)
            ->set('attempts', $queryBuilder->quoteIdentifier('attempts') . ' + 1', false)
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)))
            ->executeStatement()
        ;

        return new ImportJobItem(
            $pending->uid,
            $pending->target,
            $pending->position,
            $this->clock->now()->getTimestamp(),
            $pending->attempts + 1
        );
    }

    /**
     * Its clock is not started.
     */
    public function findPending(int $uid): ?ImportJobItem
    {
        $row = $this->connectionPool->getConnectionForTable(self::TABLE)
            ->select(['uid', 'target', 'position', 'attempts'], self::TABLE, ['uid' => $uid, 'state' => 'pending'])
            ->fetchAssociative()
        ;
        if ($row === false || !is_numeric($row['uid']) || !is_string($row['target']) || !is_numeric($row['position']) || !is_numeric($row['attempts'])) {
            return null;
        }

        return new ImportJobItem((int)$row['uid'], $row['target'], (int)$row['position'], 0, (int)$row['attempts']);
    }

    /**
     * True only for the first attempt that asks, so a retried item never sends a second successor.
     */
    public function claimSuccessor(ImportJobItem $item): bool
    {
        return $this->connectionPool->getConnectionForTable(self::TABLE)
            ->update(self::TABLE, ['successor_sent' => 1], ['uid' => $item->uid, 'successor_sent' => 0]) > 0
        ;
    }

    public function hasPending(int $job): bool
    {
        return $this->connectionPool->getConnectionForTable(self::TABLE)
            ->count('uid', self::TABLE, ['job' => $job, 'state' => 'pending']) > 0
        ;
    }

    public function skipPending(int $job): void
    {
        $this->connectionPool->getConnectionForTable(self::TABLE)->update(self::TABLE, ['state' => 'skipped'], ['job' => $job, 'state' => 'pending']);
    }

    public function markFailed(ImportJobItem $item): void
    {
        $this->connectionPool->getConnectionForTable(self::TABLE)->update(self::TABLE, ['state' => 'failed'], ['uid' => $item->uid]);
    }

    public function markDone(ImportJobItem $item): void
    {
        $this->connectionPool->getConnectionForTable(self::TABLE)->update(
            self::TABLE,
            ['state' => 'done', 'duration' => $this->clock->now()->getTimestamp() - $item->startedAt],
            ['uid' => $item->uid]
        );
    }

    /**
     * @return array{done: int, failed: int, skipped: int}
     */
    public function countByState(int $job): array
    {
        $connection = $this->connectionPool->getConnectionForTable(self::TABLE);

        return [
            'done' => $connection->count('uid', self::TABLE, ['job' => $job, 'state' => 'done']),
            'failed' => $connection->count('uid', self::TABLE, ['job' => $job, 'state' => 'failed']),
            'skipped' => $connection->count('uid', self::TABLE, ['job' => $job, 'state' => 'skipped']),
        ];
    }

    /**
     * The message of the first pending item after the given position, or the job's finishing message when none is
     * pending. An item waiting for its retry before that position gets its message from the retry instead.
     */
    public function nextMessage(int $job, int $afterPosition = -1): ImportMessage
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE);
        $item = $queryBuilder
            ->select('uid', 'action')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq('job', $queryBuilder->createNamedParameter($job, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('state', $queryBuilder->createNamedParameter('pending')),
                $queryBuilder->expr()->gt('position', $queryBuilder->createNamedParameter($afterPosition, Connection::PARAM_INT))
            )
            ->orderBy('position')
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative()
        ;
        if ($item === false || !is_numeric($item['uid'])) {
            return new ImportFinished($job);
        }

        return $item['action'] === 'delete'
            ? new DeleteRoot($job, (int)$item['uid'])
            : new ImportRoot($job, (int)$item['uid']);
    }
}
