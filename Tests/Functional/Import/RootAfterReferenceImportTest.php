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
 * A venue reached first as an earlier root's reference and then as a root of its own is resolved as a root. Each
 * venue is fetched once: the run's fetch cache serves its root turn.
 */
final class RootAfterReferenceImportTest extends AbstractImportTestCase
{
    private const TABLE = 'tx_thuecat_tourist_attraction';

    private const RESOURCES = 'https://thuecat.org/resources/';

    private const ATTRACTION = self::RESOURCES . 'attraction-in-meeting-venue';

    private const OPERATED_VENUE = self::RESOURCES . '117034427781-ndta';

    private const PARKING_VENUE = self::RESOURCES . '160874095809-phhn';

    #[Test]
    public function venueRelatesItsRoomsInUpstreamOrder(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsReferenceThenMeetingVenue.php');
        $this->expectFetch('attraction-in-meeting-venue.json');
        $this->expectMeetingVenueWithRooms();

        $this->importConfiguration(1);

        $this->assertSame($this->meetingVenueRoomRemoteIds(), $this->fetchMmRelatedRemoteIds(self::TABLE, self::MEETING_VENUE_WITH_ROOMS, 'meeting_rooms'));
    }

    #[Test]
    public function venueNewToTheSiteIsStoredOnceAndRelatedByTheReferencingRoot(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsReferenceThenMeetingVenue.php');
        $this->expectFetch('attraction-in-meeting-venue.json');
        $this->expectMeetingVenueWithRooms();

        $this->importConfiguration(1);

        $this->assertCount(1, array_keys($this->fetchLanguagesByRemoteId(self::TABLE, self::MEETING_VENUE_WITH_ROOMS, 10), 0, true));
        $this->assertSame((string)$this->fetchUidByRemoteId(self::TABLE, self::MEETING_VENUE_WITH_ROOMS), $this->fetchRowByRemoteId(self::TABLE, self::ATTRACTION)['contained_in_attraction']);
    }

    #[Test]
    public function storedVenueIsUpdatedInPlaceWithItsRooms(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsReferenceThenMeetingVenue.php');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Import/ExistingMeetingVenue.csv');
        $this->expectFetch('attraction-in-meeting-venue.json');
        $this->expectMeetingVenueWithRooms();

        $this->importConfiguration(1);

        $this->assertCount(1, array_keys($this->fetchLanguagesByRemoteId(self::TABLE, self::MEETING_VENUE_WITH_ROOMS, 10), 0, true));
        $this->assertSame('1', $this->fetchRowByRemoteId(self::TABLE, self::ATTRACTION)['contained_in_attraction']);
        $this->assertCount(count(self::MEETING_VENUE_ROOM_FIXTURES), $this->fetchMmRelatedUids(self::TABLE, 1, 'meeting_rooms'));
    }

    #[Test]
    public function operatorDroppedAtTheReferenceSightingIsRelated(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsReferenceThenMeetingVenue.php');
        $this->expectFetch('attraction-in-operated-meeting-venue.json');
        $this->expectFetchForUrl(self::OPERATED_VENUE, 'thuecat.org/resources/meeting-venue-operated-by-other-organisation.json');
        $this->expectFetch('079491439271-qqjm.json');
        $this->expectFetch('214681324608-rqzg.json');

        $this->importConfiguration(2);

        $operator = $this->fetchUidByRemoteId('tx_thuecat_organisation', self::RESOURCES . '214681324608-rqzg');
        $this->assertGreaterThan(0, $operator);
        $this->assertSame($operator, $this->fetchRowByRemoteId(self::TABLE, self::OPERATED_VENUE)['operated_by']);
    }

    #[Test]
    public function venueListedTwiceIsResolvedOnce(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsReferenceThenMeetingVenue.php');
        $this->expectFetch('attraction-in-meeting-venue.json');
        $this->expectMeetingVenueWithRooms();

        $this->importConfiguration(4);

        $this->assertCount(count(self::MEETING_VENUE_ROOM_FIXTURES), $this->fetchChildren('tx_thuecat_meeting_room'));
        $this->assertSame($this->meetingVenueRoomRemoteIds(), $this->fetchMmRelatedRemoteIds(self::TABLE, self::MEETING_VENUE_WITH_ROOMS, 'meeting_rooms'));
    }

    // The fetch cache would hide a repeat, so the run bypasses it: once as a reference, once as a root.
    #[Test]
    public function rerootedVenueReferencedAgainIsNotFetchedAgain(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsReferenceThenMeetingVenue.php');
        $this->expectFetch('attraction-in-meeting-venue.json');
        $this->expectFetchForUrl(self::MEETING_VENUE_WITH_ROOMS, 'thuecat.org/resources/meeting-venue-with-rooms.json');
        $this->expectMeetingVenueWithRooms();
        $this->expectFetch('attraction-also-in-meeting-venue.json');

        $this->importConfigurationBypassingCache(5);

        $this->assertSame((string)$this->fetchUidByRemoteId(self::TABLE, self::MEETING_VENUE_WITH_ROOMS), $this->fetchRowByRemoteId(self::TABLE, self::RESOURCES . 'attraction-also-in-meeting-venue')['contained_in_attraction']);
    }

    #[Test]
    public function storedVenueFoundAtTheCapIsResolvedAsRootWithItsRooms(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsReferenceThenMeetingVenue.php');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Import/ExistingMeetingVenue.csv');
        $this->expectFetch('attraction-in-attraction-in-meeting-venue.json');
        $this->expectFetch('attraction-in-meeting-venue.json');
        $this->expectMeetingVenueWithRooms();

        $this->importConfiguration(6);

        $this->assertSame('1', $this->fetchRowByRemoteId(self::TABLE, self::ATTRACTION)['contained_in_attraction']);
        $this->assertCount(count(self::MEETING_VENUE_ROOM_FIXTURES), $this->fetchMmRelatedUids(self::TABLE, 1, 'meeting_rooms'));
    }

    #[Test]
    public function inlineParkingFacilityExistsOnceAndBelongsToTheVenue(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsReferenceThenMeetingVenue.php');
        $this->expectFetch('attraction-in-meeting-venue-with-parking.json');
        $this->expectFetch('160874095809-phhn.json');

        $this->importConfiguration(3);

        $children = $this->fetchChildren('tx_thuecat_parking_facility');
        $this->assertSame([[
            'remote_id' => self::PARKING_VENUE . '::parking::0',
            'parentid' => $this->fetchUidByRemoteId(self::TABLE, self::PARKING_VENUE),
            'parenttable' => self::TABLE,
        ]], array_map(
            static fn (array $row): array => [
                'remote_id' => $row['remote_id'],
                'parentid' => $row['parentid'],
                'parenttable' => $row['parenttable'],
            ],
            $children
        ));
    }
}
