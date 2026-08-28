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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Dto\FilterOptions;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Dto\FilterScope;
use WerkraumMedia\ThueCat\Service\FilterField\CommaColumnField;
use WerkraumMedia\ThueCat\Service\FilterField\FilterFieldDefinition;

/**
 * Offers the values a comma-separated uid column carries across the scoped
 * records, as uid/title pairs read straight from the option table.
 *
 * The column is a TCA select without MM, so there is no relation table to join
 * against. Splitting the lists in PHP keeps the SQL portable: matching them in
 * the database would need FIND_IN_SET, which the test platform does not have.
 *
 * @implements FilterOptionProvider<CommaColumnField>
 */
class CommaColumnOptionProvider extends AbstractOptionProvider implements FilterOptionProvider
{
    public function supports(FilterFieldDefinition $field): bool
    {
        return $field instanceof CommaColumnField;
    }

    /**
     * @param CommaColumnField $field
     */
    public function provide(FilterFieldDefinition $field, FilterScope $scope): FilterOptions
    {
        $uids = $this->relatedUids($field, $scope);
        if ($uids === []) {
            return new FilterOptions($field->getName(), []);
        }

        return new FilterOptions(
            $field->getName(),
            $this->toOptions($this->optionRows($field, $scope, $uids))
        );
    }

    /**
     * Every uid the scoped records carry. array_filter drops the 0 a record
     * without a value holds: it is a placeholder, not an option.
     *
     * @return int[]
     */
    protected function relatedUids(CommaColumnField $field, FilterScope $scope): array
    {
        $recordTable = $scope->getRecordTable();
        $column = $field->getRecordColumn();

        $queryBuilder = $this->queryBuilderFor($recordTable);
        $queryBuilder
            ->selectLiteral('DISTINCT ' . $queryBuilder->quoteIdentifier($column) . ' AS value')
            ->from($recordTable)
        ;
        $this->applyRecordScope($queryBuilder, $recordTable, $scope);

        $lists = array_map(
            static fn (mixed $value): string => is_scalar($value) ? (string)$value : '',
            $queryBuilder->executeQuery()->fetchFirstColumn()
        );

        return array_values(array_filter(array_unique(
            GeneralUtility::intExplode(',', implode(',', $lists), true)
        )));
    }

    /**
     * @param int[] $uids
     *
     * @return list<array<string, mixed>>
     */
    protected function optionRows(CommaColumnField $field, FilterScope $scope, array $uids): array
    {
        $optionTable = $field->getOptionTable();

        $queryBuilder = $this->queryBuilderFor($optionTable);
        $queryBuilder
            ->select('uid', 'title')
            ->from($optionTable)
            ->where($queryBuilder->expr()->in(
                'uid',
                $queryBuilder->createNamedParameter($uids, Connection::PARAM_INT_ARRAY)
            ))
            ->orderBy('title')
        ;
        $this->applyOptionScope($queryBuilder, $optionTable, $scope);

        return $queryBuilder->executeQuery()->fetchAllAssociative();
    }
}
