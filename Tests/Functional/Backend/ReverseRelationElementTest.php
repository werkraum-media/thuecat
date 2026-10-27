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
use WerkraumMedia\ThueCat\Tests\Functional\AbstractBackendTestCase;

class ReverseRelationElementTest extends AbstractBackendTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Backend/ReverseRelations.csv');
    }

    /**
     * @return iterable<string, array{string, int, string, string, list<string>}>
     */
    public static function reverseRelations(): iterable
    {
        yield 'attractions of the default organisation' => ['tx_thuecat_organisation', 1, 'manages_tourist_attraction', '[1] Attraktion DE', ['Attraction EN', 'Attraktion einer anderen Organisation']];
        yield 'attractions of the translated organisation' => ['tx_thuecat_organisation', 2, 'manages_tourist_attraction', '[2] Attraction EN', ['Attraktion DE']];
        yield 'towns of the default organisation' => ['tx_thuecat_organisation', 1, 'manages_towns', '[1] Stadt DE', ['Town EN']];
        yield 'towns of the translated organisation' => ['tx_thuecat_organisation', 2, 'manages_towns', '[2] Town EN', ['Stadt DE']];
        yield 'tourist information of the default organisation' => ['tx_thuecat_organisation', 1, 'manages_tourist_information', '[1] Touristinformation DE', ['Tourist information EN']];
        yield 'tourist information of the translated organisation' => ['tx_thuecat_organisation', 2, 'manages_tourist_information', '[2] Tourist information EN', ['Touristinformation DE']];
        yield 'tourist information of the default town' => ['tx_thuecat_town', 1, 'tourist_information', '[1] Touristinformation DE', ['Tourist information EN']];
        yield 'tourist information of the translated town' => ['tx_thuecat_town', 2, 'tourist_information', '[2] Tourist information EN', ['Touristinformation DE']];
    }

    /**
     * @param list<string> $notListed
     */
    #[Test]
    #[DataProvider('reverseRelations')]
    public function listsTheRecordsPointingHereInTheRecordsLanguage(string $table, int $uid, string $field, string $listed, array $notListed): void
    {
        $html = $this->renderFormField($table, $uid, $field);

        self::assertStringContainsString($listed, $html);
        foreach ($notListed as $title) {
            self::assertStringNotContainsString($title, $html);
        }
    }

    #[Test]
    public function saysSoWhenNoRecordPointsHere(): void
    {
        $html = $this->renderFormField('tx_thuecat_organisation', 3, 'manages_towns');

        self::assertStringContainsString('No records', $html);
    }
}
