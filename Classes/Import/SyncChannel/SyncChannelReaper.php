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

namespace WerkraumMedia\ThueCat\Import\SyncChannel;

use Doctrine\DBAL\ArrayParameterType;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WerkraumMedia\ThueCat\Import\RecordTables;

/**
 * Deletes the site's records a channel lists as removed or expired. Identity is
 * site-wide, so whichever configuration or folder of the site holds the record,
 * it goes, from every record table that holds it.
 *
 * Public, so functional tests can put a failing deletion in its place: an
 * admin, as the import runs, is never refused one.
 */
#[Autoconfigure(public: true)]
class SyncChannelReaper
{
    public function __construct(
        protected readonly ConnectionPool $connectionPool,
        protected readonly TcaSchemaFactory $tcaSchemaFactory,
    ) {
    }

    /**
     * @param list<string> $remoteIds
     * @param list<int> $sitePageIds
     *
     * @return list<string>
     */
    public function storedRemoteIds(array $remoteIds, array $sitePageIds): array
    {
        // The site's own remote IDs number in the thousands, a channel's removals in a five-year window in the tens of
        // thousands: one query per table beats an IN list per chunk on a column without index.
        $stored = [];
        foreach (RecordTables::STANDALONE as $table) {
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
            $queryBuilder->select('remote_id')->from($table);
            $this->constrainToSite($queryBuilder, $table, $sitePageIds);
            foreach ($queryBuilder->executeQuery()->fetchFirstColumn() as $remoteId) {
                if (is_string($remoteId)) {
                    $stored[$remoteId] = $remoteId;
                }
            }
        }

        return array_values(array_filter($remoteIds, static fn (string $remoteId): bool => isset($stored[$remoteId])));
    }

    /**
     * One remote ID per call, so each removal is a DataHandler run of its own.
     *
     * @param list<int> $sitePageIds
     *
     * @return list<string> DataHandler's error log of the delete call.
     */
    public function delete(string $remoteId, array $sitePageIds): array
    {
        // Without the site's pages the scope is unknown; deleting unscoped
        // would reach into other sites.
        if ($sitePageIds === []) {
            return [];
        }

        $cmd = [];
        foreach (RecordTables::STANDALONE as $table) {
            foreach ($this->findUids($table, $remoteId, $sitePageIds) as $uid) {
                $cmd[$table][$uid]['delete'] = 1;
            }
        }

        if ($cmd === []) {
            return [];
        }

        // Translations, inline children, dates and file references follow the record.
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], $cmd);
        $dataHandler->process_cmdmap();

        /** @var list<string> $errorLog */
        $errorLog = $dataHandler->errorLog;

        return $errorLog;
    }

    /**
     * @param list<int> $sitePageIds
     *
     * @return list<int>
     */
    protected function findUids(string $table, string $remoteId, array $sitePageIds): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
        $queryBuilder->select('uid')->from($table);
        $this->constrainToSite($queryBuilder, $table, $sitePageIds);
        $queryBuilder->andWhere($queryBuilder->expr()->eq('remote_id', $queryBuilder->createNamedParameter($remoteId)));

        $uids = $queryBuilder->executeQuery()->fetchFirstColumn();

        return array_values(array_map(intval(...), array_filter($uids, is_numeric(...))));
    }

    /**
     * What the site stores, as both the start check and the deletion read it: default-language records on the site's
     * pages, deleted ones excluded.
     *
     * @param list<int> $sitePageIds
     */
    protected function constrainToSite(QueryBuilder $queryBuilder, string $table, array $sitePageIds): void
    {
        $languageField = $this->tcaSchemaFactory
            ->get($table)
            ->getCapability(TcaSchemaCapability::Language)
            ->getLanguageField()
            ->getName()
        ;

        $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());
        $queryBuilder->andWhere(
            $queryBuilder->expr()->in('pid', $queryBuilder->createNamedParameter($sitePageIds, ArrayParameterType::INTEGER)),
            $queryBuilder->expr()->eq($languageField, $queryBuilder->createNamedParameter(0, Connection::PARAM_INT))
        );
    }
}
