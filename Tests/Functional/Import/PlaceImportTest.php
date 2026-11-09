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

namespace WerkraumMedia\ThueCat\Tests\Functional\Import;

use PHPUnit\Framework\Attributes\Test;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractImportTestCase;

/**
 * A place is whatever no more specific entity claims; it exists to be related.
 */
final class PlaceImportTest extends AbstractImportTestCase
{
    private const string RESOURCES = 'https://thuecat.org/resources/';

    protected function setUp(): void
    {
        parent::setUp();

        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsPlaces.php');
    }

    #[Test]
    public function storeIsImportedAsPlaceWithAddressAndTranslation(): void
    {
        $this->expectFetch('place-store.json');

        $this->importConfiguration(1);

        $place = $this->fetchRowByRemoteId('tx_thuecat_place', self::RESOURCES . 'place-store');
        $this->assertSame('Glaskunstladen', $place['title']);
        $this->assertSame('Mundgeblasenes Glas', $place['description']);
        $this->assertIsInt($place['uid']);
        $translation = $this->fetchTranslationsByParent('tx_thuecat_place', $place['uid'])[1] ?? [];
        $this->assertSame('Glass art shop', $translation['title'] ?? null);
        $this->assertSame('Hand-blown glass', $translation['description'] ?? null);

        $addresses = $this->fetchChildren('tx_thuecat_address');
        $this->assertCount(1, $addresses);
        $this->assertSame('tx_thuecat_place', $addresses[0]['parenttable']);
        $this->assertSame($place['uid'], $addresses[0]['parentid']);
        $this->assertSame('99084', $addresses[0]['zip']);

        $this->assertSame(0, $this->fetchUidByRemoteId('tx_thuecat_organisation', self::RESOURCES . 'place-store'));
        $this->assertSame(0, $this->fetchUidByRemoteId('tx_thuecat_tourist_attraction', self::RESOURCES . 'place-store'));
    }

    #[Test]
    public function nodeOfUnknownTopLevelClassIsImportedAsPlace(): void
    {
        $this->expectFetch('place-manufacturer.json');

        $this->importConfiguration(2);

        $this->assertGreaterThan(0, $this->fetchUidByRemoteId('tx_thuecat_place', self::RESOURCES . 'place-manufacturer'));
    }

    #[Test]
    public function moreSpecificEntityWinsOverPlace(): void
    {
        $this->expectFetch('place-attraction.json');

        $this->importConfiguration(3);

        $this->assertGreaterThan(0, $this->fetchUidByRemoteId('tx_thuecat_tourist_attraction', self::RESOURCES . 'place-attraction'));
        $this->assertSame(0, $this->fetchUidByRemoteId('tx_thuecat_place', self::RESOURCES . 'place-attraction'));
    }

    #[Test]
    public function attractionRelatesTheSquareItIsContainedIn(): void
    {
        $this->expectFetch('attraction-on-a-square.json');
        $this->expectFetch('place-square.json');

        $this->importConfiguration(4);

        $squareUid = $this->fetchUidByRemoteId('tx_thuecat_place', self::RESOURCES . 'place-square');
        $this->assertGreaterThan(0, $squareUid);
        $attraction = $this->fetchRowByRemoteId('tx_thuecat_tourist_attraction', self::RESOURCES . 'attraction-on-a-square');
        $this->assertSame((string)$squareUid, $attraction['contained_in_place']);
    }
}
