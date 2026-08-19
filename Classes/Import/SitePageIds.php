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

namespace WerkraumMedia\ThueCat\Import;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\SiteFinder;

/**
 * The page uids of a site, as the import understands "site": every page below
 * the site root, filtered by soft-delete alone.
 *
 * Import scope must not be derived with PageRepository::getPageIdsRecursive(),
 * which applies enable-fields. A hidden storage folder, or one past its
 * endtime, would drop out of the set — and identity is a property of where a
 * record is stored, not of whether the storing page is visible in the
 * frontend. With enable-fields applied, hiding a storage folder makes the
 * configuration validator reject the run outright ("outside the storagePid's
 * site") and makes the resolver stop matching what it already imported,
 * duplicating records and whole category trees on the next run.
 *
 * One derivation for every import scope question: record identity, category
 * matching, keyword matching and pre-flight validation.
 */
class SitePageIds
{
    public function __construct(
        private readonly SiteFinder $siteFinder,
        private readonly ConnectionPool $connectionPool,
    ) {
    }

    /**
     * @throws SiteNotFoundException when the pid belongs to no site
     *
     * @return list<int>
     */
    public function forStoragePid(int $storagePid): array
    {
        $rootPageId = $this->siteFinder->getSiteByPageId($storagePid)->getRootPageId();

        return $this->forRootPage($rootPageId);
    }

    /**
     * Empty when the pid belongs to no site, for callers that treat an
     * unresolvable site as "nothing in scope" rather than an error.
     *
     * @return list<int>
     */
    public function forStoragePidOrEmpty(int $storagePid): array
    {
        try {
            return $this->forStoragePid($storagePid);
        } catch (SiteNotFoundException) {
            return [];
        }
    }

    /**
     * @return list<int>
     */
    public function forRootPage(int $rootPageId): array
    {
        $pageIds = [$rootPageId];
        $current = [$rootPageId];

        // Level by level, so the query count follows tree depth rather than
        // page count. Exits when a level adds nothing new; ids already seen
        // are never requeued, so a page pointing at an ancestor cannot loop.
        while (true) {
            $children = $this->childPageIds($current);
            $new = array_values(array_diff($children, $pageIds));
            if ($new === []) {
                break;
            }
            $pageIds = array_merge($pageIds, $new);
            $current = $new;
        }

        return $pageIds;
    }

    /**
     * @param list<int> $parentIds
     *
     * @return list<int>
     */
    private function childPageIds(array $parentIds): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(new DeletedRestriction())
        ;

        $rows = $queryBuilder
            ->select('uid')
            ->from('pages')
            ->where($queryBuilder->expr()->in(
                'pid',
                $queryBuilder->createNamedParameter($parentIds, Connection::PARAM_INT_ARRAY)
            ))
            ->executeQuery()
            ->fetchAllAssociative()
        ;

        $uids = [];
        foreach ($rows as $row) {
            $uid = (int)(is_numeric($row['uid']) ? $row['uid'] : 0);
            if ($uid > 0) {
                $uids[] = $uid;
            }
        }

        return $uids;
    }
}
