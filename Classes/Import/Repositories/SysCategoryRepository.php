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

namespace WerkraumMedia\ThueCat\Import\Repositories;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;

// Fetched at runtime via makeInstance (from the non-DI ImportLog entity), so must be public.
#[Autoconfigure(public: true)]
class SysCategoryRepository
{
    public function __construct(
        protected readonly ConnectionPool $connectionPool
    ) {
    }

    public function findPid(int $uid): int
    {
        $pid = $this->column($uid, 'pid');

        return is_numeric($pid) ? (int)$pid : 0;
    }

    public function findParent(int $uid): int
    {
        $parent = $this->column($uid, 'parent');

        return is_numeric($parent) ? (int)$parent : 0;
    }

    public function findTitle(int $uid): string
    {
        $title = $this->column($uid, 'title');

        return is_string($title) ? $title : '';
    }

    /**
     * Category uids currently related to one record field.
     *
     * @return list<int>
     */
    public function findRelatedUids(string $ownerTable, int $ownerUid, string $ownerField): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_category_record_mm');
        $rows = $queryBuilder->select('uid_local')
            ->from('sys_category_record_mm')
            ->where(
                $queryBuilder->expr()->eq(
                    'tablenames',
                    $queryBuilder->createNamedParameter($ownerTable)
                ),
                $queryBuilder->expr()->eq(
                    'fieldname',
                    $queryBuilder->createNamedParameter($ownerField)
                ),
                $queryBuilder->expr()->eq(
                    'uid_foreign',
                    $queryBuilder->createNamedParameter($ownerUid, Connection::PARAM_INT)
                )
            )
            ->orderBy('sorting_foreign')
            ->executeQuery()
            ->fetchFirstColumn()
        ;

        $uids = [];
        foreach ($rows as $uid) {
            if (is_numeric($uid)) {
                $uids[] = (int)$uid;
            }
        }

        return $uids;
    }

    /**
     * Related categories the import never created, identified by carrying no
     * remote_id. Submitting a relation list replaces the stored one, so these
     * have to be carried through or an editor's own choice is removed.
     *
     * @return list<int>
     */
    public function findRelatedUidsWithoutRemoteId(string $ownerTable, int $ownerUid, string $ownerField): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_category_record_mm');
        $rows = $queryBuilder->select('mm.uid_local')
            ->from('sys_category_record_mm', 'mm')
            ->join(
                'mm',
                'sys_category',
                'c',
                $queryBuilder->expr()->eq('c.uid', $queryBuilder->quoteIdentifier('mm.uid_local'))
            )
            ->where(
                $queryBuilder->expr()->eq(
                    'mm.tablenames',
                    $queryBuilder->createNamedParameter($ownerTable)
                ),
                $queryBuilder->expr()->eq(
                    'mm.fieldname',
                    $queryBuilder->createNamedParameter($ownerField)
                ),
                $queryBuilder->expr()->eq(
                    'mm.uid_foreign',
                    $queryBuilder->createNamedParameter($ownerUid, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->isNull('c.remote_id'),
                    $queryBuilder->expr()->eq('c.remote_id', $queryBuilder->createNamedParameter(''))
                )
            )
            ->orderBy('mm.sorting_foreign')
            ->executeQuery()
            ->fetchFirstColumn()
        ;

        $uids = [];
        foreach ($rows as $uid) {
            if (is_numeric($uid)) {
                $uids[] = (int)$uid;
            }
        }

        return $uids;
    }

    /**
     * Default-language uids with the given remote_id on any of the pages.
     *
     * @param list<int> $pageIds
     *
     * @return list<int>
     */
    public function findUidsByRemoteId(string $remoteId, array $pageIds): array
    {
        $queryBuilder = $this->queryBuilder();
        $rows = $queryBuilder->select('uid')
            ->from('sys_category')
            ->where(
                $queryBuilder->expr()->eq(
                    'remote_id',
                    $queryBuilder->createNamedParameter($remoteId)
                ),
                $queryBuilder->expr()->in(
                    'pid',
                    $queryBuilder->createNamedParameter($pageIds, Connection::PARAM_INT_ARRAY)
                ),
                $queryBuilder->expr()->eq(
                    'sys_language_uid',
                    $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                )
            )
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

    protected function column(int $uid, string $column): mixed
    {
        $queryBuilder = $this->queryBuilder();

        return $queryBuilder->select($column)
            ->from('sys_category')
            ->where($queryBuilder->expr()->eq(
                'uid',
                $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)
            ))
            ->executeQuery()
            ->fetchOne()
        ;
    }

    protected function queryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_category');
        // Deleted excluded; disabled/hidden kept (import must see editor-hidden categories).
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(new DeletedRestriction())
        ;

        return $queryBuilder;
    }
}
