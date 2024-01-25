<?php

declare(strict_types=1);

/*
 * Copyright (C) 2024 Daniel Siepmann <coding@daniel-siepmann.de>
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

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\MergedOpeningHour;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\MergedOpeningHours;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\OpeningHours;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\ParkingFacility;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\TouristAttraction;
use WerkraumMedia\ThueCat\Service\DateBasedFilter;

class PlaceTest extends TestCase
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
        self::assertSame('007 Parking', $result[0]->getTitle());
        self::assertSame('P+R Anlage Zoopark', $result[1]->getTitle());
        self::assertSame('Parkhaus Domplatz', $result[2]->getTitle());
        self::assertSame('Parkplatz Forum 1', $result[3]->getTitle());
        self::assertSame('Parkplatz Forum 2', $result[4]->getTitle());
        self::assertSame('Parkplatz Forum 2/3', $result[5]->getTitle());
        self::assertSame('Parkplatz Forum 4', $result[6]->getTitle());
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

        self::assertSame([
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

        self::assertSame([
            'value' => '300',
            'unit' => 'MTR',
            'types' => [
                'Streetcar',
                'CityBus',
            ],
        ], $subject->getDistanceToPublicTransport());
    }

    #[Test]
    public function returnsMergedOpeningHours(): void
    {
        GeneralUtility::addInstance(DateBasedFilter::class, new class() implements DateBasedFilter {
            public function filterOutPreviousDates(
                array $listToFilter,
                callable $provideDate
            ): array {
                return $listToFilter;
            }
        });

        $openingHours = new OpeningHours(json_encode([
            [
                'opens' => '12:00',
                'closes' => '14:00',
                'daysOfWeek' => [
                    'Sunday',
                ],
                'from' => [
                    'date' => '@' . (new DateTimeImmutable())->format('U'),
                ],
                'through' => [
                    'date' => '@' . (new DateTimeImmutable())->modify('+2 days')->format('U'),
                ],
            ],
            [
                'opens' => '10:00',
                'closes' => '16:00',
                'daysOfWeek' => [
                    'Monday',
                    'Tuesday',
                ],
                'from' => [
                    'date' => '@' . (new DateTimeImmutable())->format('U'),
                ],
                'through' => [
                    'date' => '@' . (new DateTimeImmutable())->modify('+2 days')->format('U'),
                ],
            ],
            [
                'opens' => '13:00',
                'closes' => '15:00',
                'daysOfWeek' => [
                    'Saturday',
                ],
                'from' => [
                    'date' => '@' . (new DateTimeImmutable())->format('U'),
                ],
                'through' => [
                    'date' => '@' . (new DateTimeImmutable())->modify('+3 days')->format('U'),
                ],
            ],
        ]) ?: '');

        $subject = new TouristAttraction();
        $subject->_setProperty('openingHours', $openingHours);

        $result = $subject->getMergedOpeningHours();
        self::assertInstanceOf(MergedOpeningHours::class, $result);
        self::assertCount(2, $result);
        foreach ($result as $index => $mergedHour) {
            self::assertInstanceOf(MergedOpeningHour::class, $mergedHour);
            $today = (new DateTimeImmutable())->format('Y-m-d');
            $inTwoDays = (new DateTimeImmutable())->modify('+2 days')->format('Y-m-d');
            $inThreeDays = (new DateTimeImmutable())->modify('+3 days')->format('Y-m-d');

            if ($index === 0) {
                self::assertSame($today, $mergedHour->getFrom() ? $mergedHour->getFrom()->format('Y-m-d') : '');
                self::assertSame($inTwoDays, $mergedHour->getThrough() ? $mergedHour->getThrough()->format('Y-m-d') : '');
                self::assertCount(3, $mergedHour->getWeekDays());
                self::assertSame('Sunday', $mergedHour->getWeekDays()[0]->getDayOfWeek());
                self::assertSame('12:00', $mergedHour->getWeekDays()[0]->getOpens());
                self::assertSame('14:00', $mergedHour->getWeekDays()[0]->getCloses());
                self::assertSame('Monday', $mergedHour->getWeekDays()[1]->getDayOfWeek());
                self::assertSame('10:00', $mergedHour->getWeekDays()[1]->getOpens());
                self::assertSame('16:00', $mergedHour->getWeekDays()[1]->getCloses());
                self::assertSame('Tuesday', $mergedHour->getWeekDays()[2]->getDayOfWeek());
                self::assertSame('10:00', $mergedHour->getWeekDays()[2]->getOpens());
                self::assertSame('16:00', $mergedHour->getWeekDays()[2]->getCloses());
            } elseif ($index === 1) {
                self::assertSame($today, $mergedHour->getFrom() ? $mergedHour->getFrom()->format('Y-m-d') : '');
                self::assertSame($inThreeDays, $mergedHour->getThrough() ? $mergedHour->getThrough()->format('Y-m-d') : '');
                self::assertCount(1, $mergedHour->getWeekDays());
                self::assertSame('Saturday', $mergedHour->getWeekDays()[0]->getDayOfWeek());
                self::assertSame('13:00', $mergedHour->getWeekDays()[0]->getOpens());
                self::assertSame('15:00', $mergedHour->getWeekDays()[0]->getCloses());
            }
        }
    }

    #[Test]
    public function returnsMergedSpecialOpeningHours(): void
    {
        GeneralUtility::addInstance(DateBasedFilter::class, new class() implements DateBasedFilter {
            public function filterOutPreviousDates(
                array $listToFilter,
                callable $provideDate
            ): array {
                return $listToFilter;
            }
        });

        $openingHours = new OpeningHours(json_encode([
            [
                'opens' => '12:00',
                'closes' => '14:00',
                'daysOfWeek' => [
                    'Sunday',
                ],
                'from' => [
                    'date' => '@' . (new DateTimeImmutable())->format('U'),
                ],
                'through' => [
                    'date' => '@' . (new DateTimeImmutable())->modify('+2 days')->format('U'),
                ],
            ],
            [
                'opens' => '10:00',
                'closes' => '16:00',
                'daysOfWeek' => [
                    'Monday',
                    'Tuesday',
                ],
                'from' => [
                    'date' => '@' . (new DateTimeImmutable())->format('U'),
                ],
                'through' => [
                    'date' => '@' . (new DateTimeImmutable())->modify('+2 days')->format('U'),
                ],
            ],
            [
                'opens' => '13:00',
                'closes' => '15:00',
                'daysOfWeek' => [
                    'Saturday',
                ],
                'from' => [
                    'date' => '@' . (new DateTimeImmutable())->format('U'),
                ],
                'through' => [
                    'date' => '@' . (new DateTimeImmutable())->modify('+3 days')->format('U'),
                ],
            ],
        ]) ?: '');

        $subject = new TouristAttraction();
        $subject->_setProperty('specialOpeningHours', $openingHours);

        $result = $subject->getMergedSpecialOpeningHours();
        self::assertInstanceOf(MergedOpeningHours::class, $result);
        self::assertCount(2, $result);
        foreach ($result as $index => $mergedHour) {
            self::assertInstanceOf(MergedOpeningHour::class, $mergedHour);
            $today = (new DateTimeImmutable())->format('Y-m-d');
            $inTwoDays = (new DateTimeImmutable())->modify('+2 days')->format('Y-m-d');
            $inThreeDays = (new DateTimeImmutable())->modify('+3 days')->format('Y-m-d');

            if ($index === 0) {
                self::assertSame($today, $mergedHour->getFrom() ? $mergedHour->getFrom()->format('Y-m-d') : '');
                self::assertSame($inTwoDays, $mergedHour->getThrough() ? $mergedHour->getThrough()->format('Y-m-d') : '');
                self::assertCount(3, $mergedHour->getWeekDaysWithMondayFirstWeekDay());
                self::assertSame('Monday', $mergedHour->getWeekDaysWithMondayFirstWeekDay()[0]->getDayOfWeek());
                self::assertSame('10:00', $mergedHour->getWeekDaysWithMondayFirstWeekDay()[0]->getOpens());
                self::assertSame('16:00', $mergedHour->getWeekDaysWithMondayFirstWeekDay()[0]->getCloses());
                self::assertSame('Tuesday', $mergedHour->getWeekDaysWithMondayFirstWeekDay()[1]->getDayOfWeek());
                self::assertSame('10:00', $mergedHour->getWeekDaysWithMondayFirstWeekDay()[1]->getOpens());
                self::assertSame('16:00', $mergedHour->getWeekDaysWithMondayFirstWeekDay()[1]->getCloses());
                self::assertSame('Sunday', $mergedHour->getWeekDaysWithMondayFirstWeekDay()[2]->getDayOfWeek());
                self::assertSame('12:00', $mergedHour->getWeekDaysWithMondayFirstWeekDay()[2]->getOpens());
                self::assertSame('14:00', $mergedHour->getWeekDaysWithMondayFirstWeekDay()[2]->getCloses());
            } elseif ($index === 1) {
                self::assertSame($today, $mergedHour->getFrom() ? $mergedHour->getFrom()->format('Y-m-d') : '');
                self::assertSame($inThreeDays, $mergedHour->getThrough() ? $mergedHour->getThrough()->format('Y-m-d') : '');
                self::assertCount(1, $mergedHour->getWeekDaysWithMondayFirstWeekDay());
                self::assertSame('Saturday', $mergedHour->getWeekDaysWithMondayFirstWeekDay()[0]->getDayOfWeek());
                self::assertSame('13:00', $mergedHour->getWeekDaysWithMondayFirstWeekDay()[0]->getOpens());
                self::assertSame('15:00', $mergedHour->getWeekDaysWithMondayFirstWeekDay()[0]->getCloses());
            }
        }
    }
}
