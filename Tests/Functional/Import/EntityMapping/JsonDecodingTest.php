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

namespace WerkraumMedia\ThueCat\Tests\Functional\Import\EntityMapping;

use PHPUnit\Framework\TestCase;
use WerkraumMedia\ThueCat\Domain\Import\EntityMapper\JsonDecode;

/**
 * @covers \WerkraumMedia\ThueCat\Domain\Import\EntityMapper\JsonDecode;
 */
class JsonDecodingTest extends TestCase
{
    /**
     * @test
     */
    public function canBeCreated(): void
    {
        $subject = new JsonDecode();

        self::assertInstanceOf(
            JsonDecode::class,
            $subject
        );
    }

    /**
     * @test
     */
    public function decodesPropertyWithMultipleLanguagesProvidingActiveOne(): void
    {
        $subject = new JsonDecode();
        $result = $subject->decode((string) json_encode([
            'schema:name' => [
                [
                    '@language' => 'de',
                    '@value' => 'Deutscher Text',
                ],
                [
                    '@language' => 'en',
                    '@value' => 'English Text',
                ],
            ],
        ]), 'json', [
            JsonDecode::ACTIVE_LANGUAGE => 'en',
        ]);

        self::assertSame([
            'name' => 'English Text',
        ], $result);
    }

    /**
     * @test
     */
    public function decodesPropertyWithSingleLanguageMatchingActive(): void
    {
        $subject = new JsonDecode();
        $result = $subject->decode((string) json_encode([
            'schema:name' => [
                '@language' => 'en',
                '@value' => 'English Text',
            ],
        ]), 'json', [
            JsonDecode::ACTIVE_LANGUAGE => 'en',
        ]);

        self::assertSame([
            'name' => 'English Text',
        ], $result);
    }

    /**
     * @test
     */
    public function decodesPropertyWithSingleLanguageNotMatchingActive(): void
    {
        $subject = new JsonDecode();
        $result = $subject->decode((string) json_encode([
            'schema:name' => [
                '@language' => 'de',
                '@value' => 'German Text',
            ],
        ]), 'json', [
            JsonDecode::ACTIVE_LANGUAGE => 'en',
        ]);

        self::assertSame([
            'name' => '',
        ], $result);
    }

    /**
     * @test
     */
    public function decodesPropertyWithMultipleLanguagesAndFormatsProvidingActiveLanguage(): void
    {
        $subject = new JsonDecode();
        $result = $subject->decode((string) json_encode([
            'schema:description' => [
                0 => [
                    '@language' => 'en',
                    '@value' => 'English plain',
                ],
                1 => [
                    '@language' => 'de',
                    '@value' => 'Deutsch plain',
                ],
                2 => [
                    '@id' => 'genid-7bb7d92bd6624bdf84634c86e8acdbb4-b1',
                    '@type' => [
                        0 => 'thuecat:Html',
                    ],
                    'schema:value' => [
                        '@language' => 'de',
                        '@value' => 'Deutsch HTML',
                    ],
                ],
                3 => [
                    '@id' => 'genid-7bb7d92bd6624bdf84634c86e8acdbb4-b2',
                    '@type' => [
                        0 => 'thuecat:Html',
                    ],
                    'schema:value' => [
                        '@language' => 'en',
                        '@value' => 'English HTML',
                    ],
                ],
            ],
        ]), 'json', [
            JsonDecode::ACTIVE_LANGUAGE => 'en',
        ]);

        self::assertSame([
            'description' => 'English plain',
        ], $result);
    }

    /**
     * @test
     */
    public function decodesSingleValueNotRelatedToLanguage(): void
    {
        $subject = new JsonDecode();
        $result = $subject->decode((string) json_encode([
            'schema:geo' => [
                'schema:latitude' => [
                    '@type' => 'schema:Number',
                    '@value' => '50.978772',
                ],
                'schema:longitude' => [
                    '@type' => 'schema:Number',
                    '@value' => '11.031622',
                ],
            ],
        ]), 'json', [
            JsonDecode::ACTIVE_LANGUAGE => 'en',
        ]);

        self::assertSame([
            'geo' => [
                'latitude' => '50.978772',
                'longitude' => '11.031622',
            ],
        ], $result);
    }

    /**
     * @test
     */
    public function decodesNestedObjectStructures(): void
    {
        $subject = new JsonDecode();
        $result = $subject->decode((string) json_encode([
            '@id' => 'https://thuecat.org/resources/835224016581-dara',
            'schema:name' => [
                '@language' => 'en',
                '@value' => 'Cathedral of St. Mary',
            ],
            'schema:photo' => [
                '@id' => 'https://thuecat.org/resources/dms_5159216',
            ],
            'schema:image' => [
                0 => [
                    '@id' => 'https://thuecat.org/resources/dms_5159186',
                ],
                1 => [
                    '@id' => 'https://thuecat.org/resources/dms_5159216',
                ],
            ],
            'thuecat:contentResponsible' => [
                '@id' => 'https://thuecat.org/resources/018132452787-ngbe',
            ],
        ]), 'json', [
            JsonDecode::ACTIVE_LANGUAGE => 'en',
        ]);

        self::assertSame([
            'id' => 'https://thuecat.org/resources/835224016581-dara',
            'name' => 'Cathedral of St. Mary',
            'photo' => [
                'id' => 'https://thuecat.org/resources/dms_5159216',
            ],
            'image' => [
                0 => [
                    'id' => 'https://thuecat.org/resources/dms_5159186',
                ],
                1 => [
                    'id' => 'https://thuecat.org/resources/dms_5159216',
                ],
            ],
            'contentResponsible' => [
                'id' => 'https://thuecat.org/resources/018132452787-ngbe',
            ],
        ], $result);
    }

    /**
     * @test
     */
    public function decodesOpeningHours(): void
    {
        $subject = new JsonDecode();
        $result = $subject->decode((string) json_encode([
            'schema:openingHoursSpecification' => [
                0 => [
                    '@id' => 'genid-7bb7d92bd6624bdf84634c86e8acdbb4-b4',
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
                    '@id' => 'genid-7bb7d92bd6624bdf84634c86e8acdbb4-b7',
                    'schema:closes' => [
                        '@type' => 'schema:Time',
                        '@value' => '17:00:00',
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
                        '@value' => '2021-11-01',
                    ],
                    'schema:validThrough' => [
                        '@type' => 'schema:Date',
                        '@value' => '2022-04-30',
                    ],
                ],
            ],
        ]), 'json', [
            JsonDecode::ACTIVE_LANGUAGE => 'en',
        ]);

        self::assertSame([
            'openingHoursSpecification' => [
                0 => [
                    'id' => 'genid-7bb7d92bd6624bdf84634c86e8acdbb4-b4',
                    'closes' => '18:00:00',
                    'dayOfWeek' => [
                        0 => 'schema:Saturday',
                        1 => 'schema:Friday',
                        2 => 'schema:Thursday',
                        3 => 'schema:Tuesday',
                        4 => 'schema:Monday',
                        5 => 'schema:Wednesday',
                    ],
                    'opens' => '09:30:00',
                    'validFrom' => '2021-05-01',
                    'validThrough' => '2021-10-31',
                ],
                1 => [
                    'id' => 'genid-7bb7d92bd6624bdf84634c86e8acdbb4-b7',
                    'closes' => '17:00:00',
                    'dayOfWeek' => 'schema:Sunday',
                    'opens' => '13:00:00',
                    'validFrom' => '2021-11-01',
                    'validThrough' => '2022-04-30',
                ],
            ],
        ], $result);
    }
}
