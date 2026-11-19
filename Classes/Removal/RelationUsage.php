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

use Doctrine\DBAL\ArrayParameterType;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;

/**
 * Reads from the reference index which relations a record uses, and whether anybody still uses a relation.
 */
class RelationUsage
{
    public function __construct(
        protected readonly ConnectionPool $connectionPool,
        protected readonly ReferenceIndex $referenceIndex,
        protected readonly TcaSchemaFactory $tcaSchemaFactory,
    ) {
    }

    /**
     * Read before the record is deleted: DataHandler drops its index rows with it.
     *
     * @return list<RelationRecord>
     */
    public function candidatesOf(string $table, int $uid): array
    {
        $this->referenceIndex->updateRefIndexTable($table, $uid);

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_refindex');
        /** @var list<array{ref_table: string, ref_uid: int|string}> $rows */
        $rows = $queryBuilder
            ->select('ref_table', 'ref_uid')
            ->distinct()
            ->from('sys_refindex')
            ->where(
                $queryBuilder->expr()->eq('tablename', $queryBuilder->createNamedParameter($table)),
                $queryBuilder->expr()->eq('recuid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)),
                $queryBuilder->expr()->in(
                    'ref_table',
                    $queryBuilder->createNamedParameter(RelationTables::ALL, ArrayParameterType::STRING)
                )
            )
            ->executeQuery()
            ->fetchAllAssociative()
        ;

        $candidates = [];
        foreach ($rows as $row) {
            $candidates[] = new RelationRecord($row['ref_table'], (int)$row['ref_uid']);
        }

        return $candidates;
    }

    /**
     * The relation's own translations point at it, but do not use it.
     */
    public function isUnused(RelationRecord $relation): bool
    {
        $translationOrigin = $this->tcaSchemaFactory->get($relation->table)
            ->getCapability(TcaSchemaCapability::Language)
            ->getTranslationOriginPointerField()
            ->getName()
        ;

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_refindex');
        $users = $queryBuilder
            ->count('hash')
            ->from('sys_refindex')
            ->where(
                $queryBuilder->expr()->eq('ref_table', $queryBuilder->createNamedParameter($relation->table)),
                $queryBuilder->expr()->eq('ref_uid', $queryBuilder->createNamedParameter($relation->uid, Connection::PARAM_INT)),
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->neq('tablename', $queryBuilder->createNamedParameter($relation->table)),
                    $queryBuilder->expr()->neq('field', $queryBuilder->createNamedParameter($translationOrigin))
                )
            )
            ->executeQuery()
            ->fetchOne()
        ;

        return is_numeric($users) && (int)$users === 0;
    }
}
