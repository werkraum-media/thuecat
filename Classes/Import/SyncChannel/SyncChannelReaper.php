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

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Deletes the site's events a channel lists as removed or expired. Identity is
 * site-wide, so whichever configuration or folder of the site holds the event,
 * it goes.
 *
 * Public, so functional tests can put a failing deletion in its place: an
 * admin, as the import runs, is never refused one.
 */
#[Autoconfigure(public: true)]
class SyncChannelReaper
{
    protected const TABLE = 'tx_events_domain_model_event';

    public function __construct(
        protected readonly ConnectionPool $connectionPool,
        protected readonly TcaSchemaFactory $tcaSchemaFactory,
    ) {
    }

    /**
     * @param list<string> $remoteIds
     * @param list<int> $sitePageIds
     *
     * @return list<string> DataHandler's error log of the delete call.
     */
    public function delete(array $remoteIds, array $sitePageIds): array
    {
        // Without the site's pages the scope is unknown; deleting unscoped
        // would reach into other sites.
        if ($remoteIds === [] || $sitePageIds === []) {
            return [];
        }

        $cmd = [];
        foreach ($this->findUids($remoteIds, $sitePageIds) as $uid) {
            $cmd[self::TABLE][$uid]['delete'] = 1;
        }
        if ($cmd === []) {
            return [];
        }

        // Translations, dates and file references follow the event.
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], $cmd);
        $dataHandler->process_cmdmap();

        /** @var list<string> $errorLog */
        $errorLog = $dataHandler->errorLog;

        return $errorLog;
    }

    /**
     * @param list<string> $remoteIds
     * @param list<int> $sitePageIds
     *
     * @return list<int>
     */
    protected function findUids(array $remoteIds, array $sitePageIds): array
    {
        $languageField = $this->tcaSchemaFactory
            ->get(self::TABLE)
            ->getCapability(TcaSchemaCapability::Language)
            ->getLanguageField()
            ->getName()
        ;

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE);
        $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());

        $uids = $queryBuilder
            ->select('uid')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->in(
                    'remote_id',
                    $queryBuilder->createNamedParameter($remoteIds, Connection::PARAM_STR_ARRAY)
                ),
                $queryBuilder->expr()->in(
                    'pid',
                    $queryBuilder->createNamedParameter($sitePageIds, Connection::PARAM_INT_ARRAY)
                ),
                $queryBuilder->expr()->eq(
                    $languageField,
                    $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                )
            )
            ->executeQuery()
            ->fetchFirstColumn()
        ;

        return array_values(array_map(intval(...), array_filter($uids, is_numeric(...))));
    }
}
