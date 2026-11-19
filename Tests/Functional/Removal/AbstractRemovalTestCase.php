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

use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use TYPO3\CMS\Core\Database\Connection;
use WerkraumMedia\ThueCat\Removal\Handler\RemoveRootHandler;
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
    protected function queueJob(Selection $selection, int $backendUser = 1, bool $removeFiles = false): int
    {
        return $this->get(RemovalJobs::class)->queue($selection, $removeFiles, false, $backendUser);
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
     * @return list<object> in the order they were queued
     */
    protected function queuedMessages(): array
    {
        $rows = $this->getConnectionPool()->getConnectionForTable('sys_messenger_messages')
            ->select(['body'], 'sys_messenger_messages', ['queue_name' => 'default'], [], ['id' => 'ASC'])
            ->fetchAllAssociative()
        ;

        $messages = [];
        foreach ($rows as $row) {
            $body = $row['body'];
            $this->assertIsString($body);
            $messages[] = new PhpSerializer()->decode(['body' => $body])->getMessage();
        }

        return $messages;
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
