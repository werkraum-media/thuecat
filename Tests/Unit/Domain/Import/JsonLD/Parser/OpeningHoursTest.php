<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Unit\Domain\Import\JsonLD\Parser;

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

use PHPUnit\Framework\TestCase;
use WerkraumMedia\ThueCat\Domain\Import\JsonLD\Parser\OpeningHours;

/**
 * @covers WerkraumMedia\ThueCat\Domain\Import\JsonLD\Parser\OpeningHours
 */
class OpeningHoursTest extends TestCase
{
    /**
     * @test
     */
    public function canBeCreated(): void
    {
        $subject = new OpeningHours();

        self::assertInstanceOf(OpeningHours::class, $subject);
    }

    /**
     * @test
     */
    public function returnsEmptyArrayIfOpeningHoursAreMissing(): void
    {
        $subject = new OpeningHours();

        $result = $subject->get([
        ]);

        self::assertSame([], $result);
    }

    /**
     * @test
     */
    public function returnsSingleOpeningHourWrappedInArray(): void
    {
        $subject = new OpeningHours();

        $result = $subject->get([
            'schema:openingHoursSpecification' => [
                '@id' => 'genid-28b33237f71b41e3ad54a99e1da769b9-b13',
                'schema:opens' => [
                    '@type' => 'schema:Time',
                    '@value' => '10:00:00',
                ],
                'schema:closes' => [
                    '@type' => 'schema:Time',
                    '@value' => '18:00:00',
                ],
                'schema:validFrom' => [
                    '@type' => 'schema:Date',
                    '@value' => '2021-03-01',
                ],
                'schema:validThrough' => [
                    '@type' => 'schema:Date',
                    '@value' => '2021-12-31',
                ],
                'schema:dayOfWeek' => [
                    0 => [
                        '@type' => 'schema:DayOfWeek',
                        '@value' => 'schema:Saturday',
                    ],
                    1 => [
                        '@type' => 'schema:DayOfWeek',
                        '@value' => 'schema:Sunday',
                    ],
                    2 => [
                        '@type' => 'schema:DayOfWeek',
                        '@value' => 'schema:Friday',
                    ],
                    3 => [
                        '@type' => 'schema:DayOfWeek',
                        '@value' => 'schema:Thursday',
                    ],
                    4 => [
                        '@type' => 'schema:DayOfWeek',
                        '@value' => 'schema:Tuesday',
                    ],
                    5 => [
                        '@type' => 'schema:DayOfWeek',
                        '@value' => 'schema:Wednesday',
                    ],
                ],
            ],
        ]);

        self::assertCount(1, $result);
        self::assertSame('10:00:00', $result[0]['opens']);
        self::assertSame('18:00:00', $result[0]['closes']);
        self::assertSame([
            'Friday',
            'Saturday',
            'Sunday',
            'Thursday',
            'Tuesday',
            'Wednesday',
        ], $result[0]['daysOfWeek']);
        self::assertInstanceOf(\DateTimeImmutable::class, $result[0]['from']);
        self::assertSame('2021-03-01 00:00:00', $result[0]['from']->format('Y-m-d H:i:s'));

        self::assertInstanceOf(\DateTimeImmutable::class, $result[0]['through']);
        self::assertSame('2021-12-31 00:00:00', $result[0]['through']->format('Y-m-d H:i:s'));
    }

    /**
     * @test
     */
    public function returnsSingleWeekDay(): void
    {
        $subject = new OpeningHours();

        $result = $subject->get([
            'schema:openingHoursSpecification' => [
                '@id' => 'genid-28b33237f71b41e3ad54a99e1da769b9-b13',
                'schema:dayOfWeek' => [
                    '@type' => 'schema:DayOfWeek',
                    '@value' => 'schema:Saturday',
                ],
            ],
        ]);

        self::assertCount(1, $result);
        self::assertSame([
            'Saturday',
        ], $result[0]['daysOfWeek']);
    }

    /**
     * @test
     */
    public function returnsMultipleOpeningHours(): void
    {
        $subject = new OpeningHours();

        $result = $subject->get([
            'schema:openingHoursSpecification' => [
                [
                    '@id' => 'genid-28b33237f71b41e3ad54a99e1da769b9-b13',
                    'schema:opens' => [
                        '@type' => 'schema:Time',
                        '@value' => '10:00:00',
                    ],
                    'schema:closes' => [
                        '@type' => 'schema:Time',
                        '@value' => '18:00:00',
                    ],
                    'schema:validFrom' => [
                        '@type' => 'schema:Date',
                        '@value' => '2021-03-01',
                    ],
                    'schema:validThrough' => [
                        '@type' => 'schema:Date',
                        '@value' => '2021-12-31',
                    ],
                    'schema:dayOfWeek' => [
                        0 => [
                            '@type' => 'schema:DayOfWeek',
                            '@value' => 'schema:Saturday',
                        ],
                        1 => [
                            '@type' => 'schema:DayOfWeek',
                            '@value' => 'schema:Sunday',
                        ],
                        2 => [
                            '@type' => 'schema:DayOfWeek',
                            '@value' => 'schema:Friday',
                        ],
                        3 => [
                            '@type' => 'schema:DayOfWeek',
                            '@value' => 'schema:Thursday',
                        ],
                        4 => [
                            '@type' => 'schema:DayOfWeek',
                            '@value' => 'schema:Tuesday',
                        ],
                        5 => [
                            '@type' => 'schema:DayOfWeek',
                            '@value' => 'schema:Wednesday',
                        ],
                    ],
                ],
                [
                    '@id' => 'genid-28b33237f71b41e3ad54a99e1da769b9-b13',
                    'schema:opens' => [
                        '@type' => 'schema:Time',
                        '@value' => '09:00:00',
                    ],
                    'schema:closes' => [
                        '@type' => 'schema:Time',
                        '@value' => '17:00:00',
                    ],
                    'schema:validFrom' => [
                        '@type' => 'schema:Date',
                        '@value' => '2022-03-01',
                    ],
                    'schema:validThrough' => [
                        '@type' => 'schema:Date',
                        '@value' => '2022-12-31',
                    ],
                    'schema:dayOfWeek' => [
                        0 => [
                            '@type' => 'schema:DayOfWeek',
                            '@value' => 'schema:Saturday',
                        ],
                        1 => [
                            '@type' => 'schema:DayOfWeek',
                            '@value' => 'schema:Sunday',
                        ],
                        2 => [
                            '@type' => 'schema:DayOfWeek',
                            '@value' => 'schema:Friday',
                        ],
                        3 => [
                            '@type' => 'schema:DayOfWeek',
                            '@value' => 'schema:Thursday',
                        ],
                        4 => [
                            '@type' => 'schema:DayOfWeek',
                            '@value' => 'schema:Tuesday',
                        ],
                        5 => [
                            '@type' => 'schema:DayOfWeek',
                            '@value' => 'schema:Wednesday',
                        ],
                    ],
                ],
            ],
        ]);

        self::assertCount(2, $result);

        self::assertSame('10:00:00', $result[0]['opens']);
        self::assertSame('18:00:00', $result[0]['closes']);
        self::assertSame([
            'Friday',
            'Saturday',
            'Sunday',
            'Thursday',
            'Tuesday',
            'Wednesday',
        ], $result[0]['daysOfWeek']);
        self::assertInstanceOf(\DateTimeImmutable::class, $result[0]['from']);
        self::assertSame('2021-03-01 00:00:00', $result[0]['from']->format('Y-m-d H:i:s'));

        self::assertInstanceOf(\DateTimeImmutable::class, $result[0]['through']);
        self::assertSame('2021-12-31 00:00:00', $result[0]['through']->format('Y-m-d H:i:s'));

        self::assertSame('09:00:00', $result[1]['opens']);
        self::assertSame('17:00:00', $result[1]['closes']);
        self::assertSame([
            'Friday',
            'Saturday',
            'Sunday',
            'Thursday',
            'Tuesday',
            'Wednesday',
        ], $result[1]['daysOfWeek']);
        self::assertInstanceOf(\DateTimeImmutable::class, $result[1]['from']);
        self::assertSame('2022-03-01 00:00:00', $result[1]['from']->format('Y-m-d H:i:s'));

        self::assertInstanceOf(\DateTimeImmutable::class, $result[1]['through']);
        self::assertSame('2022-12-31 00:00:00', $result[1]['through']->format('Y-m-d H:i:s'));
    }

    /**
     * @test
     */
    public function returnsProperDefaultsOnMissingValues(): void
    {
        $subject = new OpeningHours();

        $result = $subject->get([
            'schema:openingHoursSpecification' => [
                '@id' => 'genid-28b33237f71b41e3ad54a99e1da769b9-b13',
            ],
        ]);

        self::assertCount(1, $result);
        self::assertSame('', $result[0]['opens']);
        self::assertSame('', $result[0]['closes']);
        self::assertSame([], $result[0]['daysOfWeek']);
        self::assertNull($result[0]['from']);
        self::assertNull($result[0]['through']);
    }
}
