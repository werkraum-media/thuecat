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

namespace WerkraumMedia\ThueCat\Typo3\Upgrades;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Attribute\UpgradeWizard;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Upgrades\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Core\Upgrades\RepeatableInterface;
use TYPO3\CMS\Core\Upgrades\UpgradeWizardInterface;
use WerkraumMedia\ThueCat\Import\EventPlaceMatcher;

/**
 * Moves the event relations of places from comma-separated uid lists into
 * their MM tables, keeping the stored order, and leaves the relation count in
 * the field.
 *
 * Every row is migrated, translations and deleted rows included. A list of
 * one uid reads like a count, so a row counts as migrated once it has MM rows.
 * Uids already present in MM are skipped, which makes an interrupted run safe
 * to repeat.
 */
#[UpgradeWizard('thuecat_eventRelationsToMm')]
#[Autoconfigure(public: true)]
final class EventRelationsToMmUpgradeWizard implements UpgradeWizardInterface, RepeatableInterface
{
    private const FIELDS = [
        ['tx_thuecat_tourist_attraction', EventPlaceMatcher::FIELD_HOSTS],
        ['tx_thuecat_organisation', EventPlaceMatcher::FIELD_HOSTS],
        ['tx_thuecat_organisation', EventPlaceMatcher::FIELD_MANAGES],
        ['tx_thuecat_town', EventPlaceMatcher::FIELD_HOSTS],
        ['tx_thuecat_tourist_information', EventPlaceMatcher::FIELD_HOSTS],
    ];

    public function __construct(
        private readonly TcaSchemaFactory $tcaSchemaFactory,
        private readonly ConnectionPool $connectionPool,
    ) {
    }

    public function getTitle(): string
    {
        return 'ThueCat: Move event relations of places into MM tables';
    }

    public function getDescription(): string
    {
        return 'Moves the events stored as comma-separated lists on tourist attractions, organisations, towns and tourist information into their MM tables, keeping their order.';
    }

    public function executeUpdate(): bool
    {
        foreach (self::FIELDS as [$table, $field]) {
            $mmTable = $this->mmTableOf($table, $field);
            $mmConnection = $this->connectionPool->getConnectionForTable($mmTable);

            foreach ($this->rowsToMigrate($table, $field, $mmTable) as [$uid, $stored, $existing]) {
                $related = $existing;
                foreach ($stored as $eventUid) {
                    if (in_array($eventUid, $related, true)) {
                        continue;
                    }
                    $related[] = $eventUid;
                    $mmConnection->insert($mmTable, [
                        'uid_local' => $uid,
                        'uid_foreign' => $eventUid,
                        'sorting' => count($related),
                        'sorting_foreign' => 0,
                    ]);
                }

                $this->connectionPool->getConnectionForTable($table)->update(
                    $table,
                    [$field => (string)count($related)],
                    ['uid' => $uid]
                );
            }
        }

        return true;
    }

    public function updateNecessary(): bool
    {
        foreach (self::FIELDS as [$table, $field]) {
            if ($this->rowsToMigrate($table, $field, $this->mmTableOf($table, $field)) !== []) {
                return true;
            }
        }

        return false;
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    private function mmTableOf(string $table, string $field): string
    {
        $mmTable = $this->tcaSchemaFactory->get($table)->getField($field)->getConfiguration()['MM'] ?? '';

        return is_string($mmTable) ? $mmTable : '';
    }

    /**
     * @return list<array{0: int, 1: list<int>, 2: list<int>}> uid, the uids the field lists, the uids already in MM
     */
    private function rowsToMigrate(string $table, string $field, string $mmTable): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll();

        $rows = $queryBuilder
            ->select('uid', $field)
            ->from($table)
            ->where(
                $queryBuilder->expr()->isNotNull($field),
                $queryBuilder->expr()->notIn($field, $queryBuilder->createNamedParameter(['', '0'], Connection::PARAM_STR_ARRAY))
            )
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative()
        ;

        $toMigrate = [];
        foreach ($rows as $row) {
            $uid = is_numeric($row['uid'] ?? null) ? (int)$row['uid'] : 0;
            $value = is_scalar($row[$field] ?? null) ? (string)$row[$field] : '';
            $existing = $this->relatedInMm($mmTable, $uid);
            if (!str_contains($value, ',') && $existing !== []) {
                continue;
            }

            $stored = array_values(array_filter(
                array_map(intval(...), explode(',', $value)),
                static fn (int $eventUid): bool => $eventUid > 0
            ));
            $toMigrate[] = [$uid, $stored, $existing];
        }

        return $toMigrate;
    }

    /**
     * @return list<int>
     */
    private function relatedInMm(string $mmTable, int $uid): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($mmTable);
        $queryBuilder->getRestrictions()->removeAll();

        $uids = $queryBuilder
            ->select('uid_foreign')
            ->from($mmTable)
            ->where($queryBuilder->expr()->eq(
                'uid_local',
                $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)
            ))
            ->orderBy('sorting')
            ->executeQuery()
            ->fetchFirstColumn()
        ;

        return array_map(static fn (mixed $eventUid): int => is_numeric($eventUid) ? (int)$eventUid : 0, $uids);
    }
}
