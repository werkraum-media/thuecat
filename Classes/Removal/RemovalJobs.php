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

namespace WerkraumMedia\ThueCat\Removal;

use Psr\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

/**
 * The removal jobs: what was selected, by whom, and how far the removal got.
 *
 * Public, so functional tests can fetch it from the container.
 */
#[Autoconfigure(public: true)]
class RemovalJobs
{
    protected const TABLE = 'tx_thuecat_removal_job';

    /**
     * The consumer runs every minute; a job left alone for this long has no consumer taking it further.
     */
    protected const STALLED_AFTER_SECONDS = 300;

    /**
     * The garbage collection the documentation recommends keeps finished jobs for this long.
     */
    protected const CLEANUP_AFTER_SECONDS = 864000;

    public function __construct(
        protected readonly ConnectionPool $connectionPool,
        protected readonly ClockInterface $clock,
    ) {
    }

    public function queue(Selection $selection, bool $removeFiles, bool $updateReferenceIndex, int $backendUser): int
    {
        $now = $this->clock->now()->getTimestamp();
        $connection = $this->connectionPool->getConnectionForTable(self::TABLE);
        $connection->insert(self::TABLE, [
            'crdate' => $now,
            'progressed_at' => $now,
            'backend_user' => $backendUser,
            'status' => 'queued',
            'root_table' => $selection->rootTable,
            'scope' => $selection->scope->value,
            'scope_page' => $selection->scopePage,
            'origin_configuration_uid' => $selection->originConfigurationUid,
            'remove_files' => (int)$removeFiles,
            'update_reference_index' => (int)$updateReferenceIndex,
        ]);

        return (int)$connection->lastInsertId();
    }

    public function find(int $uid): ?RemovalJob
    {
        $row = $this->connectionPool->getConnectionForTable(self::TABLE)
            ->select(['*'], self::TABLE, ['uid' => $uid])
            ->fetchAssociative()
        ;
        if ($row === false) {
            return null;
        }

        return $this->jobOf($row);
    }

    public function overview(): RemovalJobOverview
    {
        $rows = $this->connectionPool->getConnectionForTable(self::TABLE)
            ->select(['*'], self::TABLE, [], [], ['uid' => 'DESC'])
            ->fetchAllAssociative()
        ;
        $now = $this->clock->now()->getTimestamp();

        $finished = [];
        $open = [];
        $stalledJobs = 0;
        $oldestFinished = $now;
        foreach ($rows as $row) {
            $status = is_string($row['status']) ? $row['status'] : '';
            $stalled = $status !== 'done'
                && $this->intOf($row['progressed_at']) < $now - self::STALLED_AFTER_SECONDS;
            $state = new RemovalJobState(
                $this->jobOf($row),
                $status,
                $this->intOf($row['removed_roots']),
                $this->intOf($row['skipped_roots']),
                $this->intOf($row['removed_relations']),
                $this->intOf($row['removed_files']),
                $this->intOf($row['errors']),
                is_string($row['error_log']) ? $row['error_log'] : '',
                $stalled
            );
            if ($status === 'done') {
                $finished[] = $state;
                $oldestFinished = min($oldestFinished, $this->intOf($row['crdate']));
                continue;
            }

            $open[] = $state;
            $stalledJobs += (int)$stalled;
        }

        return new RemovalJobOverview(
            $finished,
            $open,
            $stalledJobs,
            $oldestFinished < $now - self::CLEANUP_AFTER_SECONDS
        );
    }

    public function markRunning(int $uid): void
    {
        $this->update($uid, ['status' => 'running']);
    }

    public function markDone(int $uid): void
    {
        $this->update($uid, ['status' => 'done']);
    }

    /**
     * Adds to the totals in the database, so the count stays right however the roots of a job are spread over
     * workers and redeliveries.
     */
    public function record(int $uid, RootRecord $root, RootOutcome $outcome): void
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE);
        $queryBuilder
            ->update(self::TABLE)
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)))
            ->set('progressed_at', $this->clock->now()->getTimestamp())
            ->set('removed_roots', 'removed_roots + ' . (int)$outcome->removed, false)
            ->set('skipped_roots', 'skipped_roots + ' . (int)$outcome->skipped, false)
            ->set('removed_relations', 'removed_relations + ' . $outcome->removedRelations, false)
            ->set('removed_files', 'removed_files + ' . $outcome->removedFiles, false)
            ->set('errors', 'errors + ' . count($outcome->errors), false)
        ;
        if ($outcome->errors !== []) {
            $lines = '';
            foreach ($outcome->errors as $error) {
                $lines .= $root->table . ':' . $root->uid . ' ' . $error . "\n";
            }

            $queryBuilder->set(
                'error_log',
                $queryBuilder->expr()->concat(
                    'COALESCE(error_log, ' . $queryBuilder->quote('') . ')',
                    $queryBuilder->createNamedParameter($lines)
                ),
                false
            );
        }

        $queryBuilder->executeStatement();
    }

    /**
     * @param array<string, string> $values
     */
    protected function update(int $uid, array $values): void
    {
        $this->connectionPool->getConnectionForTable(self::TABLE)->update(
            self::TABLE,
            [...$values, 'progressed_at' => $this->clock->now()->getTimestamp()],
            ['uid' => $uid]
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    protected function jobOf(array $row): RemovalJob
    {
        $scope = Scope::from(is_string($row['scope']) ? $row['scope'] : '');
        $scopePage = $this->intOf($row['scope_page']);
        $origin = $this->intOf($row['origin_configuration_uid']);

        return new RemovalJob(
            $this->intOf($row['uid']),
            $origin > 0
                ? Selection::ofOrigin($scopePage, $origin)
                : Selection::ofKind(is_string($row['root_table']) ? $row['root_table'] : '', $scope, $scopePage),
            (bool)$this->intOf($row['remove_files']),
            (bool)$this->intOf($row['update_reference_index']),
            $this->intOf($row['backend_user'])
        );
    }

    protected function intOf(mixed $value): int
    {
        return is_numeric($value) ? (int)$value : 0;
    }
}
