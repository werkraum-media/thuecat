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

final class MeetingVenueOpeningHoursImportTest extends AbstractImportTestCase
{
    private const string HOTEL = 'https://thuecat.org/resources/e_ttg00020050000287335-tomas';

    private const string TABLE = 'tx_thuecat_opening_hours';

    #[Test]
    public function oneOfThreeSpansDroppedUpstreamIsDeleted(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsMeetingVenueParking.php');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Import/ExistingMeetingVenueWithThreeOpeningHours.csv');
        $this->expectFetchForUrl(self::HOTEL, 'thuecat.org/resources/meeting-venue-with-two-opening-hours.json');

        $this->importJob(4);

        $this->assertSame(['Monday', 'Tuesday'], $this->fetchColumnMatching(self::TABLE, 'day_of_week', ['deleted' => 0]));
    }

    // No date rules: a span upstream stopped supplying is deleted whether or not it has ended.
    #[Test]
    public function expiredSpanNoLongerSuppliedIsDeleted(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsMeetingVenueParking.php');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Import/ExistingMeetingVenueWithExpiredOpeningHours.csv');
        $this->expectFetchForUrl(self::HOTEL, 'thuecat.org/resources/meeting-venue-with-two-opening-hours.json');

        $this->importJob(4);

        $this->assertSame(['Monday', 'Tuesday'], $this->fetchColumnMatching(self::TABLE, 'day_of_week', ['deleted' => 0]));
    }

    #[Test]
    public function allSpansDroppedUpstreamAreDeleted(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsMeetingVenueParking.php');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Import/ExistingMeetingVenueWithThreeOpeningHours.csv');
        $this->expectFetchForUrl(self::HOTEL, 'thuecat.org/resources/meeting-venue-without-parking-facilities.json');

        $this->importJob(4);

        $this->assertSame([], $this->fetchColumnMatching(self::TABLE, 'day_of_week', ['deleted' => 0]));
    }

    // An attraction declares nothing, so its spans are judged only where it staged one.
    #[Test]
    public function spanDroppedFromAnAttractionIsDeleted(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsMeetingVenueParking.php');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Import/ExistingAttractionWithThreeOpeningHours.csv');
        $this->expectFetchForUrl(self::HOTEL, 'thuecat.org/resources/attraction-with-two-opening-hours.json');

        $this->importJob(4);

        $this->assertSame(['Monday', 'Tuesday'], $this->fetchColumnMatching(self::TABLE, 'day_of_week', ['deleted' => 0]));
    }
}
