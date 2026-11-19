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
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\LanguageMarker;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use WerkraumMedia\ThueCat\Service\SitePageIds;

/**
 * What the removal module offers: the pages holding roots, per site.
 *
 * Public, so functional tests can fetch it from the container.
 */
#[Autoconfigure(public: true)]
class RootOverview
{
    public function __construct(
        protected readonly ConnectionPool $connectionPool,
        protected readonly TcaSchemaFactory $tcaSchemaFactory,
        protected readonly SiteFinder $siteFinder,
        protected readonly SitePageIds $sitePageIds,
    ) {
    }

    /**
     * @return array<string, list<PageRoots>> keyed by site identifier, '' for pages outside any site
     */
    public function pagesBySite(BackendUserAuthentication $user): array
    {
        $countsByPage = [];
        foreach (RootTables::ALL as $table) {
            foreach ($this->countsByPage($table) as $pid => $count) {
                $countsByPage[$pid] ??= array_fill_keys(RootTables::ALL, 0);
                $countsByPage[$pid][$table] = $count;
            }
        }

        $bySite = [];
        foreach ($this->readablePages($user, array_keys($countsByPage)) as $page) {
            $bySite[$this->siteIdentifierOf($page['uid'])][] = new PageRoots(
                $page['uid'],
                $page['title'],
                $countsByPage[$page['uid']]
            );
        }

        $outsideSites = $bySite[''] ?? null;
        unset($bySite['']);
        if ($outsideSites !== null) {
            $bySite[''] = $outsideSites;
        }

        return $bySite;
    }

    /**
     * @return list<OriginRoots>
     */
    public function originsOfSite(int $siteRootPage): array
    {
        $pageIds = $this->sitePageIds->forRootPage($siteRootPage);
        $titles = [];
        $counts = [];
        foreach (RootTables::ALL as $table) {
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
            $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());
            $rows = $queryBuilder
                ->select('origin_configuration_uid')
                ->addSelectLiteral(
                    $queryBuilder->expr()->max('origin_configuration_title', 'title'),
                    $queryBuilder->expr()->count('uid', 'roots')
                )
                ->from($table)
                ->where(
                    $queryBuilder->expr()->eq(
                        $this->languageField($table),
                        $queryBuilder->createNamedParameter(LanguageMarker::DEFAULT_LANGUAGE, Connection::PARAM_INT)
                    ),
                    $queryBuilder->expr()->gt(
                        'origin_configuration_uid',
                        $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                    ),
                    $queryBuilder->expr()->in(
                        'pid',
                        $queryBuilder->createNamedParameter($pageIds, ArrayParameterType::INTEGER)
                    )
                )
                ->groupBy('origin_configuration_uid')
                ->executeQuery()
                ->fetchAllAssociative()
            ;

            foreach ($rows as $row) {
                $uid = (int)(is_numeric($row['origin_configuration_uid']) ? $row['origin_configuration_uid'] : 0);
                $titles[$uid] ??= is_string($row['title']) ? $row['title'] : '';
                $counts[$uid] = ($counts[$uid] ?? 0) + (int)(is_numeric($row['roots']) ? $row['roots'] : 0);
            }
        }

        ksort($counts);
        $origins = [];
        foreach ($counts as $uid => $count) {
            $origins[] = new OriginRoots($uid, $titles[$uid], $count);
        }

        return $origins;
    }

    /**
     * @return array<int, int>
     */
    protected function countsByPage(string $table): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());
        $rows = $queryBuilder
            ->select('pid')
            ->addSelectLiteral($queryBuilder->expr()->count('uid', 'roots'))
            ->from($table)
            ->where($queryBuilder->expr()->eq(
                $this->languageField($table),
                $queryBuilder->createNamedParameter(LanguageMarker::DEFAULT_LANGUAGE, Connection::PARAM_INT)
            ))
            ->groupBy('pid')
            ->executeQuery()
            ->fetchAllAssociative()
        ;

        $counts = [];
        foreach ($rows as $row) {
            $counts[(int)(is_numeric($row['pid']) ? $row['pid'] : 0)] = (int)(is_numeric($row['roots']) ? $row['roots'] : 0);
        }

        return $counts;
    }

    /**
     * @param list<int> $pageUids
     *
     * @return list<array{uid: int, title: string}>
     */
    protected function readablePages(BackendUserAuthentication $user, array $pageUids): array
    {
        if ($pageUids === []) {
            return [];
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());
        $rows = $queryBuilder
            ->select('*')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->in(
                    'uid',
                    $queryBuilder->createNamedParameter($pageUids, ArrayParameterType::INTEGER)
                ),
                QueryHelper::stripLogicalOperatorPrefix($user->getPagePermsClause(Permission::PAGE_SHOW))
            )
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative()
        ;

        $pages = [];
        foreach ($rows as $row) {
            if ($user->isInWebMount($row) === null) {
                continue;
            }

            $pages[] = [
                'uid' => (int)(is_numeric($row['uid']) ? $row['uid'] : 0),
                'title' => is_string($row['title']) ? $row['title'] : '',
            ];
        }

        return $pages;
    }

    protected function siteIdentifierOf(int $pageUid): string
    {
        try {
            return $this->siteFinder->getSiteByPageId($pageUid)->getIdentifier();
        } catch (SiteNotFoundException) {
            return '';
        }
    }

    protected function languageField(string $table): string
    {
        return $this->tcaSchemaFactory
            ->get($table)
            ->getCapability(TcaSchemaCapability::Language)
            ->getLanguageField()
            ->getName()
        ;
    }
}
