<?php

declare(strict_types=1);

/*
 * Copyright (C) 2021 Daniel Siepmann <coding@daniel-siepmann.de>
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

namespace WerkraumMedia\ThueCat\Tests\Functional\ObjectMapping;

use PHPUnit\Framework\TestCase;
use WerkraumMedia\ThueCat\Domain\Import\EntityMapper;
use WerkraumMedia\ThueCat\Domain\Import\EntityMapper\JsonDecode;
use WerkraumMedia\ThueCat\Domain\Import\Entity\Place;
use WerkraumMedia\ThueCat\Domain\Import\Entity\Properties\Address;

/**
 * @covers \WerkraumMedia\ThueCat\Domain\Import\EntityMapper
 * @covers \WerkraumMedia\ThueCat\Domain\Import\EntityMapper\JsonDecode
 * @uses \WerkraumMedia\ThueCat\Domain\Import\Entity\Place
 * @uses \WerkraumMedia\ThueCat\Domain\Import\Entity\Properties\Address
 */
class PlaceInfosTest extends TestCase
{
    /**
     * @test
     */
    public function instanceOfPlaceIsReturnedIfRequestes(): void
    {
        $subject = new EntityMapper();

        $result = $subject->mapDataToEntity([
        ], Place::class, [
            JsonDecode::ACTIVE_LANGUAGE => 'de',
        ]);

        self::assertInstanceOf(Place::class, $result);
    }

    /**
     * @test
     */
    public function returnsDefaultValuesIfNotProvidedForMapping(): void
    {
        $subject = new EntityMapper();

        $result = $subject->mapDataToEntity([
        ], Place::class, [
            JsonDecode::ACTIVE_LANGUAGE => 'de',
        ]);

        self::assertInstanceOf(Place::class, $result);
        self::assertSame([], $result->getOpeningHoursSpecification());
    }

    /**
     * @test
     */
    public function mapsIncomingOpeningHoursSpecificaton(): void
    {
        $subject = new EntityMapper();

        $result = $subject->mapDataToEntity([
            'schema:openingHoursSpecification' => [
                0 => [
                    'schema:closes' => [
                        '@type' => 'schema:Time',
                        '@value' => '18:00:00',
                    ],
                    'schema:dayOfWeek' => [
                        0 => [
                            '@type' => 'schema:DayOfWeek',
                            '@value' => 'schema:Saturday',
                        ],
                        1 => [
                            '@type' => 'schema:DayOfWeek',
                            '@value' => 'schema:Friday',
                        ],
                        2 => [
                            '@type' => 'schema:DayOfWeek',
                            '@value' => 'schema:Thursday',
                        ],
                        3 => [
                            '@type' => 'schema:DayOfWeek',
                            '@value' => 'schema:Tuesday',
                        ],
                        4 => [
                            '@type' => 'schema:DayOfWeek',
                            '@value' => 'schema:Monday',
                        ],
                        5 => [
                            '@type' => 'schema:DayOfWeek',
                            '@value' => 'schema:Wednesday',
                        ],
                    ],
                    'schema:opens' => [
                        '@type' => 'schema:Time',
                        '@value' => '09:30:00',
                    ],
                    'schema:validFrom' => [
                        '@type' => 'schema:Date',
                        '@value' => '2021-05-01',
                    ],
                    'schema:validThrough' => [
                        '@type' => 'schema:Date',
                        '@value' => '2021-10-31',
                    ],
                ],
                1 => [
                    'schema:closes' => [
                        '@type' => 'schema:Time',
                        '@value' => '18:00:00',
                    ],
                    'schema:dayOfWeek' => [
                        '@type' => 'schema:DayOfWeek',
                        '@value' => 'schema:Sunday',
                    ],
                    'schema:opens' => [
                        '@type' => 'schema:Time',
                        '@value' => '13:00:00',
                    ],
                    'schema:validFrom' => [
                        '@type' => 'schema:Date',
                        '@value' => '2021-05-01',
                    ],
                    'schema:validThrough' => [
                        '@type' => 'schema:Date',
                        '@value' => '2021-10-31',
                    ],
                ],
            ],
        ], Place::class, [
            JsonDecode::ACTIVE_LANGUAGE => 'de',
        ]);

        self::assertInstanceOf(Place::class, $result);
        self::assertCount(2, $result->getOpeningHoursSpecification());
        self::assertSame('18:00:00', $result->getOpeningHoursSpecification()[0]->getCloses()->format('H:i:s'));
        self::assertSame('09:30:00', $result->getOpeningHoursSpecification()[0]->getOpens()->format('H:i:s'));
        self::assertSame('2021-05-01', $result->getOpeningHoursSpecification()[0]->getValidFrom()->format('Y-m-d'));
        self::assertSame('2021-10-31', $result->getOpeningHoursSpecification()[0]->getValidThrough()->format('Y-m-d'));
        self::assertSame([
            'Saturday',
            'Friday',
            'Thursday',
            'Tuesday',
            'Monday',
            'Wednesday',
        ], $result->getOpeningHoursSpecification()[0]->getDaysOfWeek());
        self::assertSame('18:00:00', $result->getOpeningHoursSpecification()[1]->getCloses()->format('H:i:s'));
        self::assertSame('13:00:00', $result->getOpeningHoursSpecification()[1]->getOpens()->format('H:i:s'));
        self::assertSame('2021-05-01', $result->getOpeningHoursSpecification()[1]->getValidFrom()->format('Y-m-d'));
        self::assertSame('2021-10-31', $result->getOpeningHoursSpecification()[1]->getValidThrough()->format('Y-m-d'));
        self::assertSame(['Sunday'], $result->getOpeningHoursSpecification()[1]->getDaysOfWeek());
    }

    /**
     * @test
     */
    public function mapsIncomingAddress(): void
    {
        $subject = new EntityMapper();

        $result = $subject->mapDataToEntity([
            'schema:address' => [
                'schema:addressLocality' => [
                    '@language' => 'de',
                    '@value' => 'Erfurt',
                ],
                'schema:postalCode' => [
                    0 => [
                        '@language' => 'de',
                        '@value' => '99084',
                    ],
                    1 => [
                        '@language' => 'en',
                        '@value' => '99084',
                    ],
                ],
                'schema:telephone' => [
                    0 => [
                        '@language' => 'de',
                        '@value' => '+49 361 6461265',
                    ],
                    1 => [
                        '@language' => 'en',
                        '@value' => '+49 361 6461265',
                    ],
                ],
                'schema:email' => [
                    0 => [
                        '@language' => 'de',
                        '@value' => 'dominformation@domberg-erfurt.de',
                    ],
                    1 => [
                        '@language' => 'en',
                        '@value' => 'dominformation@domberg-erfurt.de',
                    ],
                ],
                'schema:streetAddress' => [
                    0 => [
                        '@language' => 'de',
                        '@value' => 'Domstufen 1',
                    ],
                    1 => [
                        '@language' => 'en',
                        '@value' => 'Domstufen 1',
                    ],
                ],
            ],
        ], Place::class, [
            JsonDecode::ACTIVE_LANGUAGE => 'de',
        ]);

        self::assertInstanceOf(Place::class, $result);
        self::assertInstanceOf(Address::class, $result->getAddress());
        self::assertSame('Domstufen 1', $result->getAddress()->getStreetAddress());
        self::assertSame('Erfurt', $result->getAddress()->getAddressLocality());
        self::assertSame('99084', $result->getAddress()->getPostalCode());
        self::assertSame('+49 361 6461265', $result->getAddress()->getTelephone());
        self::assertSame('', $result->getAddress()->getFaxNumber());
        self::assertSame('dominformation@domberg-erfurt.de', $result->getAddress()->getEmail());
    }
}
