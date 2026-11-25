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
use TYPO3\CMS\Core\Database\ConnectionPool;
use WerkraumMedia\ThueCat\Import\ImportLogger;
use WerkraumMedia\ThueCat\Import\Parser\DataHandlerPayload;
use WerkraumMedia\ThueCat\Import\Parser\Entity\EntityInterface;
use WerkraumMedia\ThueCat\Import\ResolverContext;

/**
 * Removes from a persisted root what upstream no longer supplies. Only a root was resolved with everything upstream
 * holds for it: its node was parsed and its references followed.
 */
final readonly class RootReconciliation
{
    public function __construct(
        private ConnectionPool $connectionPool,
        private ImportLogger $importLogger,
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
                $emptied = $this->emptiedFields($table, $remoteId, $uid, $root['entity'], $root['supplied']);
                if ($emptied !== []) {
                    $payload->addRow($table, $uid, $emptied);
                }
            }
        }
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
     * A decimal column holds its empty value as 0.00.
     */
    private function isEmpty(mixed $value): bool
    {
        return $value === null || $value === '' || (is_numeric($value) && (float)$value === 0.0);
    }
}
