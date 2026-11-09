<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS extension "thuecat".
 *
 * Copyright (C) werkraum-media <https://werkraum-media.de/>
 * Copyright (C) 2022 Daniel Siepmann <coding@daniel-siepmann.de>
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

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\ParkingFacility;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\TouristAttraction;

final class TouristAttractionTest extends TestCase
{
    #[Test]
    public function returnsParkingFacilitiesNearBySorted(): void
    {
        $unsortedFacilities = new ObjectStorage();
        $unsortedFacilities->attach($this->parkingFacilityWithTitle('P+R Anlage Zoopark'));
        $unsortedFacilities->attach($this->parkingFacilityWithTitle('Parkhaus Domplatz'));
        $unsortedFacilities->attach($this->parkingFacilityWithTitle('007 Parking'));
        $unsortedFacilities->attach($this->parkingFacilityWithTitle('Parkplatz Forum 4'));
        $unsortedFacilities->attach($this->parkingFacilityWithTitle('Parkplatz Forum 2/3'));
        $unsortedFacilities->attach($this->parkingFacilityWithTitle('Parkplatz Forum 1'));
        $unsortedFacilities->attach($this->parkingFacilityWithTitle('Parkplatz Forum 2'));

        $subject = new TouristAttraction();
        $subject->_setProperty('parkingFacilityNearBy', $unsortedFacilities);

        $result = $subject->getParkingFacilitiesNearBySortedByAlphabet();
        $this->assertSame('007 Parking', $result[0]->getTitle());
        $this->assertSame('P+R Anlage Zoopark', $result[1]->getTitle());
        $this->assertSame('Parkhaus Domplatz', $result[2]->getTitle());
        $this->assertSame('Parkplatz Forum 1', $result[3]->getTitle());
        $this->assertSame('Parkplatz Forum 2', $result[4]->getTitle());
        $this->assertSame('Parkplatz Forum 2/3', $result[5]->getTitle());
        $this->assertSame('Parkplatz Forum 4', $result[6]->getTitle());
    }

    private function parkingFacilityWithTitle(string $title): ParkingFacility
    {
        $facility = new ParkingFacility();
        $facility->_setProperty('title', $title);

        return $facility;
    }

    #[Test]
    public function returnsDistanceToPublicTransportArrayWithoutTypes(): void
    {
        $subject = new TouristAttraction();
        $subject->_setProperty('distanceToPublicTransport', '300:MTR');

        $this->assertSame([
            'value' => '300',
            'unit' => 'MTR',
            'types' => [],
        ], $subject->getDistanceToPublicTransport());
    }

    #[Test]
    public function returnsDistanceToPublicTransportArrayWithTwoTypes(): void
    {
        $subject = new TouristAttraction();
        $subject->_setProperty('distanceToPublicTransport', '300:MTR:Streetcar:CityBus');

        $this->assertSame([
            'value' => '300',
            'unit' => 'MTR',
            'types' => [
                'Streetcar',
                'CityBus',
            ],
        ], $subject->getDistanceToPublicTransport());
    }

    #[Test]
    public function returnsNoAdditionalType(): void
    {
        $this->assertSame('', new TouristAttraction()->getAdditionalType());
    }

    #[Test]
    public function returnsSingleSlogan(): void
    {
        $subject = new TouristAttraction();
        $subject->_setProperty('slogan', 'Some text');

        $this->assertSame('Some text', $subject->getSlogan());
        $this->assertSame(['Some text'], $subject->getSlogans());
    }

    #[Test]
    public function returnsMultipleSlogans(): void
    {
        $subject = new TouristAttraction();
        $subject->_setProperty('slogan', 'Some text,Highlight');

        $this->assertSame('Some text', $subject->getSlogan());
        $this->assertSame(['Some text', 'Highlight'], $subject->getSlogans());
    }
}
