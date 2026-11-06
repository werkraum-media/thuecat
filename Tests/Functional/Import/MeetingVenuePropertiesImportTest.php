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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractImportTestCase;

/**
 * Each fixture is a real venue payload trimmed to the properties under test.
 */
final class MeetingVenuePropertiesImportTest extends AbstractImportTestCase
{
    private const TABLE = 'tx_thuecat_tourist_attraction';

    private const HOTEL = 'https://thuecat.org/resources/669150457039-gamn';

    private const MEETING_LOCATION = 'https://thuecat.org/resources/059632237210-rbpe';

    private const HOTEL_WITH_GASTRONOMY = 'https://thuecat.org/resources/554408963544-zftr';

    private const MEMORIAL = 'https://thuecat.org/resources/838757899629-mqjw';

    private const VILLA = 'https://thuecat.org/resources/447107981156-qmxj';

    private const VENUE_COLUMNS = [
        'meeting_facilities', 'gastro', 'serves_cuisine', 'entrance', 'guided_tour', 'monument', 'ensemble',
        'exhibition_objects', 'sports_equipment', 'amenity_features', 'price_range', 'star_rating',
        'smoking_allowed', 'is_ruin', 'open_air_museum', 'bookable', 'has_drive_through_service', 'beer_garden',
        'catering', 'delivery_service', 'hot_cuisine_throughout', 'late_kitchen', 'self_service', 'take_away',
        'accepts_reservations', 'maximum_attendee_capacity', 'number_of_rooms', 'single_rooms', 'double_rooms',
        'twin_rooms', 'suites', 'seats_inside', 'seats_outside', 'distance_airport', 'distance_city_center',
        'distance_fair', 'distance_freeway', 'distance_ice_station', 'size_of_area', 'typical_age_range',
        'end_of_construction', 'certifications', 'checkin_time', 'checkout_time', 'has_map', 'tour_booking_page',
        'identifier', 'same_as', 'description_culture_portal', 'description_lodging_meeting',
    ];

    /**
     * @return iterable<string, array{int, string, array<string, string|int>}>
     */
    public static function venuesWithTheirProperties(): iterable
    {
        yield 'conference hotel' => [1, self::HOTEL, [
            'meeting_facilities' => 'MeetingHouseCanvas,MeetingHouseLectern,MeetingHouseScreenTV,MeetingHouseSoundSystem,MeetingHouseProjector,MeetingHouseWLAN,MeetingHouseTechnicalStaff,MeetingHouseFlipchart,Multifunctional',
            'gastro' => 'Restaurant,BreakfastService,BarEnumMem',
            'entrance' => 'IndoorActivities',
            'amenity_features' => 'SaunaEnuMem',
            'smoking_allowed' => 'false',
            'has_drive_through_service' => 'false',
            'self_service' => 'false',
            'beer_garden' => 'false',
            'bookable' => 'true',
            'delivery_service' => 'false',
            'take_away' => 'false',
            'late_kitchen' => 'false',
            'hot_cuisine_throughout' => 'false',
            'catering' => 'true',
            'maximum_attendee_capacity' => 300,
            'number_of_rooms' => 160,
            'single_rooms' => 5,
            'double_rooms' => 147,
            'suites' => 8,
            'distance_airport' => 4500,
            'distance_city_center' => 900,
            'distance_fair' => 3200,
            'distance_freeway' => 6000,
            'distance_ice_station' => 1400,
            'checkin_time' => '16:00:00',
            'checkout_time' => '11:00:00',
            'has_map' => 'https://maps.app.goo.gl/LHMD5KZXXeWoP9HU7',
        ]];
        yield 'meeting location without hotel or gastronomy' => [3, self::MEETING_LOCATION, [
            'meeting_facilities' => 'MeetingHouseScreenTV',
            'gastro' => 'TakeawayShop',
            'smoking_allowed' => 'false',
            'bookable' => '',
            'catering' => '',
            'maximum_attendee_capacity' => 0,
            'distance_airport' => 0,
            'distance_city_center' => 2000,
            'distance_fair' => 6000,
            'same_as' => 'https://www.outdooractive.com/de/812638275',
        ]];
        yield 'hotel with gastronomy' => [4, self::HOTEL_WITH_GASTRONOMY, [
            'serves_cuisine' => 'RegionalCuisine,ThuringianCuisine,VegetarianCooking,VeganCooking',
            'star_rating' => 'ThreeStar',
            'price_range' => 'PriceRangeMiddle',
            'certifications' => 'Motorradfreundlicher Hotel- und Gastronomiebetrieb',
            'sports_equipment' => 'BicycleRental',
            'accepts_reservations' => 'true',
            'seats_inside' => 120,
            'twin_rooms' => 2,
            'amenity_features' => 'Terrace,ConferenceMeetingRooms',
        ]];
        yield 'museum hosting meetings' => [5, 'https://thuecat.org/resources/117034427781-ndta', [
            'end_of_construction' => '1762',
            'monument' => 'ZeroInformationMemorialClass',
            'exhibition_objects' => 'CulturalHistoryExhibition,MemorialsWithExhibition,LiteraryHistoryExhibition',
            'is_ruin' => 'false',
            'open_air_museum' => 'false',
            'guided_tour' => 'GuidedTourOnlyWithRegistration',
            'same_as' => 'https://www.outdooractive.com/de/56762049',
        ]];
        yield 'memorial hosting meetings' => [6, self::MEMORIAL, [
            'typical_age_range' => '12',
            'identifier' => 'https://www.thueringen-entdecken.de/urlaub-hotel-reisen/gedenk-und-bildungsstaette-andreasstrasse-141546.html',
        ]];
        yield 'park hosting meetings' => [8, 'https://thuecat.org/resources/r_22033376-oapoi', [
            'size_of_area' => 360000,
            'tour_booking_page' => 'https://egapark.ticketfritz.de/Shop',
            'identifier' => 'https://www.thueringen-entdecken.de/urlaub-hotel-reisen/egapark-104689.html',
        ]];
    }

    /**
     * @param array<string, string|int> $expected
     */
    #[Test]
    #[DataProvider('venuesWithTheirProperties')]
    public function storesEveryDeliveredVenueProperty(int $configuration, string $remoteId, array $expected): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsMeetingVenues.php');
        $this->expectFetch(basename($remoteId) . '.json');

        $this->importConfiguration($configuration);

        self::assertSame($expected, $this->fetchColumnsByRemoteId(self::TABLE, $remoteId, array_keys($expected)));
    }

    #[Test]
    public function storesCertificationNamesPerLanguage(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsMeetingVenues.php');
        $this->expectFetch('554408963544-zftr.json');

        $this->importConfiguration(4);

        $translations = $this->fetchTranslationsByParentRemoteId(self::TABLE, self::HOTEL_WITH_GASTRONOMY);
        self::assertSame('Motorcycle-Friendly Hotel and Restaurant', $translations[1]['certifications'] ?? null);
        self::assertSame('Établissement hôtelier et de restauration adapté aux motards', $translations[2]['certifications'] ?? null);
    }

    #[Test]
    public function plainAttractionStoresNoVenueProperty(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsMeetingVenues.php');
        $this->expectFetchForUrl(self::HOTEL, 'thuecat.org/resources/meeting-venue-without-meeting-type.json');

        $this->importConfiguration(1);

        self::assertSame([], array_filter($this->fetchColumnsByRemoteId(self::TABLE, self::HOTEL, self::VENUE_COLUMNS)));
    }

    #[Test]
    public function quantityWithUnknownUnitIsDroppedAndLogged(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsMeetingVenues.php');
        $this->expectFetchForUrl(self::MEETING_LOCATION, 'thuecat.org/resources/meeting-venue-with-unknown-unit.json');

        $this->importConfiguration(3);

        $row = $this->fetchRowByRemoteId(self::TABLE, self::MEETING_LOCATION);
        self::assertSame(0, $row['distance_fair']);
        self::assertSame(2000, $row['distance_city_center']);

        $this->assertSingleUnmatchedValue(self::TABLE, self::MEETING_LOCATION, 'thuecat:distanceFair', 'test:UnknownUnit');
    }

    #[Test]
    public function storesCulturePortalDescriptionPerLanguage(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsMeetingVenues.php');
        $this->expectFetch('838757899629-mqjw.json');

        $this->importConfiguration(6);

        $german = $this->fetchRowByRemoteId(self::TABLE, self::MEMORIAL)['description_culture_portal'];
        $english = $this->fetchTranslationsByParentRemoteId(self::TABLE, self::MEMORIAL)[1]['description_culture_portal'] ?? null;
        self::assertIsString($german);
        self::assertIsString($english);
        self::assertStringStartsWith('<p>Was die Gedenk- und Bildungsstätte Andreasstraße besonders macht:', $german);
        self::assertStringStartsWith('<p>What makes the Andreasstraße Memorial and Educational Center special', $english);
    }

    #[Test]
    public function storesLodgingMeetingDescriptionInItsOwnField(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsMeetingVenues.php');
        $this->expectFetch('447107981156-qmxj.json');

        $this->importConfiguration(7);

        $row = $this->fetchRowByRemoteId(self::TABLE, self::VILLA);
        self::assertIsString($row['description_lodging_meeting']);
        self::assertStringStartsWith('<p>Kommunikation und Konzentration:', $row['description_lodging_meeting']);
        self::assertEmpty($row['description_culture_portal']);
    }

    #[Test]
    public function descriptionOfUnknownTypeIsReportedUnmatched(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsMeetingVenues.php');
        $this->expectFetchForUrl(self::MEMORIAL, 'thuecat.org/resources/meeting-venue-with-unknown-description-type.json');

        $this->importConfiguration(6);

        $row = $this->fetchRowByRemoteId(self::TABLE, self::MEMORIAL);
        self::assertEmpty($row['description_culture_portal']);
        self::assertEmpty($row['description_lodging_meeting']);

        $this->assertSingleUnmatchedValue(self::TABLE, self::MEMORIAL, 'thuecat:additionalDescriptions', 'test:UnknownDescription');
    }
}
