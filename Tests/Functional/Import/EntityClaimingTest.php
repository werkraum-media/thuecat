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
 * Every node carries schema:Organization and schema:Place, so the entity a node lands in
 * follows its single ttgds: top-level class.
 */
final class EntityClaimingTest extends AbstractImportTestCase
{
    private const RESOURCES = 'https://thuecat.org/resources/';

    protected function setUp(): void
    {
        parent::setUp();

        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsClaimedKinds.php');
    }

    #[Test]
    public function hotelTypedAsOrganisationIsImportedAsAttraction(): void
    {
        $this->expectFetch('claiming-hotel.json');

        $this->importConfiguration(1);

        self::assertGreaterThan(0, $this->fetchUidByRemoteId('tx_thuecat_tourist_attraction', self::RESOURCES . 'claiming-hotel'));
        self::assertSame(0, $this->fetchUidByRemoteId('tx_thuecat_organisation', self::RESOURCES . 'claiming-hotel'));
    }

    #[Test]
    public function lodgingWithoutHotelTypeIsImportedAsAttraction(): void
    {
        $this->expectFetch('claiming-bed-and-breakfast.json');

        $this->importConfiguration(2);

        self::assertGreaterThan(0, $this->fetchUidByRemoteId('tx_thuecat_tourist_attraction', self::RESOURCES . 'claiming-bed-and-breakfast'));
        self::assertSame(0, $this->fetchUidByRemoteId('tx_thuecat_organisation', self::RESOURCES . 'claiming-bed-and-breakfast'));
    }

    #[Test]
    public function hotelReachedThroughContainedPlaceIsRelatedAsAttraction(): void
    {
        $this->expectFetch('claiming-restaurant-in-hotel.json');
        $this->expectFetch('claiming-hotel.json');

        $this->importConfiguration(3);

        $hotelUid = $this->fetchUidByRemoteId('tx_thuecat_tourist_attraction', self::RESOURCES . 'claiming-hotel');
        self::assertGreaterThan(0, $hotelUid);
        self::assertSame(0, $this->fetchUidByRemoteId('tx_thuecat_organisation', self::RESOURCES . 'claiming-hotel'));
        $restaurant = $this->fetchRowByRemoteId('tx_thuecat_tourist_attraction', self::RESOURCES . 'claiming-restaurant-in-hotel');
        self::assertSame((string)$hotelUid, $restaurant['contained_in_attraction']);
    }

    #[Test]
    public function tourismOrganisationStaysOrganisation(): void
    {
        $this->expectFetch('claiming-dmo.json');

        $this->importConfiguration(4);

        self::assertGreaterThan(0, $this->fetchUidByRemoteId('tx_thuecat_organisation', self::RESOURCES . 'claiming-dmo'));
    }

    #[Test]
    public function storeTypedAsOrganisationIsNoOrganisation(): void
    {
        $this->expectFetch('claiming-store.json');

        $this->importConfiguration(5);

        self::assertSame(0, $this->fetchUidByRemoteId('tx_thuecat_organisation', self::RESOURCES . 'claiming-store'));
    }

    #[Test]
    public function parkingFacilityKeepsItsEntity(): void
    {
        $this->expectFetch('claiming-parking.json');

        $this->importConfiguration(6);

        self::assertGreaterThan(0, $this->fetchUidByRemoteId('tx_thuecat_parking_facility', self::RESOURCES . 'claiming-parking'));
        self::assertSame(0, $this->fetchUidByRemoteId('tx_thuecat_organisation', self::RESOURCES . 'claiming-parking'));
    }

    #[Test]
    public function untypedContainedPlaceIsIgnored(): void
    {
        $this->expectFetch('claiming-attraction-in-untyped-place.json');
        $this->expectFetch('claiming-untyped-place.json');

        $this->importConfiguration(7);

        $attraction = $this->fetchRowByRemoteId('tx_thuecat_tourist_attraction', self::RESOURCES . 'claiming-attraction-in-untyped-place');
        self::assertSame('', $attraction['contained_in_attraction']);
        self::assertSame('', $attraction['contained_in_organisation']);
        self::assertSame(0, $this->fetchUidByRemoteId('tx_thuecat_organisation', self::RESOURCES . 'claiming-untyped-place'));
        self::assertSame(0, $this->fetchUidByRemoteId('tx_thuecat_tourist_attraction', self::RESOURCES . 'claiming-untyped-place'));
        self::assertSame([], $this->getLogEntriesOfType('referenceUnrelatable'));
        self::assertSame([], $this->getLogEntriesOfType('referenceSkipped'));
    }
}
