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
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Dto\FilterOption;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Dto\FilterOptions;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Dto\FilterScope;
use WerkraumMedia\ThueCat\Service\FilterField\FilterFieldDefinition;
use WerkraumMedia\ThueCat\Service\FilterField\HierarchicalMmField;

/**
 * Offers a hierarchical field as the sets its records use, each expanded to its
 * whole subtree.
 *
 * A set is the ancestor whose own parent is the anchor. Records relate to
 * terms, not to the sets grouping them, so the offered tree is reached by
 * climbing from the used terms to their set and expanding downwards again —
 * which is why a branch no record uses is still offered.
 *
 * @implements FilterOptionProvider<HierarchicalMmField>
 */
class HierarchicalOptionProvider extends AbstractOptionProvider implements FilterOptionProvider
{
    /**
     * Depth ceiling for every recursion. A parent cycle makes the query itself
     * unbounded, so the guard has to bound the recursive term rather than
     * filter its result.
     *
     * Interpolated into the SQL rather than bound: a placeholder inside a
     * recursive term does not resolve, which silently removes the bound and
     * hangs the query. An int constant, so it carries no injection surface.
     */
    protected const MAX_DEPTH = 50;

    public function supports(FilterFieldDefinition $field): bool
    {
        return $field instanceof HierarchicalMmField;
    }

    /**
     * @param HierarchicalMmField $field
     */
    public function provide(FilterFieldDefinition $field, FilterScope $scope): FilterOptions
    {
        $anchor = $scope->anchorFor($field->getAnchorSetting());
        if ($anchor === 0) {
            return new FilterOptions($field->getName(), []);
        }

        $usedTerms = $this->usedTermUids($field, $scope);
        if ($usedTerms === []) {
            return new FilterOptions($field->getName(), []);
        }

        $sitePageIds = $scope->getSitePageIds();
        $sets = $this->setsOf($field, $usedTerms, $anchor, $sitePageIds);
        if ($sets === []) {
            return new FilterOptions($field->getName(), []);
        }

        return new FilterOptions(
            $field->getName(),
            $this->treeBelow($field, $sets, $sitePageIds)
        );
    }

    /**
     * The given uids plus every uid below them, for matching a selected set
     * against records carrying only its descendants.
     *
     * @param int[] $uids
     * @param int[] $sitePageIds
     *
     * @return int[]
     */
    public function descendantsOf(HierarchicalMmField $field, array $uids, array $sitePageIds = []): array
    {
        if ($uids === []) {
            return [];
        }

        $rows = $this->descendantRows($field, $uids, $sitePageIds);

        return array_values(array_unique(array_map(
            fn (array $row): int => $this->intValue($row['uid'] ?? null),
            $rows
        )));
    }

    /**
     * The terms the scoped records actually relate to.
     *
     * @return int[]
     */
    protected function usedTermUids(HierarchicalMmField $field, FilterScope $scope): array
    {
        $recordTable = $scope->getRecordTable();
        $mmTable = $field->getMmTable();
        $optionTable = $field->getOptionTable();

        $queryBuilder = $this->queryBuilderFor($recordTable);
        $queryBuilder
            ->selectLiteral('DISTINCT ' . $queryBuilder->quoteIdentifier($optionTable . '.uid') . ' AS uid')
            ->from($recordTable)
            ->join(
                $recordTable,
                $mmTable,
                $mmTable,
                (string)$queryBuilder->expr()->and(
                    $queryBuilder->expr()->eq(
                        $mmTable . '.uid_foreign',
                        $queryBuilder->quoteIdentifier($recordTable . '.uid')
                    ),
                    $queryBuilder->expr()->eq(
                        $mmTable . '.tablenames',
                        $queryBuilder->createNamedParameter($recordTable)
                    ),
                    $queryBuilder->expr()->eq(
                        $mmTable . '.fieldname',
                        $queryBuilder->createNamedParameter($field->getMmFieldName())
                    )
                )
            )
            ->join(
                $mmTable,
                $optionTable,
                $optionTable,
                $queryBuilder->expr()->eq(
                    $optionTable . '.uid',
                    $queryBuilder->quoteIdentifier($mmTable . '.uid_local')
                )
            )
        ;
        $this->applyRecordScope($queryBuilder, $recordTable, $scope);
        $this->applyOptionScope($queryBuilder, $optionTable, $scope);

        return array_map(
            fn (mixed $uid): int => $this->intValue($uid),
            $queryBuilder->executeQuery()->fetchFirstColumn()
        );
    }

    /**
     * Climbs from each used term to the ancestor whose own parent is the
     * anchor. A term whose rootline never passes the anchor belongs to another
     * tree and contributes nothing.
     *
     * @param int[] $termUids
     * @param int[] $sitePageIds
     *
     * @return int[]
     */
    protected function setsOf(HierarchicalMmField $field, array $termUids, int $anchor, array $sitePageIds): array
    {
        $optionTable = $field->getOptionTable();
        $parentColumn = $field->getParentColumn();

        $sql = 'WITH RECURSIVE climb AS ('
            . ' SELECT uid, ' . $parentColumn . ' AS parent, 0 AS depth'
            . ' FROM ' . $optionTable
            . ' WHERE uid IN (:terms) AND ' . $this->visibilitySql($optionTable, '', $sitePageIds)
            . ' UNION ALL'
            . ' SELECT o.uid, o.' . $parentColumn . ' AS parent, c.depth + 1'
            . ' FROM ' . $optionTable . ' o'
            . ' INNER JOIN climb c ON o.uid = c.parent'
            . ' WHERE ' . $this->visibilitySql($optionTable, 'o', $sitePageIds) . ' AND c.depth < ' . self::MAX_DEPTH
            . ' )'
            . ' SELECT DISTINCT uid FROM climb WHERE parent = :anchor';

        $rows = $this->connectionPool
            ->getConnectionForTable($optionTable)
            ->executeQuery(
                $sql,
                ['terms' => $termUids, 'anchor' => $anchor],
                ['terms' => Connection::PARAM_INT_ARRAY]
            )
            ->fetchFirstColumn()
        ;

        return array_map(
            fn (mixed $uid): int => $this->intValue($uid),
            $rows
        );
    }

    /**
     * @param int[] $setUids
     * @param int[] $sitePageIds
     *
     * @return FilterOption[]
     */
    protected function treeBelow(HierarchicalMmField $field, array $setUids, array $sitePageIds): array
    {
        $rows = $this->descendantRows($field, $setUids, $sitePageIds);

        $titles = [];
        $childrenOf = [];
        foreach ($rows as $row) {
            $uid = $this->intValue($row['uid'] ?? null);
            $titles[$uid] = is_scalar($row['title'] ?? null) ? (string)$row['title'] : '';
            $parent = $this->intValue($row['parent'] ?? null);
            // Sets anchor the offered tree, so their own parent is cut off.
            $childrenOf[in_array($uid, $setUids, true) ? 0 : $parent][] = $uid;
        }

        return $this->buildOptions($childrenOf[0] ?? [], $titles, $childrenOf, []);
    }

    /**
     * @param int[] $uids
     * @param array<int, string> $titles
     * @param array<int, int[]> $childrenOf
     * @param int[] $ancestors uids on the path here, so a cycle stops
     *
     * @return FilterOption[]
     */
    protected function buildOptions(array $uids, array $titles, array $childrenOf, array $ancestors): array
    {
        $options = [];
        foreach ($uids as $uid) {
            if (in_array($uid, $ancestors, true)) {
                continue;
            }

            $options[] = new FilterOption(
                $uid,
                $titles[$uid] ?? '',
                $this->buildOptions(
                    $childrenOf[$uid] ?? [],
                    $titles,
                    $childrenOf,
                    [...$ancestors, $uid]
                )
            );
        }

        usort($options, static fn (FilterOption $a, FilterOption $b): int => strcmp($a->getTitle(), $b->getTitle()));

        return $options;
    }

    /**
     * The given uids and everything below them, as flat rows carrying depth.
     *
     * @param int[] $uids
     * @param int[] $sitePageIds
     *
     * @return list<array<string, mixed>>
     */
    protected function descendantRows(HierarchicalMmField $field, array $uids, array $sitePageIds): array
    {
        $optionTable = $field->getOptionTable();
        $parentColumn = $field->getParentColumn();

        $sql = 'WITH RECURSIVE tree AS ('
            . ' SELECT uid, ' . $parentColumn . ' AS parent, title, 0 AS depth'
            . ' FROM ' . $optionTable
            . ' WHERE uid IN (:roots) AND ' . $this->visibilitySql($optionTable, '', $sitePageIds)
            . ' UNION ALL'
            . ' SELECT o.uid, o.' . $parentColumn . ' AS parent, o.title, t.depth + 1'
            . ' FROM ' . $optionTable . ' o'
            . ' INNER JOIN tree t ON o.' . $parentColumn . ' = t.uid'
            . ' WHERE ' . $this->visibilitySql($optionTable, 'o', $sitePageIds) . ' AND t.depth < ' . self::MAX_DEPTH
            . ' )'
            . ' SELECT DISTINCT uid, parent, title FROM tree';

        return $this->connectionPool
            ->getConnectionForTable($optionTable)
            ->executeQuery(
                $sql,
                ['roots' => $uids],
                ['roots' => Connection::PARAM_INT_ARRAY]
            )
            ->fetchAllAssociative()
        ;
    }
}
