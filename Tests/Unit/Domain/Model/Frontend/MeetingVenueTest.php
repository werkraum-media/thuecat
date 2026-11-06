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

namespace WerkraumMedia\ThueCat\Tests\Unit\Domain\Model\Frontend;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\MeetingRoom;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\MeetingVenue;

class MeetingVenueTest extends TestCase
{
    public static function enumLists(): iterable
    {
        yield 'meeting facilities' => ['meetingFacilities', 'getMeetingFacilityMembers', 'Beamer,Flipchart', ['Beamer', 'Flipchart']];
        yield 'gastro' => ['gastro', 'getGastroMembers', 'Restaurant', ['Restaurant']];
        yield 'serves cuisine' => ['servesCuisine', 'getServesCuisineMembers', 'RegionalCuisine,GermanCuisine', ['RegionalCuisine', 'GermanCuisine']];
        yield 'entrance' => ['entrance', 'getEntranceMembers', 'MainEntrance', ['MainEntrance']];
        yield 'guided tour' => ['guidedTour', 'getGuidedTourMembers', 'GuidedTourOnlyWithRegistration,GuidedTourForGroups', ['GuidedTourOnlyWithRegistration', 'GuidedTourForGroups']];
        yield 'monument' => ['monument', 'getMonumentMembers', 'ArchitecturalMonument', ['ArchitecturalMonument']];
        yield 'ensemble' => ['ensemble', 'getEnsembleMembers', 'CastleEnsemble', ['CastleEnsemble']];
        yield 'exhibition objects' => ['exhibitionObjects', 'getExhibitionObjectMembers', 'Paintings,Sculptures', ['Paintings', 'Sculptures']];
        yield 'sports equipment' => ['sportsEquipment', 'getSportsEquipmentMembers', 'BicycleRental', ['BicycleRental']];
        yield 'amenity features' => ['amenityFeatures', 'getAmenityFeatureMembers', 'Sauna,Elevator', ['Sauna', 'Elevator']];
    }

    /**
     * @param non-empty-string $property
     * @param list<string> $expected
     */
    #[Test]
    #[DataProvider('enumLists')]
    public function returnsEnumListMembers(string $property, string $getter, string $stored, array $expected): void
    {
        $subject = new MeetingVenue();
        $subject->_setProperty($property, $stored);

        $this->assertSame($expected, $subject->{$getter}());
    }

    #[Test]
    public function returnsNoEnumListMembersWhenNothingIsStored(): void
    {
        $this->assertSame([], (new MeetingVenue())->getMeetingFacilityMembers());
    }

    #[Test]
    public function returnsDeliveredDistancesInKilometresFromOneKilometreOn(): void
    {
        $subject = new MeetingVenue();
        $subject->_setProperty('distanceAirport', 12500);
        $subject->_setProperty('distanceCityCenter', 800);
        $subject->_setProperty('distanceFair', 1000);
        $subject->_setProperty('distanceFreeway', 0);
        $subject->_setProperty('distanceIceStation', 999);

        $this->assertSame([
            'airport' => ['value' => 12.5, 'unit' => 'km'],
            'cityCenter' => ['value' => 800, 'unit' => 'm'],
            'fair' => ['value' => 1.0, 'unit' => 'km'],
            'iceStation' => ['value' => 999, 'unit' => 'm'],
        ], $subject->getDistances());
    }

    public static function additionalTypes(): iterable
    {
        yield 'conference hotel' => ['meeting_congress_hotel', 'https://thuecat.org/ontology/thuecat/1.0/MeetingCongressHotel'];
        yield 'meeting and congress' => ['meeting_congress', 'https://thuecat.org/ontology/thuecat/1.0/MeetingCongress'];
        yield 'no venue type' => ['', ''];
    }

    #[Test]
    #[DataProvider('additionalTypes')]
    public function returnsTheThueCatTypeOfItsVenueTypeAsAdditionalType(string $venueType, string $expected): void
    {
        $subject = new MeetingVenue();
        $subject->_setProperty('venueType', $venueType);

        $this->assertSame($expected, $subject->getAdditionalType());
    }

    public static function meetingRoomTables(): iterable
    {
        yield 'venue without rooms' => [[], [], []];
        yield 'venue order' => [[3, 1, 2], [], [3, 1, 2]];
        yield 'combined room named by the venue too keeps the venue order' => [[1, 3, 2], [1 => [2]], [1, 3, 2]];
        yield 'room reached only through another room follows it' => [[1, 3], [1 => [2]], [1, 2, 3]];
        yield 'depth first' => [[1, 4], [1 => [2], 2 => [3]], [1, 2, 3, 4]];
        yield 'room reached through two rooms is listed after the first' => [[1, 2], [1 => [3], 2 => [3]], [1, 3, 2]];
        yield 'rooms naming each other' => [[1], [1 => [2], 2 => [1]], [1, 2]];
    }

    /**
     * @param list<int> $venueRoomUids
     * @param array<int, list<int>> $containedRoomUids
     * @param list<int> $expectedUids
     */
    #[Test]
    #[DataProvider('meetingRoomTables')]
    public function listsEveryMeetingRoomOnce(array $venueRoomUids, array $containedRoomUids, array $expectedUids): void
    {
        $roomUids = array_unique([...$venueRoomUids, ...array_keys($containedRoomUids), ...array_merge([], ...$containedRoomUids)]);
        $rooms = [];
        foreach ($roomUids as $uid) {
            $rooms[$uid] = new MeetingRoom();
            $rooms[$uid]->_setProperty('uid', $uid);
            $rooms[$uid]->_setProperty('containedRooms', new ObjectStorage());
        }
        foreach ($containedRoomUids as $uid => $containedUids) {
            foreach ($containedUids as $containedUid) {
                $rooms[$uid]->getContainedRooms()->attach($rooms[$containedUid]);
            }
        }
        $venueRooms = new ObjectStorage();
        foreach ($venueRoomUids as $uid) {
            $venueRooms->attach($rooms[$uid]);
        }
        $subject = new MeetingVenue();
        $subject->_setProperty('meetingRooms', $venueRooms);

        $this->assertSame($expectedUids, array_map(static fn (MeetingRoom $room): ?int => $room->getUid(), $subject->getMeetingRoomTable()));
    }
}
