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

namespace WerkraumMedia\ThueCat\Tests\Functional\Backend;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\DataHandling\ItemsProcessorContext;
use TYPO3\CMS\Core\Schema\Struct\SelectItem;
use TYPO3\CMS\Core\Schema\Struct\SelectItemCollection;
use TYPO3\CMS\Core\Site\Entity\NullSite;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractFunctionalTestCase;
use WerkraumMedia\ThueCat\Typo3\FormEngine\OriginChoicesItemsProcessor;

final class OriginChoicesItemsProcessorTest extends AbstractFunctionalTestCase
{
    /**
     * @return iterable<string, array{mixed, list<array{string, string}>}>
     */
    public static function tables(): iterable
    {
        yield 'a table with origins' => [
            'tx_thuecat_tourist_attraction',
            [['Existing', 'existing'], ['Erfurt POIs', 'Erfurt POIs'], ['Weimar POIs', 'Weimar POIs']],
        ];
        yield 'a table without origin column' => ['sys_category', [['Existing', 'existing']]];
        yield 'an unknown table' => ['tx_unknown', [['Existing', 'existing']]];
        yield 'no table' => [null, [['Existing', 'existing']]];
    }

    /**
     * @param list<array{string, string}> $expected
     */
    #[Test]
    #[DataProvider('tables')]
    public function offersEachStoredOriginTitleOfTheTableOnce(mixed $table, array $expected): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/OriginChoices.csv');

        $items = new SelectItemCollection();
        $items->add(new SelectItem(type: 'select', label: 'Existing', value: 'existing'));

        $context = new ItemsProcessorContext(
            table: 'tt_content',
            field: 'settings.origins',
            row: [],
            fieldConfiguration: [],
            processorParameters: $table === null ? [] : ['table' => $table],
            realPid: 1,
            site: new NullSite(),
        );

        $choices = [];
        foreach ($this->get(OriginChoicesItemsProcessor::class)->processItems($items, $context) as $item) {
            $choices[] = [$item->getLabel(), $item->getValue()];
        }

        $this->assertSame($expected, $choices);
    }
}
