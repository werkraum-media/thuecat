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
use TYPO3\CMS\Core\Database\Connection;
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

        self::assertSame([0], $this->fetchLanguagesByRemoteId(self::TABLE, self::HOTEL, self::STORAGE_PID));
        self::assertSame(1, $this->fetchUidByRemoteId(self::TABLE, self::HOTEL));
        self::assertSame('attraction', $this->fetchRowByRemoteId(self::TABLE, self::HOTEL)['type']);
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

        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('sys_category_record_mm');
        $relations = $queryBuilder
            ->count('uid_local')
            ->from('sys_category_record_mm')
            ->where(
                $queryBuilder->expr()->eq('uid_local', $queryBuilder->createNamedParameter($categoryUid, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq(
                    'uid_foreign',
                    $queryBuilder->createNamedParameter($this->fetchUidByRemoteId(self::TABLE, $remoteId), Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq('tablenames', $queryBuilder->createNamedParameter(self::TABLE)),
                $queryBuilder->expr()->eq('fieldname', $queryBuilder->createNamedParameter('categories'))
            )
            ->executeQuery()
            ->fetchOne()
        ;

        self::assertSame(1, is_numeric($relations) ? (int)$relations : 0);
    }
}
