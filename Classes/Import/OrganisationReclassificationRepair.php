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

namespace WerkraumMedia\ThueCat\Import;

use RuntimeException;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Replaces the organisations of the site whose node a run wrote to another table: their contained-in
 * relations move to the new row, relations only an organisation can hold are dropped, and the organisation
 * is deleted.
 *
 * Driven by remote_id rather than by the recorded table mismatches: a mismatch is recorded only when a
 * reference found the organisation first, so a node imported as a root would never be repaired.
 *
 * @todo Remove (fix-hotel-table-swap), with ImportLogger::recordOrganisationRepair() and
 *       RecordReclassification::KIND_REPAIRED.
 */
class OrganisationReclassificationRepair
{
    protected const TABLE = 'tx_thuecat_organisation';

    protected const CONTAINED_IN_OWNER_TABLES = [
        'tx_thuecat_tourist_attraction',
        'tx_thuecat_tourist_information',
        'tx_thuecat_parking_facility',
    ];

    protected const MANAGED_BY_OWNER_TABLES = [
        'tx_thuecat_tourist_attraction',
        'tx_thuecat_tourist_information',
        'tx_thuecat_parking_facility',
        'tx_thuecat_trail',
        'tx_thuecat_town',
    ];

    // Keeps the IN list well below every platform's placeholder limit.
    protected const CHUNK_SIZE = 500;

    public function __construct(
        protected readonly ConnectionPool $connectionPool,
        protected readonly TcaSchemaFactory $tcaSchemaFactory,
        protected readonly ImportLogger $importLogger,
    ) {
    }

    // After the run's write, so the written rows carry uids.
    public function repair(ResolverContext $context, int $iteration): void
    {
        // Without the site's pages the scope is unknown; deleting unscoped
        // would reach into other sites.
        if ($context->sitePageIds === []) {
            return;
        }

        $containedInFields = Resolver::BUCKET_MAP['containedInPlace'];
        $written = [];
        foreach ($context->remoteIdToTable as $remoteId => $table) {
            $uid = $context->remoteIdToKey[$remoteId] ?? '';
            if ($table === self::TABLE || !isset($containedInFields[$table]) || !ctype_digit($uid)) {
                continue;
            }
            $written[$remoteId] = ['table' => $table, 'uid' => (int)$uid];
        }

        $organisations = $this->findOrganisations(array_keys($written), $context->sitePageIds);
        if ($organisations === []) {
            return;
        }

        $data = [];
        $cmd = [];
        $repairs = [];
        foreach ($organisations as $organisationUid => $remoteId) {
            $target = $written[$remoteId];
            $moved = $this->moveContainedIn(
                $data,
                $organisationUid,
                $containedInFields[$target['table']],
                $target['uid'],
                $context->sitePageIds
            );
            $droppedManagedBy = $this->clearManagedBy($data, $organisationUid, $context->sitePageIds);
            $repairs[] = [$remoteId, $organisationUid, $target, $moved, $droppedManagedBy, $this->findManagedEvents($organisationUid)];

            // Translations and the inline address follow the organisation.
            $cmd[self::TABLE][$organisationUid]['delete'] = 1;
            // The repair entry reports it.
            unset($context->tableMismatches[$remoteId]);
        }

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start($data, $cmd);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();
        /** @var list<string> $errorLog */
        $errorLog = $dataHandler->errorLog;
        $this->importLogger->recordDataHandlerErrors($errorLog, $iteration);

        foreach ($repairs as [$remoteId, $organisationUid, $target, $moved, $droppedManagedBy, $droppedManagedEvents]) {
            $this->importLogger->recordOrganisationRepair(
                $remoteId,
                $organisationUid,
                $target['table'],
                $target['uid'],
                $moved,
                $droppedManagedBy,
                $droppedManagedEvents
            );
        }
    }

    /**
     * Only default-language rows are staged: the contained-in fields are
     * l10n_mode exclude, so DataHandler carries the value to the translations.
     *
     * @param array<string, array<int, array<string, string>>> $data
     * @param list<int> $sitePageIds
     *
     * @return list<string> the moved relations as table:uid of the owner
     */
    protected function moveContainedIn(
        array &$data,
        int $organisationUid,
        string $targetField,
        int $targetUid,
        array $sitePageIds
    ): array {
        $moved = [];
        foreach (self::CONTAINED_IN_OWNER_TABLES as $table) {
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
            $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());
            $rows = $queryBuilder
                ->select('uid', 'contained_in_organisation', $targetField)
                ->from($table)
                ->where(
                    $queryBuilder->expr()->inSet('contained_in_organisation', (string)$organisationUid),
                    $queryBuilder->expr()->in(
                        'pid',
                        $queryBuilder->createNamedParameter($sitePageIds, Connection::PARAM_INT_ARRAY)
                    ),
                    $queryBuilder->expr()->eq(
                        'sys_language_uid',
                        $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                    )
                )
                ->orderBy('uid')
                ->executeQuery()
                ->fetchAllAssociative()
            ;

            foreach ($rows as $row) {
                if (!is_numeric($row['uid'] ?? null)) {
                    continue;
                }
                $uid = (int)$row['uid'];
                $data[$table][$uid]['contained_in_organisation'] = implode(',', array_diff(
                    $this->uidList($row['contained_in_organisation']),
                    [(string)$organisationUid]
                ));
                $data[$table][$uid][$targetField] = implode(',', array_unique([
                    ...$this->uidList($row[$targetField]),
                    (string)$targetUid,
                ]));
                $moved[] = $table . ':' . $uid;
            }
        }

        return $moved;
    }

    /**
     * @param array<string, array<int, array<string, string>>> $data
     * @param list<int> $sitePageIds
     *
     * @return list<string> the dropped relations as table:uid of the managed record
     */
    protected function clearManagedBy(array &$data, int $organisationUid, array $sitePageIds): array
    {
        $dropped = [];
        foreach (self::MANAGED_BY_OWNER_TABLES as $table) {
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
            $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());
            $uids = $queryBuilder
                ->select('uid')
                ->from($table)
                ->where(
                    $queryBuilder->expr()->eq(
                        'managed_by',
                        $queryBuilder->createNamedParameter($organisationUid, Connection::PARAM_INT)
                    ),
                    $queryBuilder->expr()->in(
                        'pid',
                        $queryBuilder->createNamedParameter($sitePageIds, Connection::PARAM_INT_ARRAY)
                    ),
                    $queryBuilder->expr()->eq(
                        'sys_language_uid',
                        $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                    )
                )
                ->orderBy('uid')
                ->executeQuery()
                ->fetchFirstColumn()
            ;

            foreach ($uids as $uid) {
                if (!is_numeric($uid)) {
                    continue;
                }
                $data[$table][(int)$uid]['managed_by'] = '0';
                $dropped[] = $table . ':' . (int)$uid;
            }
        }

        return $dropped;
    }

    /**
     * @return list<int>
     */
    protected function findManagedEvents(int $organisationUid): array
    {
        $mmTable = $this->tcaSchemaFactory->get(self::TABLE)->getField('manages_event')->getConfiguration()['MM'] ?? '';
        if (!is_string($mmTable) || $mmTable === '') {
            throw new RuntimeException(self::TABLE . '.manages_event is not an MM relation.', 1793360862);
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($mmTable);
        $queryBuilder->getRestrictions()->removeAll();
        $uids = $queryBuilder
            ->select('uid_foreign')
            ->from($mmTable)
            ->where($queryBuilder->expr()->eq(
                'uid_local',
                $queryBuilder->createNamedParameter($organisationUid, Connection::PARAM_INT)
            ))
            ->orderBy('sorting')
            ->executeQuery()
            ->fetchFirstColumn()
        ;

        return array_values(array_map(intval(...), array_filter($uids, is_numeric(...))));
    }

    /**
     * @param list<string> $remoteIds
     * @param list<int> $sitePageIds
     *
     * @return array<int, string> organisation uid => remote_id
     */
    protected function findOrganisations(array $remoteIds, array $sitePageIds): array
    {
        $organisations = [];
        foreach (array_chunk($remoteIds, self::CHUNK_SIZE) as $chunk) {
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE);
            $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());
            $rows = $queryBuilder
                ->select('uid', 'remote_id')
                ->from(self::TABLE)
                ->where(
                    $queryBuilder->expr()->in(
                        'remote_id',
                        $queryBuilder->createNamedParameter($chunk, Connection::PARAM_STR_ARRAY)
                    ),
                    $queryBuilder->expr()->in(
                        'pid',
                        $queryBuilder->createNamedParameter($sitePageIds, Connection::PARAM_INT_ARRAY)
                    ),
                    $queryBuilder->expr()->eq(
                        'sys_language_uid',
                        $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                    )
                )
                ->orderBy('uid')
                ->executeQuery()
                ->fetchAllAssociative()
            ;

            foreach ($rows as $row) {
                if (is_numeric($row['uid'] ?? null) && is_string($row['remote_id'] ?? null)) {
                    $organisations[(int)$row['uid']] = $row['remote_id'];
                }
            }
        }

        return $organisations;
    }

    /**
     * @return list<string>
     */
    protected function uidList(mixed $value): array
    {
        if (!is_scalar($value) || (string)$value === '' || (string)$value === '0') {
            return [];
        }

        return array_values(array_filter(explode(',', (string)$value), static fn (string $uid): bool => $uid !== ''));
    }
}
