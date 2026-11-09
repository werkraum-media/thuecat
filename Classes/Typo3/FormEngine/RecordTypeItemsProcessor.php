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

use TYPO3\CMS\Core\DataHandling\ItemsProcessorContext;
use TYPO3\CMS\Core\DataHandling\ItemsProcessorInterface;
use TYPO3\CMS\Core\Schema\Struct\SelectItemCollection;

/**
 * Narrows a record picker grouped by record type (`foreign_table_item_group`) to the ticked types.
 *
 * Parameter `typesField`: the field holding the ticked types. Nothing ticked offers every type. Items already
 * selected always stay, so changing the ticks never turns a saved selection into invalid values.
 *
 * Inside a FlexForm the row holds the sheet's values: a field processed earlier arrives as a list, the field being
 * processed still as a comma string, so both shapes are read.
 */
class RecordTypeItemsProcessor implements ItemsProcessorInterface
{
    public function processItems(SelectItemCollection $items, ItemsProcessorContext $context): SelectItemCollection
    {
        $typesField = $context->processorParameters['typesField'] ?? '';
        $types = is_string($typesField) ? $this->listOf($context->row[$typesField] ?? '') : [];
        if ($types === []) {
            return $items;
        }

        $selected = $this->listOf($context->row[$context->field] ?? '');

        $kept = new SelectItemCollection();
        foreach ($items as $item) {
            if (in_array((string)$item->getGroup(), $types, true)
                || in_array((string)$item->getValue(), $selected, true)
            ) {
                $kept->add($item);
            }
        }

        return $kept;
    }

    /**
     * @return list<string>
     */
    protected function listOf(mixed $value): array
    {
        $values = is_array($value) ? $value : explode(',', is_scalar($value) ? (string)$value : '');

        return array_values(array_filter(
            array_map(static fn (mixed $entry): string => is_scalar($entry) ? trim((string)$entry) : '', $values),
            static fn (string $entry): bool => $entry !== ''
        ));
    }
}
