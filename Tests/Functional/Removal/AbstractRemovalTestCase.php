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

namespace WerkraumMedia\ThueCat\Tests\Functional\Removal;

use Psr\EventDispatcher\EventDispatcherInterface;
use RuntimeException;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Log\LogDataTrait;
use WerkraumMedia\ThueCat\Removal\Handler\RemovalStartedHandler;
use WerkraumMedia\ThueCat\Removal\Handler\RemoveRootHandler;
use WerkraumMedia\ThueCat\Removal\Message\RemovalStarted;
use WerkraumMedia\ThueCat\Removal\Message\RemoveRoot;
use WerkraumMedia\ThueCat\Removal\RemovalJobs;
use WerkraumMedia\ThueCat\Removal\Scope;
use WerkraumMedia\ThueCat\Removal\Selection;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractBackendTestCase;

/**
 * Reads what the removal leaves behind: the queued messages and the job record.
 */
abstract class AbstractRemovalTestCase extends AbstractBackendTestCase
{
    use LogDataTrait;

    protected function queueJob(
        Selection $selection,
        int $backendUser = 1,
        bool $removeFiles = false,
        bool $updateReferenceIndex = false,
    ): int {
        return $this->get(RemovalJobs::class)->queue($selection, $removeFiles, $updateReferenceIndex, $backendUser);
    }

    /**
     * A job removing the trails of the instance, at the given state.
     */
    protected function insertJob(string $status, int $createdAt, int $progressedAt): int
    {
        $connection = $this->getConnectionPool()->getConnectionForTable('tx_thuecat_removal_job');
        $connection->insert('tx_thuecat_removal_job', [
            'crdate' => $createdAt,
            'progressed_at' => $progressedAt,
            'backend_user' => 1,
            'status' => $status,
            'root_table' => 'tx_thuecat_trail',
            'scope' => 'instance',
        ]);

        return (int)$connection->lastInsertId();
    }

    /**
     * Queues a job and handles its first message.
     *
     * @return int the job's uid
     */
    protected function startJob(Selection $selection, bool $updateReferenceIndex = false): int
    {
        $job = $this->queueJob($selection, 1, false, $updateReferenceIndex);
        $this->get(RemovalStartedHandler::class)(new RemovalStarted($job));

        return $job;
    }

    /**
     * Handles the root's message of a job removing its kind from folder 10.
     *
     * @return int the job's uid
     */
    protected function removeRoot(string $table, int $uid, bool $removeFiles = false, int $backendUser = 1): int
    {
        $job = $this->queueJob(Selection::ofKind($table, Scope::Folder, 10), $backendUser, $removeFiles);
        $this->get(RemoveRootHandler::class)(new RemoveRoot($job, $table, $uid));

        return $job;
    }

    /**
     * Fails the message the way the worker does: stored and being handled when the failure is announced, rejected
     * afterwards.
     */
    protected function failMessage(object $message): void
    {
        $this->get(MessageBusInterface::class)->dispatch($message);
        $connection = $this->getConnectionPool()->getConnectionForTable('sys_messenger_messages');
        $inFlight = $connection->select(['id'], 'sys_messenger_messages', [], [], ['id' => 'DESC'], 1)->fetchOne();
        $connection->update('sys_messenger_messages', ['delivered_at' => date('Y-m-d H:i:s')], ['id' => $inFlight]);

        $this->announceFailure($message);

        $connection->delete('sys_messenger_messages', ['id' => $inFlight]);
    }

    protected function announceFailure(object $message): void
    {
        $envelope = new Envelope($message);
        $this->get(EventDispatcherInterface::class)->dispatch(new WorkerMessageFailedEvent(
            $envelope,
            'doctrine',
            new HandlerFailedException($envelope, [new RuntimeException('Database gone [test]', 1795092847)])
        ));
    }

    /**
     * @return array<string, mixed>
     */
    protected function jobRow(int $uid): array
    {
        $row = $this->getConnectionPool()->getConnectionForTable('tx_thuecat_removal_job')
            ->select(['*'], 'tx_thuecat_removal_job', ['uid' => $uid])
            ->fetchAssociative()
        ;
        $this->assertIsArray($row);

        return $row;
    }

    /**
     * @return list<array{type: int, error: int, details: string}> the user's error entries, details formatted
     */
    protected function systemLogErrors(int $userId): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('sys_log');
        $rows = $queryBuilder
            ->select('type', 'error', 'details', 'log_data')
            ->from('sys_log')
            ->where(
                $queryBuilder->expr()->eq('userid', $queryBuilder->createNamedParameter($userId, Connection::PARAM_INT)),
                $queryBuilder->expr()->neq('error', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT))
            )
            ->executeQuery()
            ->fetchAllAssociative()
        ;

        $errors = [];
        foreach ($rows as $row) {
            $this->assertIsInt($row['type']);
            $this->assertIsInt($row['error']);
            $this->assertIsString($row['details']);
            $errors[] = [
                'type' => $row['type'],
                'error' => $row['error'],
                'details' => $this->formatLogDetails($row['details'], $row['log_data']),
            ];
        }

        return $errors;
    }

    protected function referenceExists(string $table, int $uid, string $refTable, int $refUid): bool
    {
        return $this->getConnectionPool()->getConnectionForTable('sys_refindex')->count('*', 'sys_refindex', [
            'tablename' => $table,
            'recuid' => $uid,
            'ref_table' => $refTable,
            'ref_uid' => $refUid,
        ]) > 0;
    }

    /**
     * Soft-deleted, as DataHandler deletes; a row that is gone altogether fails the assertion.
     */
    protected function isDeleted(string $table, int $uid): bool
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable($table);
        // intentional, the deleted flag is what is read here
        $queryBuilder->getRestrictions()->removeAll();
        $deleted = $queryBuilder
            ->select('deleted')
            ->from($table)
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchOne()
        ;
        $this->assertNotFalse($deleted, $table . ':' . $uid . ' does not exist.');

        return (bool)$deleted;
    }
}
