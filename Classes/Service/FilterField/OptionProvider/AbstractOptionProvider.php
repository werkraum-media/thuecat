<?php

declare(strict_types=1);

/*
 * Copyright (C) 2026 werkraum-media
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 */

namespace WerkraumMedia\ThueCat\Service\FilterField\OptionProvider;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Dto\FilterOption;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Dto\FilterScope;

/**
 * What every option query shares: the visibility rules a frontend request
 * implies, the storage scope, and the mapping of rows to options.
 */
abstract class AbstractOptionProvider
{
    public function __construct(
        protected readonly ConnectionPool $connectionPool,
        protected readonly TcaSchemaFactory $tcaSchemaFactory,
    ) {
    }

    protected function queryBuilderFor(string $table): QueryBuilder
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(new FrontendRestrictionContainer())
        ;

        return $queryBuilder;
    }

    /** Database rows arrive untyped, so every read of one narrows here. */
    protected function intValue(mixed $value): int
    {
        return is_numeric($value) ? (int)$value : 0;
    }

    /**
     * Deleted and disabled as raw SQL, for a recursive query the restriction
     * container cannot reach into. Unprefixed, so it suits both the anchor term
     * and an aliased recursive term.
     *
     * @param int[] $sitePageIds
     */
    protected function visibilitySql(string $table, string $alias = '', array $sitePageIds = []): string
    {
        $prefix = $alias === '' ? '' : $alias . '.';
        $conditions = [$prefix . 'deleted = 0'];

        // Interpolated: these are page uids the request resolved, and a bound
        // parameter does not resolve inside a recursive term.
        if ($sitePageIds !== []) {
            $conditions[] = $prefix . 'pid IN (' . implode(',', array_map(
                fn (mixed $pageId): int => $this->intValue($pageId),
                $sitePageIds
            )) . ')';
        }

        if ($this->tcaSchemaFactory->has($table)) {
            $schema = $this->tcaSchemaFactory->get($table);
            if ($schema->hasCapability(TcaSchemaCapability::RestrictionDisabledField)) {
                $conditions[] = $prefix
                    . $schema->getCapability(TcaSchemaCapability::RestrictionDisabledField)->getFieldName()
                    . ' = 0';
            }
        }

        return implode(' AND ', $conditions);
    }

    /**
     * The record table: language, plus the storage pages the list is bound to.
     *
     * Records are what a plugin's storage configuration selects, so this is the
     * only place the storage pages belong.
     */
    protected function applyRecordScope(QueryBuilder $queryBuilder, string $table, FilterScope $scope): void
    {
        foreach ([
            $this->languageConstraint($queryBuilder, $table),
            $this->storageConstraint($queryBuilder, $table, $scope->getStoragePageIds()),
        ] as $constraint) {
            if ($constraint !== null) {
                $queryBuilder->andWhere($constraint);
            }
        }
    }

    /**
     * The option table: language, plus the site the request runs in.
     *
     * A filter value is bound to the site, not to the list's storage pages: it
     * may live anywhere in the site, its root included, so constraining it by
     * storage would drop values records legitimately point at. The site bound
     * is applied deliberately rather than derived — being related to a
     * delivered record does not imply living in the site.
     */
    protected function applyOptionScope(QueryBuilder $queryBuilder, string $table, FilterScope $scope): void
    {
        foreach ([
            $this->languageConstraint($queryBuilder, $table),
            $this->siteConstraint($queryBuilder, $table, $scope->getSitePageIds()),
        ] as $constraint) {
            if ($constraint !== null) {
                $queryBuilder->andWhere($constraint);
            }
        }
    }

    /**
     * @param int[] $sitePageIds
     */
    protected function siteConstraint(QueryBuilder $queryBuilder, string $table, array $sitePageIds): ?string
    {
        if ($sitePageIds === []) {
            return null;
        }

        return $queryBuilder->expr()->in(
            $table . '.pid',
            $queryBuilder->createNamedParameter($sitePageIds, Connection::PARAM_INT_ARRAY)
        );
    }

    /**
     * Only the default language and language -1 contribute; a translation would
     * otherwise offer its parent's value a second time.
     */
    protected function languageConstraint(QueryBuilder $queryBuilder, string $table): ?string
    {
        if (!$this->tcaSchemaFactory->has($table)) {
            return null;
        }

        $schema = $this->tcaSchemaFactory->get($table);
        if (!$schema->isLanguageAware()) {
            return null;
        }

        $languageField = $schema->getCapability(TcaSchemaCapability::Language)->getLanguageField()->getName();

        return $queryBuilder->expr()->in(
            $table . '.' . $languageField,
            $queryBuilder->createNamedParameter([0, -1], Connection::PARAM_INT_ARRAY)
        );
    }

    /**
     * @param int[] $storagePageIds
     */
    protected function storageConstraint(QueryBuilder $queryBuilder, string $table, array $storagePageIds): ?string
    {
        if ($storagePageIds === []) {
            return null;
        }

        return $queryBuilder->expr()->in(
            $table . '.pid',
            $queryBuilder->createNamedParameter($storagePageIds, Connection::PARAM_INT_ARRAY)
        );
    }

    /**
     * @param list<array<string, mixed>> $rows
     *
     * @return FilterOption[]
     */
    protected function toOptions(array $rows): array
    {
        return array_map(
            static function (array $row): FilterOption {
                $uid = $row['uid'] ?? 0;
                $title = $row['title'] ?? '';

                return new FilterOption(
                    is_numeric($uid) ? (int)$uid : 0,
                    is_scalar($title) ? (string)$title : ''
                );
            },
            $rows
        );
    }
}
