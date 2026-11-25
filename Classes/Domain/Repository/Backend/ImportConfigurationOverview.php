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

namespace WerkraumMedia\ThueCat\Domain\Repository\Backend;

use Doctrine\DBAL\ArrayParameterType;
use Psr\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfigurationOverviewRow;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportJobOverviewRow;

/**
 * Lists the configurations whose storage page the user may read, grouped by the site of that page.
 */
#[Autoconfigure(public: true)]
class ImportConfigurationOverview
{
    protected const OPEN_JOB_STATUSES = ['queued', 'running', 'stopping'];

    protected const RECENT_JOBS = 5;

    /**
     * Seconds a queued job may wait, or a running job may go without progress, before it is marked stalled.
     */
    protected const STALLED_AFTER = 300;

    public function __construct(
        protected readonly ConnectionPool $connectionPool,
        protected readonly SiteFinder $siteFinder,
        protected readonly ClockInterface $clock,
    ) {
    }

    /**
     * @return array<string, list<ImportConfigurationOverviewRow>> keyed by site identifier, '' for storage pages
     *                                                              outside any site; rows ordered by title
     */
    public function bySite(BackendUserAuthentication $user): array
    {
        $configurations = $this->configurations();
        $storageTitles = $this->readablePageTitles($user, array_values(array_unique(array_map(
            fn (array $row): int => $this->intOf($row['storage_pid']),
            $configurations
        ))));
        $openJobs = $this->openJobs();

        $bySite = [];
        foreach ($configurations as $row) {
            $storagePid = $this->intOf($row['storage_pid']);
            if (!array_key_exists($storagePid, $storageTitles)) {
                continue;
            }

            $uid = $this->intOf($row['uid']);
            $bySite[$this->siteIdentifierOf($storagePid)][] = new ImportConfigurationOverviewRow(
                $uid,
                $this->stringOf($row['title']),
                $this->stringOf($row['type']),
                $storagePid,
                $storageTitles[$storagePid],
                (bool)$this->intOf($row['skip_media']),
                $this->intOf($row['schedule_interval']),
                $this->intOf($row['next_run']),
                $this->intOf($row['last_run']),
                $openJobs[$uid]['uid'] ?? 0,
                $openJobs[$uid]['status'] ?? '',
                $this->recentJobs($uid),
            );
        }

        return $bySite;
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function configurations(): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_thuecat_import_configuration');
        $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());

        return $queryBuilder
            ->select('uid', 'title', 'type', 'storage_pid', 'skip_media', 'schedule_interval', 'next_run', 'last_run')
            ->from('tx_thuecat_import_configuration')
            ->orderBy('title')
            ->addOrderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative()
        ;
    }

    /**
     * @param list<int> $pageUids
     *
     * @return array<int, string> titles keyed by page uid
     */
    protected function readablePageTitles(BackendUserAuthentication $user, array $pageUids): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());
        $rows = $queryBuilder
            ->select('uid', 'title')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->in('uid', $queryBuilder->createNamedParameter($pageUids, ArrayParameterType::INTEGER)),
                QueryHelper::stripLogicalOperatorPrefix($user->getPagePermsClause(Permission::PAGE_SHOW))
            )
            ->executeQuery()
            ->fetchAllAssociative()
        ;

        $titles = [];
        foreach ($rows as $row) {
            $uid = $this->intOf($row['uid']);
            if ($user->isInWebMount($uid) === null) {
                continue;
            }

            $titles[$uid] = $this->stringOf($row['title']);
        }

        return $titles;
    }

    /**
     * @return array<int, array{uid: int, status: string}> keyed by configuration uid
     */
    protected function openJobs(): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_thuecat_import_job');
        $rows = $queryBuilder
            ->select('uid', 'configuration', 'status')
            ->from('tx_thuecat_import_job')
            ->where($queryBuilder->expr()->in(
                'status',
                $queryBuilder->createNamedParameter(self::OPEN_JOB_STATUSES, ArrayParameterType::STRING)
            ))
            ->executeQuery()
            ->fetchAllAssociative()
        ;

        $jobs = [];
        foreach ($rows as $row) {
            $jobs[$this->intOf($row['configuration'])] = [
                'uid' => $this->intOf($row['uid']),
                'status' => $this->stringOf($row['status']),
            ];
        }

        return $jobs;
    }

    /**
     * @return list<ImportJobOverviewRow>
     */
    protected function recentJobs(int $configurationUid): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_thuecat_import_job');
        $rows = $queryBuilder
            ->select('*')
            ->from('tx_thuecat_import_job')
            ->where($queryBuilder->expr()->eq(
                'configuration',
                $queryBuilder->createNamedParameter($configurationUid, Connection::PARAM_INT)
            ))
            ->orderBy('crdate', 'DESC')
            ->addOrderBy('uid', 'DESC')
            ->setMaxResults(self::RECENT_JOBS)
            ->executeQuery()
            ->fetchAllAssociative()
        ;

        return array_map($this->jobRow(...), $rows);
    }

    /**
     * @param array<string, mixed> $row
     */
    protected function jobRow(array $row): ImportJobOverviewRow
    {
        $uid = $this->intOf($row['uid']);
        $status = $this->stringOf($row['status']);
        $items = $status === 'running' ? $this->itemsByState($uid) : [];
        $pending = $items['pending']['count'] ?? 0;
        $done = $items['done'] ?? ['count' => 0, 'duration' => 0];
        $total = array_sum(array_column($items, 'count'));

        return new ImportJobOverviewRow(
            $uid,
            $status,
            $this->stringOf($row['job_trigger']),
            $this->intOf($row['backend_user']),
            $this->intOf($row['started_at']),
            $this->intOf($row['ended_at']),
            $this->intOf($row['done_items']),
            $this->intOf($row['failed_items']),
            $this->intOf($row['skipped_items']),
            $this->intOf($row['import_log']),
            $total - $pending,
            $total,
            $done['count'] > 0 ? (int)round($done['duration'] * $pending / $done['count']) : null,
            in_array($status, ['queued', 'running'], true)
                && $this->clock->now()->getTimestamp() - $this->intOf($row['progressed_at']) > self::STALLED_AFTER,
        );
    }

    /**
     * @return array<string, array{count: int, duration: int}> keyed by item state
     */
    protected function itemsByState(int $jobUid): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_thuecat_import_job_item');
        $rows = $queryBuilder
            ->select('state')
            ->addSelectLiteral(
                $queryBuilder->expr()->count('uid', 'items'),
                $queryBuilder->expr()->sum('duration', 'duration')
            )
            ->from('tx_thuecat_import_job_item')
            ->where($queryBuilder->expr()->eq('job', $queryBuilder->createNamedParameter($jobUid, Connection::PARAM_INT)))
            ->groupBy('state')
            ->executeQuery()
            ->fetchAllAssociative()
        ;

        $items = [];
        foreach ($rows as $row) {
            $items[$this->stringOf($row['state'])] = [
                'count' => $this->intOf($row['items']),
                'duration' => $this->intOf($row['duration']),
            ];
        }

        return $items;
    }

    protected function siteIdentifierOf(int $pageUid): string
    {
        try {
            return $this->siteFinder->getSiteByPageId($pageUid)->getIdentifier();
        } catch (SiteNotFoundException) {
            return '';
        }
    }

    protected function intOf(mixed $value): int
    {
        return is_numeric($value) ? (int)$value : 0;
    }

    protected function stringOf(mixed $value): string
    {
        return is_string($value) ? $value : '';
    }
}
