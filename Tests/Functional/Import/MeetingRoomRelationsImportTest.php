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
 * Venue→room and room→room relations. The fixtures are real payloads trimmed to the rooms they name. The faker
 * fails a test on any fetch it was not told to expect, which is what proves a room was not fetched.
 */
final class MeetingRoomRelationsImportTest extends AbstractImportTestCase
{
    private const string TABLE = 'tx_thuecat_meeting_room';

    private const string VENUE_TABLE = 'tx_thuecat_tourist_attraction';

    private const string RESOURCES = 'https://thuecat.org/resources/';

    #[Test]
    public function venueRelatesItsRoomsInUpstreamOrder(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsMeetingRooms.php');
        $this->expectMeetingVenueWithRooms();

        $this->importConfiguration(2);

        $this->assertSame($this->meetingVenueRoomRemoteIds(), $this->fetchMmRelatedRemoteIds(self::VENUE_TABLE, self::MEETING_VENUE_WITH_ROOMS, 'meeting_rooms'));
    }

    #[Test]
    public function placeThatIsNoRoomIsNeitherRelatedNorImported(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsMeetingRooms.php');
        $this->expectFetchForUrl(self::RESOURCES . 'r_22033376-oapoi', 'thuecat.org/resources/meeting-venue-with-room-and-attraction.json');
        $this->expectFetch('394356958814-mhch.json');
        $this->expectFetch('201162676710-tagb.json');

        $this->importConfiguration(3);

        $this->assertSame([self::RESOURCES . '201162676710-tagb'], $this->fetchMmRelatedRemoteIds(self::VENUE_TABLE, self::RESOURCES . 'r_22033376-oapoi', 'meeting_rooms'));
        $this->assertNotContains(self::RESOURCES . '394356958814-mhch', $this->fetchChildRemoteIds(self::VENUE_TABLE));
        $this->assertSame([], $this->getSkippedReferenceLogEntries());
    }

    #[Test]
    public function venuesImportedTogetherRelateOnlyTheirOwnRooms(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsMeetingRooms.php');
        $this->expectFetchForUrl(self::RESOURCES . '059632237210-rbpe', 'thuecat.org/resources/meeting-venue-with-room.json');
        $this->expectFetch('019950652406-nywx.json');
        $this->expectFetchForUrl(self::RESOURCES . 'r_22033376-oapoi', 'thuecat.org/resources/meeting-venue-with-room-and-attraction.json');
        $this->expectFetch('394356958814-mhch.json');
        $this->expectFetch('201162676710-tagb.json');

        $this->importConfiguration(4);

        $this->assertSame([
            '059632237210-rbpe' => [self::RESOURCES . '019950652406-nywx'],
            'r_22033376-oapoi' => [self::RESOURCES . '201162676710-tagb'],
        ], [
            '059632237210-rbpe' => $this->fetchMmRelatedRemoteIds(self::VENUE_TABLE, self::RESOURCES . '059632237210-rbpe', 'meeting_rooms'),
            'r_22033376-oapoi' => $this->fetchMmRelatedRemoteIds(self::VENUE_TABLE, self::RESOURCES . 'r_22033376-oapoi', 'meeting_rooms'),
        ]);
    }

    // The direction stays as delivered.
    #[Test]
    public function roomRelatesTheRoomsItNamesAsDelivered(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsMeetingRooms.php');
        $this->expectMeetingVenueWithRooms();

        $this->importConfiguration(2);

        $this->assertSame([self::RESOURCES . '541146409582-zdzo', self::RESOURCES . '853365081548-bobo'], $this->fetchMmRelatedRemoteIds(self::TABLE, self::RESOURCES . '380984144868-tjdz', 'contained_rooms'));
    }

    // DEFENSIVE: not observed upstream.
    #[Test]
    public function roomsReachedOnlyThroughAnotherRoomAreImportedDownToTheSubDivision(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsMeetingRooms.php');
        $this->expectFetchForUrl(self::RESOURCES . 'e_ttg00020050000287335-tomas', 'thuecat.org/resources/meeting-venue-with-divided-room.json');
        $this->expectFetch('199366862325-pbyc.json');
        $this->expectFetch('757717628971-kyro.json');
        $this->expectFetch('737866811554-cmed.json');

        $this->importConfiguration(5);

        $this->assertSame([
            'division' => [self::RESOURCES . '757717628971-kyro'],
            'sub-division' => [self::RESOURCES . '737866811554-cmed'],
        ], [
            'division' => $this->fetchMmRelatedRemoteIds(self::TABLE, self::RESOURCES . '199366862325-pbyc', 'contained_rooms'),
            'sub-division' => $this->fetchMmRelatedRemoteIds(self::TABLE, self::RESOURCES . '757717628971-kyro', 'contained_rooms'),
        ]);
    }

    // DEFENSIVE: not observed upstream.
    #[Test]
    public function roomBeyondTheBoundIsNotFetched(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsMeetingRooms.php');
        $this->expectFetchForUrl(self::RESOURCES . 'e_ttg00020050000287335-tomas', 'thuecat.org/resources/meeting-venue-with-divided-room.json');
        $this->expectFetch('199366862325-pbyc.json');
        $this->expectFetch('757717628971-kyro.json');
        $this->expectFetchForUrl(self::RESOURCES . '737866811554-cmed', 'thuecat.org/resources/meeting-room-divided-beyond-the-bound.json');

        $this->importConfiguration(5);

        $this->assertSame([], $this->fetchMmRelatedRemoteIds(self::TABLE, self::RESOURCES . '737866811554-cmed', 'contained_rooms'));
        $this->assertNotContains(self::RESOURCES . '452821338011-thxk', $this->fetchChildRemoteIds(self::TABLE));
    }

    // DEFENSIVE: not observed upstream.
    #[Test]
    public function roomsNamingEachOtherAreImportedOnceEach(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsMeetingRooms.php');
        $this->expectFetchForUrl(self::RESOURCES . '234669135473-wkez', 'thuecat.org/resources/meeting-venue-with-combined-room.json');
        $this->expectFetch('927752209912-nrho.json');
        $this->expectFetchForUrl(self::RESOURCES . '279158168683-ypnr', 'thuecat.org/resources/meeting-room-naming-its-combination.json');
        $this->expectFetch('113066118073-geqe.json');

        $this->importConfiguration(6);

        $this->assertCount(3, $this->fetchChildren(self::TABLE));
        $this->assertSame([
            'combination' => [self::RESOURCES . '279158168683-ypnr', self::RESOURCES . '113066118073-geqe'],
            'part' => [self::RESOURCES . '927752209912-nrho'],
        ], [
            'combination' => $this->fetchMmRelatedRemoteIds(self::TABLE, self::RESOURCES . '927752209912-nrho', 'contained_rooms'),
            'part' => $this->fetchMmRelatedRemoteIds(self::TABLE, self::RESOURCES . '279158168683-ypnr', 'contained_rooms'),
        ]);
    }

    #[Test]
    public function venueReachedAsReferenceFetchesNoNewRooms(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsMeetingRooms.php');
        $this->expectFetch('771285888381-bqfq.json');
        $this->expectFetchForUrl(self::RESOURCES . 'e_ttg00020050000285951-tomas', 'thuecat.org/resources/meeting-venue-reached-as-reference.json');

        $this->importConfiguration(7);

        $this->assertSame('meeting_venue', $this->fetchRowByRemoteId(self::VENUE_TABLE, self::RESOURCES . 'e_ttg00020050000285951-tomas')['type']);
        $this->assertSame([], $this->fetchChildren(self::TABLE));
    }

    #[Test]
    public function droppedRoomIsUnrelated(): void
    {
        $this->markTestSkipped('Reference writes never clear a dropped reference; see resolve-relation-reap-gap.');

        // @phpstan-ignore-next-line deadCode.unreachable
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsMeetingRooms.php');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Import/ExistingMeetingVenueWithRooms.csv');
        $this->expectFetchForUrl(self::RESOURCES . '669150457039-gamn', 'thuecat.org/resources/meeting-venue-with-one-of-its-rooms.json');
        $this->expectFetch('380984144868-tjdz.json');

        $this->importConfiguration(2);

        $this->assertSame([self::RESOURCES . '380984144868-tjdz'], $this->fetchMmRelatedRemoteIds(self::VENUE_TABLE, self::RESOURCES . '669150457039-gamn', 'meeting_rooms'));
    }

    #[Test]
    public function allDroppedRoomsAreUnrelated(): void
    {
        $this->markTestSkipped('Reference writes never clear an empty set; see resolve-relation-reap-gap.');

        // @phpstan-ignore-next-line deadCode.unreachable
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsMeetingRooms.php');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Import/ExistingMeetingVenueWithRooms.csv');
        $this->expectFetchForUrl(self::RESOURCES . '669150457039-gamn', 'thuecat.org/resources/meeting-venue-without-rooms.json');

        $this->importConfiguration(2);

        $this->assertSame([], $this->fetchMmRelatedRemoteIds(self::VENUE_TABLE, self::RESOURCES . '669150457039-gamn', 'meeting_rooms'));
    }

    #[Test]
    public function droppedRelatedRoomIsUnrelated(): void
    {
        $this->markTestSkipped('Reference writes never clear a dropped reference; see resolve-relation-reap-gap.');

        // @phpstan-ignore-next-line deadCode.unreachable
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsMeetingRooms.php');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Import/ExistingMeetingVenueWithRooms.csv');
        $this->expectFetchForUrl(self::RESOURCES . '669150457039-gamn', 'thuecat.org/resources/meeting-venue-with-one-of-its-rooms.json');
        $this->expectFetchForUrl(self::RESOURCES . '380984144868-tjdz', 'thuecat.org/resources/meeting-room-naming-one-combination.json');
        $this->expectFetch('541146409582-zdzo.json');

        $this->importConfiguration(2);

        $this->assertSame([self::RESOURCES . '541146409582-zdzo'], $this->fetchMmRelatedRemoteIds(self::TABLE, self::RESOURCES . '380984144868-tjdz', 'contained_rooms'));
    }

    #[Test]
    public function allDroppedRelatedRoomsAreUnrelated(): void
    {
        $this->markTestSkipped('Reference writes never clear an empty set; see resolve-relation-reap-gap.');

        // @phpstan-ignore-next-line deadCode.unreachable
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsMeetingRooms.php');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Import/ExistingMeetingVenueWithRooms.csv');
        $this->expectFetchForUrl(self::RESOURCES . '669150457039-gamn', 'thuecat.org/resources/meeting-venue-with-one-of-its-rooms.json');
        $this->expectFetchForUrl(self::RESOURCES . '380984144868-tjdz', 'thuecat.org/resources/meeting-room-naming-no-combination.json');

        $this->importConfiguration(2);

        $this->assertSame([], $this->fetchMmRelatedRemoteIds(self::TABLE, self::RESOURCES . '380984144868-tjdz', 'contained_rooms'));
    }
}
