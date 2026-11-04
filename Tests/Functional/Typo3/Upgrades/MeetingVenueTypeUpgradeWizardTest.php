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

namespace WerkraumMedia\ThueCat\Tests\Functional\Typo3\Upgrades;

use PHPUnit\Framework\Attributes\Test;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractFunctionalTestCase;
use WerkraumMedia\ThueCat\Typo3\Upgrades\MeetingVenueTypeUpgradeWizard;

final class MeetingVenueTypeUpgradeWizardTest extends AbstractFunctionalTestCase
{
    private const TABLE = 'tx_thuecat_tourist_attraction';

    #[Test]
    public function conferenceHotelBecomesMeetingVenueWithItsTranslation(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Upgrades/MeetingVenuesStoredAsAttractions.csv');
        $categoriesBefore = $this->getConnectionPool()->getConnectionForTable('sys_category_record_mm')
            ->select(['*'], 'sys_category_record_mm')->fetchAllAssociative()
        ;

        $this->get(MeetingVenueTypeUpgradeWizard::class)->executeUpdate();

        $hotel = $this->fetchRowByRemoteId(self::TABLE, 'test:wizard-hotel');
        $translation = $this->fetchTranslationsByParent(self::TABLE, 800)[1] ?? [];
        self::assertSame(
            [
                [800, 'dorint-hotel-am-dom-erfurt', 'meeting_venue', 'meeting_congress_hotel'],
                [801, 'dorint-hotel-am-dom-erfurt-en', 'meeting_venue', 'meeting_congress_hotel'],
            ],
            [
                [$hotel['uid'], $hotel['slug'], $hotel['type'], $hotel['venue_type']],
                [$translation['uid'] ?? null, $translation['slug'] ?? null, $translation['type'] ?? null, $translation['venue_type'] ?? null],
            ]
        );
        self::assertSame(
            $categoriesBefore,
            $this->getConnectionPool()->getConnectionForTable('sys_category_record_mm')
                ->select(['*'], 'sys_category_record_mm')->fetchAllAssociative()
        );
    }

    #[Test]
    public function venueOfTheCongressCategoryBecomesMeetingCongress(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Upgrades/MeetingVenuesStoredAsAttractions.csv');

        $this->get(MeetingVenueTypeUpgradeWizard::class)->executeUpdate();

        $row = $this->fetchRowByRemoteId(self::TABLE, 'test:wizard-congress');
        self::assertSame(['meeting_venue', 'meeting_congress'], [$row['type'], $row['venue_type']]);
    }

    #[Test]
    public function hotelCategoryDecidesWhenBothAreRelated(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Upgrades/MeetingVenuesStoredAsAttractions.csv');

        $this->get(MeetingVenueTypeUpgradeWizard::class)->executeUpdate();

        $row = $this->fetchRowByRemoteId(self::TABLE, 'test:wizard-hotel-and-congress');
        self::assertSame(['meeting_venue', 'meeting_congress_hotel'], [$row['type'], $row['venue_type']]);
    }

    #[Test]
    public function attractionWithoutMeetingCategoryStaysAttraction(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Upgrades/MeetingVenuesStoredAsAttractions.csv');

        $this->get(MeetingVenueTypeUpgradeWizard::class)->executeUpdate();

        $row = $this->fetchRowByRemoteId(self::TABLE, 'test:wizard-museum');
        self::assertSame(['attraction', ''], [$row['type'], $row['venue_type']]);
    }

    #[Test]
    public function reportsUpdateNecessaryWhileAVenueIsUntyped(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Upgrades/MeetingVenuesStoredAsAttractions.csv');

        self::assertTrue($this->get(MeetingVenueTypeUpgradeWizard::class)->updateNecessary());
    }

    #[Test]
    public function reportsNothingToDoOnceEveryVenueIsTyped(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Upgrades/MeetingVenuesStoredAsAttractions.csv');
        $wizard = $this->get(MeetingVenueTypeUpgradeWizard::class);

        $wizard->executeUpdate();

        self::assertFalse($wizard->updateNecessary());
    }
}
