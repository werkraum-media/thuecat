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

use Doctrine\DBAL\Result;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\DataHandling\TableColumnType;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;

/**
 * Finds the files a record references, and whether anybody still references a file.
 */
class FileUsage
{
    public function __construct(
        protected readonly ConnectionPool $connectionPool,
        protected readonly TcaSchemaFactory $tcaSchemaFactory,
    ) {
    }

    /**
     * The files of the record, its translations and its inline children, read before the record is deleted:
     * DataHandler deletes their file references with it.
     *
     * @return list<int> sys_file uids
     */
    public function candidatesOf(string $table, int $uid): array
    {
        $files = $this->filesReferencedBy($table, $uid);
        foreach ($this->translationsOf($table, $uid) as $translation) {
            $files = [...$files, ...$this->candidatesOf($table, $translation)];
        }

        $schema = $this->tcaSchemaFactory->get($table);
        foreach ($schema->getFieldsOfType(TableColumnType::INLINE) as $field) {
            /** @var array{foreign_table: string, foreign_field: string, foreign_table_field?: string} $config */
            $config = $field->getConfiguration();
            foreach ($this->inlineChildrenOf($table, $uid, $config) as $child) {
                $files = [...$files, ...$this->candidatesOf($config['foreign_table'], $child)];
            }
        }

        return array_keys(array_flip($files));
    }

    /**
     * File metadata points at its file, but does not use it.
     */
    public function isUnused(int $file): bool
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_refindex');
        $users = $queryBuilder
            ->count('hash')
            ->from('sys_refindex')
            ->where(
                $queryBuilder->expr()->eq('ref_table', $queryBuilder->createNamedParameter('sys_file')),
                $queryBuilder->expr()->eq('ref_uid', $queryBuilder->createNamedParameter($file, Connection::PARAM_INT)),
                $queryBuilder->expr()->neq('tablename', $queryBuilder->createNamedParameter('sys_file_metadata'))
            )
            ->executeQuery()
            ->fetchOne()
        ;

        return is_numeric($users) && (int)$users === 0;
    }

    /**
     * @return list<int>
     */
    protected function filesReferencedBy(string $table, int $uid): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_file_reference');
        $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());

        return $this->uidsOf($queryBuilder
            ->select('uid_local')
            ->from('sys_file_reference')
            ->where(
                $queryBuilder->expr()->eq('tablenames', $queryBuilder->createNamedParameter($table)),
                $queryBuilder->expr()->eq('uid_foreign', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT))
            )
            ->executeQuery());
    }

    /**
     * @return list<int>
     */
    protected function translationsOf(string $table, int $uid): array
    {
        $schema = $this->tcaSchemaFactory->get($table);
        if (!$schema->hasCapability(TcaSchemaCapability::Language)) {
            return [];
        }

        $translationOrigin = $schema->getCapability(TcaSchemaCapability::Language)->getTranslationOriginPointerField()->getName();
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());

        return $this->uidsOf($queryBuilder
            ->select('uid')
            ->from($table)
            ->where($queryBuilder->expr()->eq($translationOrigin, $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)))
            ->executeQuery());
    }

    /**
     * @param array{foreign_table: string, foreign_field: string, foreign_table_field?: string} $config
     *
     * @return list<int>
     */
    protected function inlineChildrenOf(string $table, int $uid, array $config): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($config['foreign_table']);
        $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());
        $queryBuilder
            ->select('uid')
            ->from($config['foreign_table'])
            ->where($queryBuilder->expr()->eq($config['foreign_field'], $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)))
        ;
        if (isset($config['foreign_table_field'])) {
            $queryBuilder->andWhere($queryBuilder->expr()->eq($config['foreign_table_field'], $queryBuilder->createNamedParameter($table)));
        }

        return $this->uidsOf($queryBuilder->executeQuery());
    }

    /**
     * @return list<int>
     */
    protected function uidsOf(Result $result): array
    {
        /** @var list<int|string> $uids */
        $uids = $result->fetchFirstColumn();

        return array_map(intval(...), $uids);
    }
}
