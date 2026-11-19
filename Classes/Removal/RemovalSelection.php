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
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\LanguageMarker;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use WerkraumMedia\ThueCat\Service\SitePageIds;

/**
 * Walks a selection one root at a time, so no list of roots ever exists.
 * Translations are never returned: they follow their default-language row.
 *
 * Public, so functional tests can fetch it from the container.
 */
#[Autoconfigure(public: true)]
class RemovalSelection
{
    public function __construct(
        protected readonly ConnectionPool $connectionPool,
        protected readonly TcaSchemaFactory $tcaSchemaFactory,
        protected readonly SitePageIds $sitePageIds,
    ) {
    }

    public function first(Selection $selection): ?RootRecord
    {
        return $this->firstFrom($selection, $selection->rootTables(), 0);
    }

    public function next(Selection $selection, RootRecord $after): ?RootRecord
    {
        $tables = $selection->rootTables();
        $position = array_search($after->table, $tables, true);
        if ($position === false) {
            return null;
        }

        return $this->firstFrom($selection, array_slice($tables, $position), $after->uid);
    }

    public function count(Selection $selection): int
    {
        $count = 0;
        foreach ($selection->rootTables() as $table) {
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
            $this->constrainToSelection($queryBuilder, $selection, $table);
            $tableCount = $queryBuilder->count('uid')->executeQuery()->fetchOne();
            $count += is_numeric($tableCount) ? (int)$tableCount : 0;
        }

        return $count;
    }

    /**
     * @param list<string> $tables the first table is searched above $afterUid, the others from the start
     */
    protected function firstFrom(Selection $selection, array $tables, int $afterUid): ?RootRecord
    {
        foreach ($tables as $table) {
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
            $this->constrainToSelection($queryBuilder, $selection, $table);
            $uid = $queryBuilder
                ->select('uid')
                ->andWhere($queryBuilder->expr()->gt(
                    'uid',
                    $queryBuilder->createNamedParameter($afterUid, Connection::PARAM_INT)
                ))
                ->orderBy('uid')
                ->setMaxResults(1)
                ->executeQuery()
                ->fetchOne()
            ;
            if (is_numeric($uid)) {
                return new RootRecord($table, (int)$uid);
            }

            $afterUid = 0;
        }

        return null;
    }

    /**
     * Hidden roots are selected too: removal is about where a record is stored, not whether it is visible.
     */
    protected function constrainToSelection(QueryBuilder $queryBuilder, Selection $selection, string $table): void
    {
        $languageField = $this->tcaSchemaFactory
            ->get($table)
            ->getCapability(TcaSchemaCapability::Language)
            ->getLanguageField()
            ->getName()
        ;

        $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());
        $queryBuilder->from($table)->where($queryBuilder->expr()->eq(
            $languageField,
            $queryBuilder->createNamedParameter(LanguageMarker::DEFAULT_LANGUAGE, Connection::PARAM_INT)
        ));

        if ($selection->scope === Scope::Folder) {
            $queryBuilder->andWhere($queryBuilder->expr()->eq(
                'pid',
                $queryBuilder->createNamedParameter($selection->scopePage, Connection::PARAM_INT)
            ));
        }

        if ($selection->scope === Scope::Site) {
            $queryBuilder->andWhere($queryBuilder->expr()->in(
                'pid',
                $queryBuilder->createNamedParameter(
                    $this->sitePageIds->forRootPage($selection->scopePage),
                    ArrayParameterType::INTEGER
                )
            ));
        }

        if ($selection->originConfigurationUid > 0) {
            $queryBuilder->andWhere($queryBuilder->expr()->eq(
                'origin_configuration_uid',
                $queryBuilder->createNamedParameter($selection->originConfigurationUid, Connection::PARAM_INT)
            ));
        }
    }
}
