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

namespace WerkraumMedia\ThueCat\Typo3\FormEngine;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\DataHandling\ItemsProcessorContext;
use TYPO3\CMS\Core\DataHandling\ItemsProcessorInterface;
use TYPO3\CMS\Core\Schema\Struct\SelectItem;
use TYPO3\CMS\Core\Schema\Struct\SelectItemCollection;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;

/**
 * Offers the origin titles stored on a table's records as choices. A stored title, not the import configuration's
 * current one, matches records after its configuration was renamed.
 *
 * The `table` parameter names the table whose records supply the titles.
 */
#[Autoconfigure(public: true)]
class OriginChoicesItemsProcessor implements ItemsProcessorInterface
{
    protected const string FIELD = 'origin_configuration_title';

    public function __construct(
        protected readonly ConnectionPool $connectionPool,
        protected readonly TcaSchemaFactory $tcaSchemaFactory,
    ) {
    }

    public function processItems(SelectItemCollection $items, ItemsProcessorContext $context): SelectItemCollection
    {
        $table = $context->processorParameters['table'] ?? '';
        if (
            !is_string($table)
            || !$this->tcaSchemaFactory->has($table)
            || !$this->tcaSchemaFactory->get($table)->hasField(self::FIELD)
        ) {
            return $items;
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());
        $titles = $queryBuilder
            ->select(self::FIELD)
            ->distinct()
            ->from($table)
            ->where($queryBuilder->expr()->neq(self::FIELD, $queryBuilder->createNamedParameter('', Connection::PARAM_STR)))
            ->orderBy(self::FIELD)
            ->executeQuery()
            ->fetchFirstColumn()
        ;

        foreach ($titles as $title) {
            if (is_string($title)) {
                $items->add(new SelectItem(type: 'select', label: $title, value: $title));
            }
        }

        return $items;
    }
}
