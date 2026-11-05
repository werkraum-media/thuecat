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

namespace WerkraumMedia\ThueCat\Tests\Unit\Typo3\FormEngine;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\DataHandling\ItemsProcessorContext;
use TYPO3\CMS\Core\Schema\Struct\SelectItem;
use TYPO3\CMS\Core\Schema\Struct\SelectItemCollection;
use TYPO3\CMS\Core\Site\Entity\NullSite;
use WerkraumMedia\ThueCat\Typo3\FormEngine\RecordTypeItemsProcessor;

/**
 * Items 1 and 2 are attractions, 3 and 4 meeting venues.
 */
class RecordTypeItemsProcessorTest extends TestCase
{
    /**
     * A field processed earlier in the same sheet arrives as a list, the processed field itself as a comma string.
     *
     * @return iterable<string, array{array<string, mixed>, list<int>}>
     */
    public static function rows(): iterable
    {
        $cases = [
            'only items of the ticked types' => [['meeting_venue'], [], [3, 4]],
            'every item when no type is ticked' => [[], [], [1, 2, 3, 4]],
            'selected items of an unticked type' => [['meeting_venue'], ['2'], [2, 3, 4]],
        ];
        foreach ($cases as $name => [$types, $selected, $expected]) {
            yield $name . ', comma strings' => [
                ['settings.recordTypes' => implode(',', $types), 'settings.selectedRecords' => implode(',', $selected)],
                $expected,
            ];
            yield $name . ', lists' => [
                ['settings.recordTypes' => $types, 'settings.selectedRecords' => $selected],
                $expected,
            ];
        }
    }

    /**
     * @param array<string, mixed> $row
     * @param list<int> $expected
     */
    #[Test]
    #[DataProvider('rows')]
    public function keepsItemsOfTheTickedTypesAndTheSelectedItems(array $row, array $expected): void
    {
        $items = new SelectItemCollection();
        $items->add(new SelectItem(type: 'select', label: 'Stadtmuseum', value: 1, group: 'attraction'));
        $items->add(new SelectItem(type: 'select', label: 'Domplatz', value: 2, group: 'attraction'));
        $items->add(new SelectItem(type: 'select', label: 'Tagungshotel', value: 3, group: 'meeting_venue'));
        $items->add(new SelectItem(type: 'select', label: 'Kongresszentrum', value: 4, group: 'meeting_venue'));

        $context = new ItemsProcessorContext(
            table: 'tt_content',
            field: 'settings.selectedRecords',
            row: $row,
            fieldConfiguration: [],
            processorParameters: ['typesField' => 'settings.recordTypes'],
            realPid: 1,
            site: new NullSite(),
        );

        $values = [];
        foreach ((new RecordTypeItemsProcessor())->processItems($items, $context) as $item) {
            $values[] = $item->getValue();
        }

        self::assertSame($expected, $values);
    }
}
