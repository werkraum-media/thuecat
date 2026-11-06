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

final class MeetingVenueImportTest extends AbstractImportTestCase
{
    private const HOTEL = 'https://thuecat.org/resources/669150457039-gamn';

    private const CONVENTION_CENTER = 'https://thuecat.org/resources/766768028887-ohje';

    private const TABLE = 'tx_thuecat_tourist_attraction';

    private const STORAGE_PID = 10;

    #[Test]
    public function conferenceHotelIsStoredAsMeetingVenue(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsMeetingVenues.php');
        $this->expectFetch('669150457039-gamn.json');

        $this->importConfiguration(1);

        $row = $this->fetchRowByRemoteId(self::TABLE, self::HOTEL);
        self::assertSame('meeting_venue', $row['type']);
        self::assertSame('meeting_congress_hotel', $row['venue_type']);
    }

    #[Test]
    public function subclassOfMeetingCongressIsStoredAsMeetingVenue(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsMeetingVenues.php');
        $this->expectFetch('766768028887-ohje.json');

        $this->importConfiguration(2);

        $row = $this->fetchRowByRemoteId(self::TABLE, self::CONVENTION_CENTER);
        self::assertSame('meeting_venue', $row['type']);
        self::assertSame('meeting_congress', $row['venue_type']);
    }

    #[Test]
    public function placeWithoutMeetingTypeIsStoredAsAttraction(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsMeetingVenues.php');
        $this->expectFetchForUrl(self::HOTEL, 'thuecat.org/resources/meeting-venue-without-meeting-type.json');

        $this->importConfiguration(1);

        self::assertSame('attraction', $this->fetchRowByRemoteId(self::TABLE, self::HOTEL)['type']);
    }

    #[Test]
    public function venueWhoseNodeLostTheMeetingTypeBecomesAttraction(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsMeetingVenues.php');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Import/ExistingMeetingVenue.csv');
        $this->expectFetchForUrl(self::HOTEL, 'thuecat.org/resources/meeting-venue-without-meeting-type.json');

        $this->importConfiguration(1);

        self::assertSame([0, 1, 2], $this->fetchLanguagesByRemoteId(self::TABLE, self::HOTEL, self::STORAGE_PID));
        self::assertSame(1, $this->fetchUidByRemoteId(self::TABLE, self::HOTEL));
        self::assertSame('attraction', $this->fetchRowByRemoteId(self::TABLE, self::HOTEL)['type']);
    }

    #[Test]
    public function hotelStoredAsAttractionOnASiteWithoutTypeCategoriesBecomesMeetingVenue(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsMeetingVenueWithoutTypeCategories.php');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Import/ExistingHotelStoredAsAttraction.csv');
        $this->expectFetchForUrl(self::HOTEL, 'thuecat.org/resources/meeting-venue-without-rooms.json');

        $this->importConfiguration(1);

        self::assertSame(0, $this->countRows('sys_category'));
        self::assertSame(
            ['uid' => 1, 'type' => 'meeting_venue', 'venue_type' => 'meeting_congress_hotel'],
            $this->fetchColumnsByRemoteId(self::TABLE, self::HOTEL, ['uid', 'type', 'venue_type'])
        );
    }

    /**
     * @return iterable<string, array{int, string, string, string}>
     */
    public static function venuesWithTheirMeetingCategory(): iterable
    {
        yield 'conference hotel' => [1, '669150457039-gamn.json', self::HOTEL, 'type:thuecat:MeetingCongressHotel'];
        yield 'convention center' => [2, '766768028887-ohje.json', self::CONVENTION_CENTER, 'type:thuecat:MeetingCongress'];
    }

    #[Test]
    #[DataProvider('venuesWithTheirMeetingCategory')]
    public function venueKeepsItsMeetingCategory(int $configuration, string $fixture, string $remoteId, string $categoryRemoteId): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsMeetingVenues.php');
        $this->expectFetch($fixture);

        $this->importConfiguration($configuration);

        $categoryUid = $this->fetchUidByRemoteId('sys_category', $categoryRemoteId);
        self::assertGreaterThan(0, $categoryUid);

        self::assertContains(
            $categoryUid,
            $this->fetchMmRelatedCategoryUids(self::TABLE, $this->fetchUidByRemoteId(self::TABLE, $remoteId), 'categories')
        );
    }
}
