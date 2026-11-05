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
use TYPO3\CMS\Core\DataHandling\ItemsProcessorContext;
use TYPO3\CMS\Core\DataHandling\ItemsProcessorInterface;
use TYPO3\CMS\Core\Schema\Struct\SelectItem;
use TYPO3\CMS\Core\Schema\Struct\SelectItemCollection;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;

/**
 * Offers the record types of a table as choices, so a type added to the table's type column becomes selectable
 * without touching the fields that offer it.
 *
 * Parameter `table`: the table whose type column supplies the items.
 */
#[Autoconfigure(public: true)]
class RecordTypeChoicesItemsProcessor implements ItemsProcessorInterface
{
    public function __construct(
        protected readonly TcaSchemaFactory $tcaSchemaFactory,
    ) {
    }

    public function processItems(SelectItemCollection $items, ItemsProcessorContext $context): SelectItemCollection
    {
        $table = $context->processorParameters['table'] ?? '';
        if (!is_string($table) || !$this->tcaSchemaFactory->has($table)) {
            return $items;
        }

        $schema = $this->tcaSchemaFactory->get($table);
        if (!$schema->supportsSubSchema()) {
            return $items;
        }

        $typeField = $schema->getField($schema->getSubSchemaTypeInformation()->getFieldName());
        $typeItems = $typeField->getConfiguration()['items'] ?? [];
        if (!is_array($typeItems)) {
            return $items;
        }

        foreach ($typeItems as $typeItem) {
            if (is_array($typeItem)) {
                $items->add(SelectItem::fromTcaItemArray($typeItem));
            }
        }

        return $items;
    }
}
