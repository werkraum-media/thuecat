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
 * Each fixture is a real payload trimmed to the room properties under test. Rooms are reached through the venue
 * that names them, so every test imports a venue.
 */
final class MeetingRoomImportTest extends AbstractImportTestCase
{
    private const TABLE = 'tx_thuecat_meeting_room';

    private const VENUE = 'https://thuecat.org/resources/059632237210-rbpe';

    private const GALERIE = 'https://thuecat.org/resources/019950652406-nywx';

    /**
     * @return iterable<string, array{int, string, string, string, array<string, string|int>}>
     */
    public static function roomsWithTheirProperties(): iterable
    {
        yield 'Galerie' => [1, self::VENUE, 'meeting-venue-with-room.json', '019950652406-nywx', [
            'title' => 'Galerie',
            'floor_size' => '382.00',
            'room_height' => '11.50',
            'capacity_banquet' => 160,
            'capacity_classroom' => 160,
            'capacity_theatre' => 160,
            'capacity_standing' => 160,
            'facilities' => 'MeetingFacilitysRoomDaylight,AirConditioning',
            'bookable' => 'true',
            'url' => 'https://www.stadtwerke-erfurt.de/pb/die_swe/die+swe/fuer+erfurt/veranstaltungsraeume+bei+der+swe',
        ]];
        yield 'Kaminsaal' => [8, 'https://thuecat.org/resources/121522223838-onak', 'meeting-venue-with-numbered-room.json', '685195632502-ffxz', [
            'floor_size' => '115.00',
            'room_height' => '3.30',
            'capacity_banquet' => 50,
            'capacity_classroom' => 0,
            'capacity_theatre' => 50,
            'capacity_standing' => 0,
            'facilities' => 'MeetingFacilitysRoomDaylight,Separable,Wardrobe,Full,UsableOutdoorArea',
            'number_of_rooms' => 3,
            'amenity_features' => 'WC',
        ]];
        yield 'Rittersaal' => [9, 'https://thuecat.org/resources/819437258593-jnec', 'meeting-venue-with-bookable-room.json', '561915229386-dtxm', [
            'floor_size' => '60.00',
            'room_height' => '0.00',
            'bookable' => '',
            'tour_booking_page' => 'https://burg-liebstedt.de/mieten/rittersaal/',
        ]];
    }

    /**
     * @param array<string, string|int> $expected
     */
    #[Test]
    #[DataProvider('roomsWithTheirProperties')]
    public function storesEveryDeliveredRoomProperty(
        int $configuration,
        string $venue,
        string $venueFixture,
        string $room,
        array $expected
    ): void {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsMeetingRooms.php');
        $this->expectFetchForUrl($venue, 'thuecat.org/resources/' . $venueFixture);
        $this->expectFetch($room . '.json');

        $this->importConfiguration($configuration);

        $this->assertSame($expected, $this->fetchColumnsByRemoteId(self::TABLE, 'https://thuecat.org/resources/' . $room, array_keys($expected)));
    }

    #[Test]
    public function storesTitleAndDescriptionPerLanguage(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsMeetingRooms.php');
        $this->expectFetchForUrl(self::VENUE, 'thuecat.org/resources/meeting-venue-with-room.json');
        $this->expectFetch('019950652406-nywx.json');

        $this->importConfiguration(1);

        $row = $this->fetchRowByRemoteId(self::TABLE, self::GALERIE);
        $translations = $this->fetchTranslationsByParentRemoteId(self::TABLE, self::GALERIE);
        $this->assertIsString($row['description']);
        $this->assertStringStartsWith('Die Galerie ist eine Veranstaltungsfläche', $row['description']);
        $this->assertSame('Gallery', $translations[1]['title'] ?? null);
        $this->assertIsString($translations[1]['description'] ?? null);
        $this->assertStringStartsWith('The gallery is an event space', $translations[1]['description']);
        $this->assertSame('Galerie', $translations[2]['title'] ?? null);
    }

    #[Test]
    public function roomIsRelatedToItsTypeCategory(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsMeetingRooms.php');
        $this->expectFetchForUrl(self::VENUE, 'thuecat.org/resources/meeting-venue-with-room.json');
        $this->expectFetch('019950652406-nywx.json');

        $this->importConfiguration(1);

        $this->assertContains($this->fetchUidByRemoteId('sys_category', 'type:thuecat:MajorEventRoom'), $this->fetchMmRelatedCategoryUids(self::TABLE, $this->fetchUidByRemoteId(self::TABLE, self::GALERIE), 'categories'));
    }

    // A room is fetched as a reference, so its organisation relates only when already stored.
    #[Test]
    public function roomIsRelatedToItsKeywordAndManagingOrganisation(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsMeetingRoomWithKeyword.php');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Import/OrganisationManagingMeetingRoom.csv');
        $this->expectFetchForUrl(self::VENUE, 'thuecat.org/resources/meeting-venue-with-room.json');
        $this->expectFetchForUrl(self::GALERIE, 'thuecat.org/resources/meeting-room-with-keyword-and-organisation.json');
        $this->expectFetch('537010241706-gncn.json');
        $this->expectFetch('733143604162-wfob.json');

        $this->importConfiguration(1);

        $room = $this->fetchRowByRemoteId(self::TABLE, self::GALERIE);
        $this->assertSame(1, $room['managed_by']);
        $this->assertContains($this->fetchUidByRemoteId('sys_category', 'keyword:https://thuecat.org/resources/537010241706-gncn'), $this->fetchMmRelatedCategoryUids(self::TABLE, $this->fetchUidByRemoteId(self::TABLE, self::GALERIE), 'keywords'));
    }

    // DEFENSIVE: not observed upstream.
    #[Test]
    public function quantityWithUnknownUnitIsDroppedAndLogged(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsMeetingRooms.php');
        $this->expectFetchForUrl(self::VENUE, 'thuecat.org/resources/meeting-venue-with-room.json');
        $this->expectFetchForUrl(self::GALERIE, 'thuecat.org/resources/meeting-room-with-unknown-unit.json');

        $this->importConfiguration(1);

        $this->assertSame(['floor_size' => '0.00', 'room_height' => '11.50'], $this->fetchColumnsByRemoteId(self::TABLE, self::GALERIE, ['floor_size', 'room_height']));
        $this->assertSingleUnmatchedValue(self::TABLE, self::GALERIE, 'schema:floorSize', 'test:UnknownUnit');
    }

    // DEFENSIVE: not observed upstream.
    #[Test]
    public function floorPlanThatIsNoMediaIsLoggedAndSkipped(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsMeetingRooms.php');
        $this->expectFetchForUrl(self::VENUE, 'thuecat.org/resources/meeting-venue-with-room.json');
        $this->expectFetchForUrl(self::GALERIE, 'thuecat.org/resources/meeting-room-with-url-floor-plan.json');

        $this->importConfiguration(1);

        $this->assertSame(0, $this->fetchRowByRemoteId(self::TABLE, self::GALERIE)['floor_plan']);
        $this->assertSingleUnmatchedValue(self::TABLE, self::GALERIE, 'schema:accommodationFloorPlan', 'https://example.com/test-floor-plan.pdf');
    }

    #[Test]
    public function reimportCreatesNoAdditionalRoom(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsMeetingRooms.php');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Import/ExistingMeetingRoom.csv');
        $this->expectFetchForUrl(self::VENUE, 'thuecat.org/resources/meeting-venue-with-room.json');
        $this->expectFetch('019950652406-nywx.json');

        $this->importConfiguration(1);

        $this->assertSame([[1, 160]], array_map(
            static fn (array $room): array => [$room['uid'], $room['capacity_banquet']],
            $this->fetchChildren(self::TABLE)
        ));
    }

    #[Test]
    public function siteKeepsItsOwnRoomApartFromAnotherSite(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsMeetingRooms.php');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Import/MeetingRoomInOtherSite.csv');
        $this->expectFetchForUrl(self::VENUE, 'thuecat.org/resources/meeting-venue-with-room.json');
        $this->expectFetch('019950652406-nywx.json');

        $this->importConfiguration(1);

        $this->assertSame([[7, 50, 'Galerie of the second site'], [8, 10, 'Galerie']], array_map(
            static fn (array $room): array => [$room['uid'], $room['pid'], $room['title']],
            $this->fetchChildren(self::TABLE)
        ));
    }

    #[Test]
    public function roomNamedByItsVenueAndByAnotherRoomIsStoredOnce(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsMeetingRooms.php');
        $this->expectMeetingVenueWithRooms();

        $this->importConfiguration(2);

        $tripleRoom = 'https://thuecat.org/resources/853365081548-bobo';
        $this->assertCount(1, array_filter(
            $this->fetchChildren(self::TABLE),
            static fn (array $room): bool => $room['remote_id'] === $tripleRoom
        ));
        $uid = $this->fetchUidByRemoteId(self::TABLE, $tripleRoom);
        $this->assertContains($uid, $this->fetchMmRelatedUids(
            'tx_thuecat_tourist_attraction',
            $this->fetchUidByRemoteId('tx_thuecat_tourist_attraction', self::MEETING_VENUE_WITH_ROOMS),
            'meeting_rooms'
        ));
        $this->assertContains($uid, $this->fetchMmRelatedUids(
            self::TABLE,
            $this->fetchUidByRemoteId(self::TABLE, 'https://thuecat.org/resources/380984144868-tjdz'),
            'contained_rooms'
        ));
    }
}
