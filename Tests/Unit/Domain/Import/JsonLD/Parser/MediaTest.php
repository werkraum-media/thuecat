<?php

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
use Prophecy\PhpUnit\ProphecyTrait;
use WerkraumMedia\ThueCat\Domain\Import\Importer\FetchData;
use WerkraumMedia\ThueCat\Domain\Import\JsonLD\Parser\Media;

/**
 * @covers \WerkraumMedia\ThueCat\Domain\Import\JsonLD\Parser\Media
 */
class MediaTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function canBeCreated(): void
    {
        $fetchData = $this->prophesize(FetchData::class);

        $subject = new Media(
            $fetchData->reveal()
        );

        self::assertInstanceOf(Media::class, $subject);
    }

    /**
     * @test
     */
    public function returnsFallback(): void
    {
        $fetchData = $this->prophesize(FetchData::class);

        $subject = new Media(
            $fetchData->reveal()
        );

        $result = $subject->get([]);

        self::assertSame([], $result);
    }

    /**
     * @test
     */
    public function returnsPhotoAndImage(): void
    {
        $fetchData = $this->prophesize(FetchData::class);

        $fetchData->jsonLDFromUrl('https://thuecat.org/resources/dms_5099196')->willReturn([
            '@graph' => [
                0 => [
                    'schema:name' => [
                        '@language' => 'de',
                        '@value' => 'Erfurt-Alte Synagoge',
                    ],
                    'schema:description' => [
                        '@language' => 'de',
                        '@value' => 'Frontaler Blick auf die Hausfront/Hausfassade im Innenhof mit Zugang über die Waagegasse',
                    ],
                    'schema:copyrightYear' => [
                        '@language' => 'de',
                        '@value' => '2009',
                    ],
                    'schema:url' => [
                        '@type' => 'xsd:anyURI',
                        '@value' => 'https://cms.thuecat.org/o/adaptive-media/image/5099196/Preview-1280x0/image',
                    ],
                    'schema:license' => [
                        '@language' => 'de',
                        '@value' => 'https://creativecommons.org/licenses/by/4.0/',
                    ],
                    'thuecat:licenseAuthor' => [
                        '@language' => 'de',
                        '@value' => 'F:\Bilddatenbank\Museen und Ausstellungen\Alte Synagoge',
                    ],
                ],
            ],
        ]);

        $subject = new Media(
            $fetchData->reveal()
        );

        $result = $subject->get([
            'schema:photo' => [
                '@id' => 'https://thuecat.org/resources/dms_5099196',
            ],
            'schema:image' => [
                '@id' => 'https://thuecat.org/resources/dms_5099196',
            ],
        ]);

        self::assertSame([
            [
                'mainImage' => true,
                'type' => 'image',
                'title' => 'Erfurt-Alte Synagoge',
                'description' => 'Frontaler Blick auf die Hausfront/Hausfassade im Innenhof mit Zugang über die Waagegasse',
                'url' => 'https://cms.thuecat.org/o/adaptive-media/image/5099196/Preview-1280x0/image',
                'copyrightYear' => 2009,
                'license' => [
                    'type' => 'https://creativecommons.org/licenses/by/4.0/',
                    'author' => 'F:\Bilddatenbank\Museen und Ausstellungen\Alte Synagoge',
                ],
            ],
            [
                'mainImage' => false,
                'type' => 'image',
                'title' => 'Erfurt-Alte Synagoge',
                'description' => 'Frontaler Blick auf die Hausfront/Hausfassade im Innenhof mit Zugang über die Waagegasse',
                'url' => 'https://cms.thuecat.org/o/adaptive-media/image/5099196/Preview-1280x0/image',
                'copyrightYear' => 2009,
                'license' => [
                    'type' => 'https://creativecommons.org/licenses/by/4.0/',
                    'author' => 'F:\Bilddatenbank\Museen und Ausstellungen\Alte Synagoge',
                ],
            ],
        ], $result);
    }

    /**
     * @test
     */
    public function returnsMultipleImages(): void
    {
        $fetchData = $this->prophesize(FetchData::class);

        $fetchData->jsonLDFromUrl('https://thuecat.org/resources/dms_5159186')->willReturn([
            '@graph' => [
                0 => [
                    'schema:description' => [
                        '@language' => 'de',
                        '@value' => 'Sicht auf Dom St. Marien, St. Severikirche sowie die davor liegenden Klostergebäude und einem Ausschnitt des Biergartens umgeben von einem dämmerungsverfärten Himmel',
                    ],
                    'schema:name' => [
                        '@language' => 'de',
                        '@value' => 'Erfurt-Dom-und-Severikirche.jpg',
                    ],
                    'schema:url' => [
                        '@type' => 'xsd:anyURI',
                        '@value' => 'https://cms.thuecat.org/o/adaptive-media/image/5159186/Preview-1280x0/image',
                    ],
                    'schema:copyrightYear' => [
                        '@language' => 'de',
                        '@value' => '2020',
                    ],
                    'schema:license' => [
                        '@language' => 'de',
                        '@value' => 'https://creativecommons.org/licenses/by/4.0/',
                    ],
                    'thuecat:licenseAuthor' => [
                        '@language' => 'de',
                        '@value' => '',
                    ],
                ],
            ],
        ]);
        $fetchData->jsonLDFromUrl('https://thuecat.org/resources/dms_5159216')->willReturn([
            '@graph' => [
                0 => [
                    'schema:name' => [
                        '@language' => 'de',
                        '@value' => 'Erfurt-Dom und Severikirche-beleuchtet.jpg',
                    ],
                    'schema:copyrightYear' => [
                        '@language' => 'de',
                        '@value' => '2016',
                    ],
                    'schema:url' => [
                        '@type' => 'xsd:anyURI',
                        '@value' => 'https://cms.thuecat.org/o/adaptive-media/image/5159216/Preview-1280x0/image',
                    ],
                    'schema:license' => [
                        '@language' => 'de',
                        '@value' => 'https://creativecommons.org/licenses/by/4.0/',
                    ],
                    'thuecat:licenseAuthor' => [
                        '@language' => 'de',
                        '@value' => '',
                    ],
                ],
            ],
        ]);

        $subject = new Media(
            $fetchData->reveal()
        );

        $result = $subject->get([
            'schema:image' => [
                0 => [
                    '@id' => 'https://thuecat.org/resources/dms_5159186',
                ],
                1 => [
                    '@id' => 'https://thuecat.org/resources/dms_5159216',
                ],
            ],
        ]);

        self::assertSame([
            [
                'mainImage' => false,
                'type' => 'image',
                'title' => 'Erfurt-Dom-und-Severikirche.jpg',
                'description' => 'Sicht auf Dom St. Marien, St. Severikirche sowie die davor liegenden Klostergebäude und einem Ausschnitt des Biergartens umgeben von einem dämmerungsverfärten Himmel',
                'url' => 'https://cms.thuecat.org/o/adaptive-media/image/5159186/Preview-1280x0/image',
                'copyrightYear' => 2020,
                'license' => [
                    'type' => 'https://creativecommons.org/licenses/by/4.0/',
                    'author' => '',
                ],
            ],
            [
                'mainImage' => false,
                'type' => 'image',
                'title' => 'Erfurt-Dom und Severikirche-beleuchtet.jpg',
                'description' => '',
                'url' => 'https://cms.thuecat.org/o/adaptive-media/image/5159216/Preview-1280x0/image',
                'copyrightYear' => 2016,
                'license' => [
                    'type' => 'https://creativecommons.org/licenses/by/4.0/',
                    'author' => '',
                ],
            ],
        ], $result);
    }
}
