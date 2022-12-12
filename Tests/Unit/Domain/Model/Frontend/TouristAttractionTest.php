<?php

declare(strict_types=1);

/*
 * Copyright (C) 2022 Daniel Siepmann <coding@daniel-siepmann.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301, USA.
 */

namespace WerkraumMedia\ThueCat\Tests\Unit\Domain\Model\Frontend;

use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\ParkingFacility;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\TouristAttraction;
use PHPUnit\Framework\TestCase;

/**
 * @covers \WerkraumMedia\ThueCat\Domain\Model\Frontend\TouristAttraction
 */
class TouristAttractionTest extends TestCase
{
    /**
     * @test
     */
    public function returnsParkingFacilitiesNearBySorted(): void
    {
        $unsortedFacilities = new ObjectStorage();
        $unsortedFacilities->attach($this->parkingFacilityWithTitle('P+R Anlage Zoopark'));
        $unsortedFacilities->attach($this->parkingFacilityWithTitle('Parkhaus Domplatz'));
        $unsortedFacilities->attach($this->parkingFacilityWithTitle('007 Parking'));

        $subject = new TouristAttraction();
        $subject->_setProperty('parkingFacilityNearBy', $unsortedFacilities);

        $result = $subject->getParkingFacilitiesNearBySortedByAlphabet();
        self::assertSame('007 Parking', $result[0]->getTitle());
        self::assertSame('P+R Anlage Zoopark', $result[1]->getTitle());
        self::assertSame('Parkhaus Domplatz', $result[2]->getTitle());
    }

    private function parkingFacilityWithTitle(string $title): ParkingFacility
    {
        $facility = new ParkingFacility();
        $facility->_setProperty('title', $title);

        return $facility;
    }

    /**
     * @test
     */
    public function returnsDistanceToPublicTransportArrayWithoutTypes(): void
    {
        $subject = new TouristAttraction();
        $subject->_setProperty('distanceToPublicTransport', '300:MTR');

        self::assertSame([
            'value' => '300',
            'unit' => 'MTR',
            'types' => [],
        ], $subject->getDistanceToPublicTransport());
    }

    /**
     * @test
     */
    public function returnsDistanceToPublicTransportArrayWithTwoTypes(): void
    {
        $subject = new TouristAttraction();
        $subject->_setProperty('distanceToPublicTransport', '300:MTR:Streetcar:CityBus');

        self::assertSame([
            'value' => '300',
            'unit' => 'MTR',
            'types' => [
                'Streetcar',
                'CityBus',
            ],
        ], $subject->getDistanceToPublicTransport());
    }
}
