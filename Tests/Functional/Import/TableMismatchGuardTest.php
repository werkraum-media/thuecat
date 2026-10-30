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
 * The hotel is stored as organisation 7 and its payload is claimed as an attraction.
 * Attraction 7 is the English translation of an unrelated record, which the
 * organisation's uid must never reach.
 */
final class TableMismatchGuardTest extends AbstractImportTestCase
{
    private const HOTEL = 'https://thuecat.org/resources/hotel-kind-changed';
    private const RESTAURANT = 'https://thuecat.org/resources/restaurant-in-kind-changed-hotel';

    protected function setUp(): void
    {
        parent::setUp();

        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsRestaurantInKindChangedHotel.php');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Import/ExistingHotelOrganisationAndUnrelatedAttractionTranslation.csv');
        $this->expectFetch('restaurant-in-kind-changed-hotel.json');
    }

    #[Test]
    public function recordKnownFromAnotherTableIsNeverWrittenUnderThatUid(): void
    {
        $this->expectFetch('hotel-kind-changed.json');

        $this->importConfiguration(1);

        $unrelatedTranslation = $this->fetchTranslationsByParent('tx_thuecat_tourist_attraction', 6)[1];
        self::assertSame(7, $unrelatedTranslation['uid']);
        self::assertSame('https://thuecat.org/resources/unrelated-attraction', $unrelatedTranslation['remote_id']);
        self::assertSame("Children's traffic area", $unrelatedTranslation['title']);

        $hotelUid = $this->fetchUidByRemoteId('tx_thuecat_tourist_attraction', self::HOTEL);
        self::assertGreaterThan(0, $hotelUid);
        self::assertNotSame(7, $hotelUid);

        $restaurant = $this->fetchRowByRemoteId('tx_thuecat_tourist_attraction', self::RESTAURANT);
        self::assertSame((string)$hotelUid, $restaurant['contained_in_attraction']);
        self::assertSame('', $restaurant['contained_in_organisation']);
    }

    #[Test]
    public function translationOfRecordKnownFromAnotherTableFollowsItsOwnRow(): void
    {
        $this->expectFetchForUrl(self::HOTEL, 'thuecat.org/resources/hotel-kind-changed-translated.json');

        $this->importConfiguration(1);

        $hotelUid = $this->fetchUidByRemoteId('tx_thuecat_tourist_attraction', self::HOTEL);
        self::assertGreaterThan(0, $hotelUid);
        self::assertSame(
            'Hotel of a changed kind',
            $this->fetchTranslationsByParent('tx_thuecat_tourist_attraction', $hotelUid)[1]['title'] ?? null
        );
        self::assertSame(
            "Children's traffic area",
            $this->fetchTranslationsByParent('tx_thuecat_tourist_attraction', 6)[1]['title']
        );
    }

    #[Test]
    public function tableMismatchOfARepairedOrganisationIsReportedOnce(): void
    {
        $this->expectFetch('hotel-kind-changed.json');

        $this->importConfiguration(1);

        $hotelUid = $this->fetchUidByRemoteId('tx_thuecat_tourist_attraction', self::HOTEL);
        $entries = $this->getLogEntriesOfType('recordReclassification');
        self::assertCount(1, $entries);
        self::assertSame('warning', $entries[0]['severity']);
        self::assertSame(self::HOTEL, $entries[0]['remote_id']);
        self::assertSame('tx_thuecat_tourist_attraction', $entries[0]['table_name']);
        self::assertIsString($entries[0]['context']);
        self::assertSame(
            [
                'previousTable' => 'tx_thuecat_organisation',
                'previousUid' => 7,
                'table' => 'tx_thuecat_tourist_attraction',
                'uid' => $hotelUid,
                'moved' => [],
                'droppedManagedBy' => [],
                'droppedManagedEvents' => [],
            ],
            json_decode($entries[0]['context'], true)
        );
    }
}
