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

namespace WerkraumMedia\ThueCat\Tests\Functional;

use Codappix\Typo3PhpDatasets\TestingFramework;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * The environment every thuecat functional test shares: the extensions
 * thuecat needs to boot, and read helpers for any table.
 *
 * The lists are published as constants. A class needing more redeclares the
 * property and spreads the constant of the base it extends, e.g.
 * `[...AbstractFunctionalTestCase::CORE_EXTENSIONS, 'seo']`.
 */
abstract class AbstractFunctionalTestCase extends FunctionalTestCase
{
    use TestingFramework;

    /** thuecat's composer requirements beyond the framework defaults; the framework refuses to boot without them. */
    public const CORE_EXTENSIONS = [
        'filelist',
        'filemetadata',
        'install',
        'seo',
    ];

    /** TYPO3 does not enforce thuecat's dependency on events, so it is listed explicitly. */
    public const TEST_EXTENSIONS = [
        'werkraummedia/thuecat',
        'werkraummedia/events',
    ];

    protected array $coreExtensionsToLoad = self::CORE_EXTENSIONS;

    protected array $testExtensionsToLoad = self::TEST_EXTENSIONS;

    protected function countRows(string $table): int
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());

        $count = $queryBuilder
            ->count('uid')
            ->from($table)
            ->executeQuery()
            ->fetchOne()
        ;

        return is_numeric($count) ? (int)$count : 0;
    }

    protected function fetchUidByRemoteId(string $table, string $remoteId): int
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());

        $value = $queryBuilder
            ->select('uid')
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq(
                    'remote_id',
                    $queryBuilder->createNamedParameter($remoteId)
                )
            )
            ->executeQuery()
            ->fetchOne()
        ;

        return is_numeric($value) ? (int)$value : 0;
    }

    /** @return array<string, mixed> */
    protected function fetchRowByRemoteId(string $table, string $remoteId): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());

        $row = $queryBuilder
            ->select('*')
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq(
                    'remote_id',
                    $queryBuilder->createNamedParameter($remoteId)
                )
            )
            ->executeQuery()
            ->fetchAssociative()
        ;

        self::assertIsArray($row, 'No record in ' . $table . ' for ' . $remoteId);

        return $row;
    }

    /**
     * Translations of one default-language row, keyed by language. Languages without a row are absent.
     * Field names come from the schema, since the parent pointer differs per table.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function fetchTranslationsByParent(string $table, int $parentUid): array
    {
        $capability = $this->get(TcaSchemaFactory::class)
            ->get($table)
            ->getCapability(TcaSchemaCapability::Language)
        ;
        $parentField = $capability->getTranslationOriginPointerField()->getName();
        $languageField = $capability->getLanguageField()->getName();

        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());

        $rows = $queryBuilder
            ->select('*')
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq(
                    $parentField,
                    $queryBuilder->createNamedParameter($parentUid, Connection::PARAM_INT)
                )
            )
            ->executeQuery()
            ->fetchAllAssociative()
        ;

        $byLanguage = [];
        foreach ($rows as $row) {
            $language = $row[$languageField] ?? null;
            if (!is_numeric($language)) {
                continue;
            }
            $byLanguage[(int)$language] = $row;
        }

        return $byLanguage;
    }

    /**
     * The foreign uids of one MM relation field, in stored order. The MM table comes from the field's TCA.
     *
     * @return list<int>
     */
    protected function fetchMmRelatedUids(string $table, int $uid, string $field): array
    {
        $mmTable = $this->get(TcaSchemaFactory::class)
            ->get($table)
            ->getField($field)
            ->getConfiguration()['MM'] ?? null
        ;
        self::assertIsString($mmTable, $table . '.' . $field . ' is not an MM relation.');

        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable($mmTable);
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

        return array_map(static fn (mixed $uid): int => is_numeric($uid) ? (int)$uid : 0, $uids);
    }

    protected function countDeletedRows(string $table): int
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable($table);
        // intentional, we count deleted rows here
        $queryBuilder->getRestrictions()->removeAll();

        $count = $queryBuilder
            ->count('uid')
            ->from($table)
            ->where($queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(1, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchOne()
        ;

        return is_numeric($count) ? (int)$count : 0;
    }

    /** @return list<array<string, mixed>> */
    protected function fetchChildren(string $table): array
    {
        $languageField = $this->get(TcaSchemaFactory::class)
            ->get($table)
            ->getCapability(TcaSchemaCapability::Language)
            ->getLanguageField()
            ->getName()
        ;

        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());

        return $queryBuilder
            ->select('*')
            ->from($table)
            ->where($queryBuilder->expr()->eq(
                $languageField,
                $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
            ))
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative()
        ;
    }

    /**
     * Rows in all languages, not only the default.
     *
     * @return list<array<string, mixed>>
     */
    protected function fetchAllChildren(string $table): array
    {
        $languageField = $this->get(TcaSchemaFactory::class)
            ->get($table)
            ->getCapability(TcaSchemaCapability::Language)
            ->getLanguageField()
            ->getName()
        ;

        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());

        return $queryBuilder
            ->select('*')
            ->from($table)
            ->orderBy($languageField)
            ->addOrderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative()
        ;
    }
}
