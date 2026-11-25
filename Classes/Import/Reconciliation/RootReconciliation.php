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

namespace WerkraumMedia\ThueCat\Import\Reconciliation;

use ReflectionProperty;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\DataHandling\TableColumnType;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Site\Entity\Site;
use WerkraumMedia\ThueCat\Import\CollectedMedia;
use WerkraumMedia\ThueCat\Import\ImportLogger;
use WerkraumMedia\ThueCat\Import\Parser\DataHandlerPayload;
use WerkraumMedia\ThueCat\Import\Parser\Entity\EntityInterface;
use WerkraumMedia\ThueCat\Import\Repositories\SysCategoryRepository;
use WerkraumMedia\ThueCat\Import\ResolverContext;
use WerkraumMedia\ThueCat\Import\Settings\AnchorKind;
use WerkraumMedia\ThueCat\Import\Settings\CategoryAnchorResolver;

/**
 * Removes from a persisted root what upstream no longer supplies. Only a root was resolved with everything upstream
 * holds for it: its node was parsed and its references followed.
 */
final readonly class RootReconciliation
{
    public function __construct(
        private ConnectionPool $connectionPool,
        private ImportLogger $importLogger,
        private TcaSchemaFactory $tcaSchemaFactory,
        private SysCategoryRepository $sysCategoryRepository,
        private CategoryAnchorResolver $categoryAnchorResolver,
    ) {
    }

    /**
     * Stages the changes as rows of the payload, for a pass after the persist loop.
     */
    public function reconcile(DataHandlerPayload $payload, ResolverContext $context): void
    {
        foreach ($payload->getRoots() as $table => $roots) {
            foreach ($roots as $remoteId => $root) {
                $uid = $context->remoteIdToKey[$remoteId] ?? '';
                $emptied = $this->emptiedFields($table, $remoteId, $uid, $root['entity'], $root['supplied'])
                    + $this->editorOnlyRelations($context->site, $table, $remoteId, (int)$uid, $root['entity'], $root['supplied']);
                if ($emptied !== []) {
                    $payload->addRow($table, $uid, $emptied);
                }
            }
        }
    }

    /**
     * The deletions of inline children and file references upstream no longer supplies, as a DataHandler cmdmap. They run before the
     * persist loop: localizing a root copies every inline child still stored, deleted ones included.
     *
     * @return array<string, array<int, array{delete: 1}>>
     */
    public function unsuppliedChildDeletions(DataHandlerPayload $payload, ResolverContext $context): array
    {
        $deletions = [];
        foreach ($payload->getRoots() as $table => $roots) {
            foreach ($roots as $remoteId => $root) {
                $uid = $context->remoteIdToKey[$remoteId] ?? '';
                // A new root has no stored children.
                if (!is_numeric($uid)) {
                    continue;
                }

                foreach ($this->unsuppliedChildrenOf($context, $table, $remoteId, (int)$uid, $root['entity'], $root['children']) as $childTable => $commands) {
                    $deletions[$childTable] = ($deletions[$childTable] ?? []) + $commands;
                }
            }
        }

        return $deletions;
    }

    /**
     * A column the root neither supplied nor claimed is one upstream cleared: toArray() drops an empty value, and a
     * reference writes its column only when it resolves. A value is written empty for its type, the default of the
     * entity's property; a reference column is written empty, and the record it pointed at stays.
     *
     * @param class-string<EntityInterface> $entity
     * @param list<string> $supplied
     *
     * @return array<string, string|int|float>
     */
    private function emptiedFields(string $table, string $remoteId, string $uid, string $entity, array $supplied): array
    {
        $unsupplied = array_diff_key(
            array_filter(
                $entity::RECONCILED_FIELDS,
                static fn (FieldMechanism $mechanism): bool => in_array($mechanism, [FieldMechanism::Scalar, FieldMechanism::ReferenceWrite], true)
            ),
            array_flip($supplied)
        );
        if ($unsupplied === []) {
            return [];
        }

        $stored = $this->connectionPool->getConnectionForTable($table)
            ->select(array_keys($unsupplied), $table, ['uid' => $uid])
            ->fetchAssociative() ?: [];
        $emptied = [];
        foreach ($unsupplied as $field => $mechanism) {
            $value = $stored[$field] ?? null;
            if ($this->isEmpty($value)) {
                continue;
            }

            /** @var string|int|float $value */
            $this->importLogger->recordRemovedUpstream($table, $remoteId, $field, $value);

            /** @var string|int|float $empty */
            $empty = $mechanism === FieldMechanism::Scalar ? new ReflectionProperty($entity, $field)->getDefaultValue() : '';
            $emptied[$field] = $empty;
        }

        return $emptied;
    }

    /**
     * A relation list the root wrote nothing into is one upstream emptied: a list is written whenever one member
     * resolved or a failure carried the stored ones over. The list keeps only what the import did not create. Without
     * an anchor nothing is written into the list, so what upstream supplies is unknown and the list stays.
     *
     * @param class-string<EntityInterface> $entity
     * @param list<string> $supplied
     *
     * @return array<string, string>
     */
    private function editorOnlyRelations(?Site $site, string $table, string $remoteId, int $uid, string $entity, array $supplied): array
    {
        $lists = [];
        foreach (array_keys($entity::RECONCILED_FIELDS, FieldMechanism::RelationReplace, true) as $field) {
            if (in_array($field, $supplied, true)) {
                continue;
            }

            $kind = $field === $entity::KEYWORD_FIELD ? AnchorKind::Keyword : AnchorKind::Category;
            if (!$site instanceof Site || !$this->categoryAnchorResolver->resolvePair($site, $table, $kind)->isSet()) {
                continue;
            }

            $imported = $this->sysCategoryRepository->findRelatedRemoteIds($table, $uid, $field);
            if ($imported === []) {
                continue;
            }

            foreach ($imported as $categoryRemoteId) {
                $this->importLogger->recordRemovedUpstream($table, $remoteId, $field, $categoryRemoteId);
            }

            $lists[$field] = implode(',', $this->sysCategoryRepository->findRelatedUidsWithoutRemoteId($table, $uid, $field));
        }

        return $lists;
    }

    /**
     * Child nodes ride inside the root's own node, so a child the parsed root no longer carries is one upstream
     * dropped. A child without a remote_id is an editor's and stays. Translations go with their default-language row.
     *
     * @param class-string<EntityInterface> $entity
     * @param array<string, true> $children
     *
     * @return array<string, array<int, array{delete: 1}>>
     */
    private function unsuppliedChildrenOf(
        ResolverContext $context,
        string $table,
        string $remoteId,
        int $uid,
        string $entity,
        array $children,
    ): array {
        $deletions = [];
        $schema = $this->tcaSchemaFactory->get($table);
        foreach (array_keys($entity::RECONCILED_FIELDS, FieldMechanism::ExplicitDelete, true) as $field) {
            $tcaField = $schema->getField($field);
            if ($tcaField->isType(TableColumnType::FILE)) {
                if ($this->suppliedNoMedia($context, $table, $uid, $field)) {
                    foreach ($this->storedFileReferences($table, $uid, $field) as $referenceUid => $identifier) {
                        $this->importLogger->recordRemovedUpstream($table, $remoteId, $field, $identifier);
                        $deletions['sys_file_reference'][$referenceUid] = ['delete' => 1];
                    }
                }

                continue;
            }

            /** @var array{foreign_table: string, foreign_field: string, foreign_table_field: string, foreign_match_fields?: array<string, string>} $configuration */
            $configuration = $tcaField->getConfiguration();
            $childTable = $configuration['foreign_table'];
            foreach ($this->storedChildren($configuration, $table, $uid) as $childUid => $childRemoteId) {
                if (isset($children[$childRemoteId])) {
                    continue;
                }

                $this->importLogger->recordRemovedUpstream($table, $remoteId, $field, $childRemoteId);
                $deletions[$childTable][$childUid] = ['delete' => 1];
            }
        }

        return $deletions;
    }

    /**
     * A field that collected an image is reaped by the media flush. Without media, or after a failed download, what
     * upstream supplies is unknown.
     */
    private function suppliedNoMedia(ResolverContext $context, string $table, int $uid, string $field): bool
    {
        if ($context->skipMedia || isset($context->mediaFailureByField[$table . '|' . $uid . '|' . $field])) {
            return false;
        }
        return array_all($context->collectedMedia, fn (CollectedMedia $media): bool => !($media->ownerTable === $table && $media->ownerKey === (string)$uid && $media->targetField === $field));
    }

    /**
     * Default-language references in one file field, as uid => the file's identifier.
     *
     * @return array<int, string>
     */
    private function storedFileReferences(string $table, int $uid, string $field): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_file_reference');
        $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());
        $queryBuilder->select('r.uid', 'f.identifier')
            ->from('sys_file_reference', 'r')
            ->join('r', 'sys_file', 'f', $queryBuilder->expr()->eq('f.uid', $queryBuilder->quoteIdentifier('r.uid_local')))
            ->where(
                $queryBuilder->expr()->eq('r.tablenames', $queryBuilder->createNamedParameter($table)),
                $queryBuilder->expr()->eq('r.fieldname', $queryBuilder->createNamedParameter($field)),
                $queryBuilder->expr()->eq('r.uid_foreign', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('r.sys_language_uid', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
            )
        ;

        $references = [];
        foreach ($queryBuilder->executeQuery()->fetchAllAssociative() as $row) {
            /** @var array{uid: int|string, identifier: string} $row */
            $references[(int)$row['uid']] = $row['identifier'];
        }

        return $references;
    }

    /**
     * Default-language children of one parent that carry a remote_id, as uid => remote_id.
     *
     * @param array{foreign_table: string, foreign_field: string, foreign_table_field: string, foreign_match_fields?: array<string, string>} $configuration
     *
     * @return array<int, string>
     */
    private function storedChildren(array $configuration, string $parentTable, int $parentUid): array
    {
        $childTable = $configuration['foreign_table'];
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($childTable);
        $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());
        $queryBuilder->select('uid', 'remote_id')
            ->from($childTable)
            ->where(
                $queryBuilder->expr()->eq($configuration['foreign_field'], $queryBuilder->createNamedParameter($parentUid, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq($configuration['foreign_table_field'], $queryBuilder->createNamedParameter($parentTable)),
                $queryBuilder->expr()->neq('remote_id', $queryBuilder->createNamedParameter('')),
            )
        ;
        foreach ($configuration['foreign_match_fields'] ?? [] as $column => $value) {
            $queryBuilder->andWhere($queryBuilder->expr()->eq($column, $queryBuilder->createNamedParameter($value)));
        }

        $childSchema = $this->tcaSchemaFactory->get($childTable);
        if ($childSchema->isLanguageAware()) {
            $languageField = $childSchema->getCapability(TcaSchemaCapability::Language)->getLanguageField()->getName();
            $queryBuilder->andWhere($queryBuilder->expr()->eq($languageField, $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)));
        }

        $children = [];
        foreach ($queryBuilder->executeQuery()->fetchAllAssociative() as $row) {
            /** @var array{uid: int|string, remote_id: string} $row */
            $children[(int)$row['uid']] = $row['remote_id'];
        }

        return $children;
    }

    /**
     * A decimal column holds its empty value as 0.00.
     */
    private function isEmpty(mixed $value): bool
    {
        return $value === null || $value === '' || (is_numeric($value) && (float)$value === 0.0);
    }
}
