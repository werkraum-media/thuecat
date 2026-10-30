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
 * Every node is imported as a root, so no reference finds the organisation before the node is written.
 * The restaurant and the fountain are not part of the run; only the repair can move their relations.
 */
final class OrganisationReclassificationRepairTest extends AbstractImportTestCase
{
    private const RESOURCES = 'https://thuecat.org/resources/';

    protected function setUp(): void
    {
        parent::setUp();

        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/RepairsReclassifiedOrganisations.php');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Import/ExistingMisclassifiedOrganisations.csv');
    }

    #[Test]
    public function hotelHeldAsOrganisationMovesToTheAttractionTable(): void
    {
        $this->expectFetch('repair-hotel.json');

        $this->importConfiguration(1);

        $hotel = $this->fetchRowByRemoteId('tx_thuecat_tourist_attraction', self::RESOURCES . 'repair-hotel');
        self::assertSame('Hotel am Markt', $hotel['title']);
        self::assertIsInt($hotel['uid']);
        self::assertSame(
            'Hotel on the market square',
            $this->fetchTranslationsByParent('tx_thuecat_tourist_attraction', $hotel['uid'])[1]['title'] ?? null
        );

        $addresses = array_values(array_filter(
            $this->fetchChildren('tx_thuecat_address'),
            static fn (array $address): bool => $address['remote_id'] === self::RESOURCES . 'repair-hotel::addr::0'
        ));
        self::assertCount(1, $addresses);
        self::assertSame('tx_thuecat_tourist_attraction', $addresses[0]['parenttable']);
        self::assertSame($hotel['uid'], $addresses[0]['parentid']);

        self::assertSame(2, $this->countDeletedRows('tx_thuecat_organisation'));
        self::assertSame([], $this->fetchTranslationsByParent('tx_thuecat_organisation', 21));
    }

    #[Test]
    public function translatedAddressOfTheOrganisationIsReplacedWithoutError(): void
    {
        $this->expectFetch('repair-hotel.json');

        $this->importConfiguration(1);

        self::assertSame([], $this->getLogEntriesOfType('dataHandlerError'));
        $hotelUid = $this->fetchUidByRemoteId('tx_thuecat_tourist_attraction', self::RESOURCES . 'repair-hotel');
        $hotelTranslation = $this->fetchTranslationsByParent('tx_thuecat_tourist_attraction', $hotelUid)[1] ?? [];
        $addresses = array_values(array_filter(
            $this->fetchChildren('tx_thuecat_address'),
            static fn (array $address): bool => $address['remote_id'] === self::RESOURCES . 'repair-hotel::addr::0'
        ));
        self::assertCount(1, $addresses);
        self::assertNotSame(31, $addresses[0]['uid']);
        self::assertIsInt($addresses[0]['uid']);
        $addressTranslation = $this->fetchTranslationsByParent('tx_thuecat_address', $addresses[0]['uid'])[1] ?? [];
        self::assertSame('tx_thuecat_tourist_attraction', $addressTranslation['parenttable'] ?? null);
        self::assertSame($hotelTranslation['uid'] ?? null, $addressTranslation['parentid'] ?? null);
        self::assertSame([], $this->fetchTranslationsByParent('tx_thuecat_address', 31));
    }

    #[Test]
    public function addressOfAnUntranslatedHotelGetsNoTranslation(): void
    {
        $this->expectFetch('repair-holiday-flat.json');

        $this->importConfiguration(5);

        $flatUid = $this->fetchUidByRemoteId('tx_thuecat_tourist_attraction', self::RESOURCES . 'repair-holiday-flat');
        self::assertGreaterThan(0, $flatUid);
        self::assertSame([], $this->fetchTranslationsByParent('tx_thuecat_tourist_attraction', $flatUid));
        self::assertSame([], $this->fetchTranslationsByParent('tx_thuecat_address', 33));
    }

    /**
     * The square is reached as a reference, so the run first knows it as the organisation the probe found, and the
     * fountain's own address is already staged when the square's address arrives.
     */
    #[Test]
    public function translatedAddressOfAReferencedSquareIsReplacedWithoutError(): void
    {
        $this->expectFetch('repair-fountain-on-square.json');
        $this->expectFetch('repair-square.json');

        $this->importConfiguration(6);

        self::assertSame([], $this->getLogEntriesOfType('dataHandlerError'));
        $squareUid = $this->fetchUidByRemoteId('tx_thuecat_place', self::RESOURCES . 'repair-square');
        $fountain = $this->fetchRowByRemoteId('tx_thuecat_tourist_attraction', self::RESOURCES . 'repair-fountain-on-square');
        self::assertSame((string)$squareUid, $fountain['contained_in_place']);
        $addresses = array_values(array_filter(
            $this->fetchChildren('tx_thuecat_address'),
            static fn (array $address): bool => $address['remote_id'] === self::RESOURCES . 'repair-square::addr::0'
        ));
        self::assertCount(1, $addresses);
        self::assertNotSame(34, $addresses[0]['uid']);
        self::assertSame('tx_thuecat_place', $addresses[0]['parenttable']);
        self::assertSame([], $this->fetchTranslationsByParent('tx_thuecat_address', 34));
    }

    #[Test]
    public function storeHeldAsOrganisationMovesToThePlaceTable(): void
    {
        $this->expectFetch('place-store.json');

        $this->importConfiguration(2);

        self::assertGreaterThan(0, $this->fetchUidByRemoteId('tx_thuecat_place', self::RESOURCES . 'place-store'));
        self::assertSame(0, $this->fetchUidByRemoteId('tx_thuecat_organisation', self::RESOURCES . 'place-store'));
    }

    #[Test]
    public function organisationOfAnotherSiteIsKept(): void
    {
        $this->expectFetch('repair-hotel.json');

        $this->importConfiguration(1);

        $organisation = $this->fetchRowByRemoteId('tx_thuecat_organisation', self::RESOURCES . 'repair-hotel');
        self::assertSame(26, $organisation['uid']);
        self::assertSame(4010, $organisation['pid']);
    }

    #[Test]
    public function organisationThatStaysOneIsUpdated(): void
    {
        $this->expectFetch('claiming-dmo.json');

        $this->importConfiguration(4);

        $organisation = $this->fetchRowByRemoteId('tx_thuecat_organisation', self::RESOURCES . 'claiming-dmo');
        self::assertSame(25, $organisation['uid']);
        self::assertSame('Tourismusverband', $organisation['title']);
        self::assertSame(0, $this->countDeletedRows('tx_thuecat_organisation'));
    }

    #[Test]
    public function restaurantInsideTheHotelNamesTheHotelsAttraction(): void
    {
        $this->expectFetch('repair-hotel.json');

        $this->importConfiguration(1);

        $hotelUid = $this->fetchUidByRemoteId('tx_thuecat_tourist_attraction', self::RESOURCES . 'repair-hotel');
        $restaurant = $this->fetchRowByRemoteId('tx_thuecat_tourist_attraction', self::RESOURCES . 'repair-restaurant');
        self::assertSame((string)$hotelUid, $restaurant['contained_in_attraction']);
        self::assertSame('', $restaurant['contained_in_organisation']);
        self::assertSame(0, $restaurant['managed_by']);
    }

    #[Test]
    public function attractionOnASquareNamesTheSquaresPlace(): void
    {
        $this->expectFetch('place-square.json');

        $this->importConfiguration(3);

        $squareUid = $this->fetchUidByRemoteId('tx_thuecat_place', self::RESOURCES . 'place-square');
        $fountain = $this->fetchRowByRemoteId('tx_thuecat_tourist_attraction', self::RESOURCES . 'repair-attraction-on-square');
        self::assertSame((string)$squareUid, $fountain['contained_in_place']);
        self::assertSame('', $fountain['contained_in_organisation']);
    }

    #[Test]
    public function repairIsLoggedWithMovedAndDroppedRelations(): void
    {
        $this->expectFetch('repair-hotel.json');

        $this->importConfiguration(1);

        $hotelUid = $this->fetchUidByRemoteId('tx_thuecat_tourist_attraction', self::RESOURCES . 'repair-hotel');
        $entries = $this->getLogEntriesOfType('recordReclassification');
        self::assertCount(1, $entries);
        self::assertSame('warning', $entries[0]['severity']);
        self::assertSame(self::RESOURCES . 'repair-hotel', $entries[0]['remote_id']);
        self::assertSame('tx_thuecat_tourist_attraction', $entries[0]['table_name']);
        self::assertIsString($entries[0]['context']);
        self::assertSame(
            [
                'previousTable' => 'tx_thuecat_organisation',
                'previousUid' => 21,
                'table' => 'tx_thuecat_tourist_attraction',
                'uid' => $hotelUid,
                'moved' => ['tx_thuecat_tourist_attraction:41'],
                'droppedManagedBy' => ['tx_thuecat_tourist_attraction:41'],
                'droppedManagedEvents' => [51, 52],
            ],
            json_decode($entries[0]['context'], true)
        );
    }
}
