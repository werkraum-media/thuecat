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
 * Each fixture is a real payload trimmed to the parking facilities under test.
 */
final class ParkingFacilityImportTest extends AbstractImportTestCase
{
    private const TABLE = 'tx_thuecat_parking_facility';

    private const VENUE_TABLE = 'tx_thuecat_tourist_attraction';

    private const PARKING_FACILITY = 'https://thuecat.org/resources/109087118959-gwhg';

    private const MULTI_PURPOSE_HALL = 'https://thuecat.org/resources/160874095809-phhn';

    private const CULTURAL_ESTATE = 'https://thuecat.org/resources/332021174274-eyhf';

    private const WIELANDGUT = 'https://thuecat.org/resources/117034427781-ndta';

    private const HOTEL = 'https://thuecat.org/resources/e_ttg00020050000287335-tomas';

    private const SPACES = ['spaces_car', 'spaces_handicapped', 'spaces_bus', 'spaces_ev_charging', 'spaces_motorcycle'];

    #[Test]
    public function parkingFacilityStoresItsSpacesPerVehicleType(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsMeetingVenueParking.php');
        $this->expectFetch('109087118959-gwhg.json');

        $this->importConfiguration(5);

        self::assertSame(
            ['spaces_car' => 120, 'spaces_handicapped' => 0, 'spaces_bus' => 0, 'spaces_ev_charging' => 0, 'spaces_motorcycle' => 0],
            $this->fetchColumnsByRemoteId(self::TABLE, self::PARKING_FACILITY, self::SPACES)
        );
    }

    // Shared spaces count for every vehicle type they name.
    #[Test]
    public function spacesNamingTwoVehicleTypesCountForEach(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsMeetingVenueParking.php');
        $this->expectFetchForUrl(self::PARKING_FACILITY, 'thuecat.org/resources/parking-facility-with-shared-spaces.json');

        $this->importConfiguration(5);

        self::assertSame(
            ['spaces_car' => 5, 'spaces_handicapped' => 0, 'spaces_bus' => 0, 'spaces_ev_charging' => 0, 'spaces_motorcycle' => 5],
            $this->fetchColumnsByRemoteId(self::TABLE, self::PARKING_FACILITY, self::SPACES)
        );
    }

    // DEFENSIVE: not observed upstream.
    #[Test]
    public function unknownVehicleTypeIsDroppedAndLogged(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsMeetingVenueParking.php');
        $this->expectFetchForUrl(self::PARKING_FACILITY, 'thuecat.org/resources/parking-facility-with-unknown-vehicle-type.json');

        $this->importConfiguration(5);

        self::assertSame(120, $this->fetchRowByRemoteId(self::TABLE, self::PARKING_FACILITY)['spaces_car']);

        $this->assertSingleUnmatchedValue(self::TABLE, self::PARKING_FACILITY, 'thuecat:numberOfParkingSpace', 'test:UnknownVehicle');
    }

    #[Test]
    public function venueParkingFacilityIsStoredAsInlineChild(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsMeetingVenueParking.php');
        $this->expectFetch('160874095809-phhn.json');

        $this->importConfiguration(1);

        self::assertSame([self::MULTI_PURPOSE_HALL . '::parking::0'], $this->fetchChildRemoteIds(self::TABLE));
        $expected = [
            'title' => 'Parkplatz am Haupteingang',
            'parentid' => $this->fetchUidByRemoteId(self::VENUE_TABLE, self::MULTI_PURPOSE_HALL),
            'parenttable' => self::VENUE_TABLE,
            'spaces_car' => 6,
            'spaces_handicapped' => 2,
            'spaces_bus' => 0,
            'spaces_ev_charging' => 1,
            'spaces_motorcycle' => 0,
        ];
        self::assertSame(
            $expected,
            $this->fetchColumnsByRemoteId(self::TABLE, self::MULTI_PURPOSE_HALL . '::parking::0', array_keys($expected))
        );
    }

    #[Test]
    public function venueParkingFacilityKeepsItsDescription(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsMeetingVenueParking.php');
        $this->expectFetchForUrl(self::CULTURAL_ESTATE, 'thuecat.org/resources/meeting-venue-with-described-parking-facility.json');

        $this->importConfiguration(2);

        self::assertSame(
            'Parkplätze an der Kirche können ebenfalls genutzt werden.',
            $this->fetchRowByRemoteId(self::TABLE, self::CULTURAL_ESTATE . '::parking::0')['description']
        );
    }

    #[Test]
    public function venueParkingFacilityIsTranslated(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsMeetingVenueParking.php');
        $this->expectFetchForUrl(self::WIELANDGUT, 'thuecat.org/resources/meeting-venue-with-translated-parking-facility.json');

        $this->importConfiguration(3);

        $translations = $this->fetchTranslationsByParentRemoteId(self::TABLE, self::WIELANDGUT . '::parking::0');
        self::assertSame('Parking', $translations[1]['title'] ?? null);
        self::assertSame('Parking', $translations[2]['title'] ?? null);
    }

    // DEFENSIVE: not observed upstream.
    #[Test]
    public function venueParkingFacilityKeepsItsOpeningHours(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsMeetingVenueParking.php');
        $this->expectFetchForUrl(self::MULTI_PURPOSE_HALL, 'thuecat.org/resources/meeting-venue-with-parking-facility-opening-hours.json');

        $this->importConfiguration(1);

        $parkingFacility = self::MULTI_PURPOSE_HALL . '::parking::0';
        self::assertSame(
            ['parentid' => $this->fetchUidByRemoteId(self::TABLE, $parkingFacility), 'parenttable' => self::TABLE],
            $this->fetchColumnsByRemoteId(
                'tx_thuecat_opening_hours',
                $parkingFacility . '::oh::regular::Monday::00:00:00::2026-10-06::2029-03-20',
                ['parentid', 'parenttable']
            )
        );
    }

    #[Test]
    public function reimportUpdatesTheInlineParkingFacility(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsMeetingVenueParking.php');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Import/ExistingMeetingVenueWithParkingFacility.csv');
        $this->expectFetch('160874095809-phhn.json');

        $this->importConfiguration(1);

        $parkingFacilities = $this->fetchChildren(self::TABLE);
        self::assertCount(1, $parkingFacilities);
        self::assertSame(1, $parkingFacilities[0]['uid']);
        self::assertSame(2, $parkingFacilities[0]['spaces_handicapped']);
    }

    #[Test]
    public function droppedOneOfTwoParkingFacilitiesIsDeleted(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsMeetingVenueParking.php');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Import/ExistingMeetingVenueWithTwoParkingFacilities.csv');
        $this->expectFetchForUrl(self::HOTEL, 'thuecat.org/resources/meeting-venue-with-one-of-two-parking-facilities.json');

        $this->importConfiguration(4);

        self::assertSame(
            [self::HOTEL . '::parking::0'],
            $this->fetchChildRemoteIds(self::TABLE)
        );
    }

    #[Test]
    public function allDroppedParkingFacilitiesAreDeleted(): void
    {
        self::markTestSkipped('Inline reaping never clears an empty set; see resolve-relation-reap-gap.');

        // @phpstan-ignore-next-line deadCode.unreachable
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsMeetingVenueParking.php');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Import/ExistingMeetingVenueWithTwoParkingFacilities.csv');
        $this->expectFetchForUrl(self::HOTEL, 'thuecat.org/resources/meeting-venue-without-parking-facilities.json');

        $this->importConfiguration(4);

        self::assertSame([], $this->fetchChildren(self::TABLE));
    }
}
