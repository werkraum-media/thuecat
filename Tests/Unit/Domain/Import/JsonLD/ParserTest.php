<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Unit\Domain\Import\JsonLD;

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
use WerkraumMedia\ThueCat\Domain\Import\JsonLD\Parser;
use WerkraumMedia\ThueCat\Domain\Import\JsonLD\Parser\Address;
use WerkraumMedia\ThueCat\Domain\Import\JsonLD\Parser\GenericFields;
use WerkraumMedia\ThueCat\Domain\Import\JsonLD\Parser\Media;
use WerkraumMedia\ThueCat\Domain\Import\JsonLD\Parser\OpeningHours;

/**
 * @covers WerkraumMedia\ThueCat\Domain\Import\JsonLD\Parser
 */
class ParserTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function canBeCreated(): void
    {
        $genericFields = $this->prophesize(GenericFields::class);
        $openingHours = $this->prophesize(OpeningHours::class);
        $address = $this->prophesize(Address::class);
        $media = $this->prophesize(Media::class);

        $subject = new Parser(
            $genericFields->reveal(),
            $openingHours->reveal(),
            $address->reveal(),
            $media->reveal()
        );

        self::assertInstanceOf(Parser::class, $subject);
    }

    /**
     * @test
     */
    public function returnsId(): void
    {
        $genericFields = $this->prophesize(GenericFields::class);
        $openingHours = $this->prophesize(OpeningHours::class);
        $address = $this->prophesize(Address::class);
        $media = $this->prophesize(Media::class);

        $subject = new Parser(
            $genericFields->reveal(),
            $openingHours->reveal(),
            $address->reveal(),
            $media->reveal()
        );

        $result = $subject->getId([
            '@id' => 'https://example.com/resources/165868194223-id',
        ]);

        self::assertSame('https://example.com/resources/165868194223-id', $result);
    }

    /**
     * @test
     */
    public function returnsTitle(): void
    {
        $jsonLD = [
            'schema:name' => [
                '@language' => 'de',
                '@value' => 'Erfurt',
            ],
        ];

        $genericFields = $this->prophesize(GenericFields::class);
        $genericFields->getTitle($jsonLD, 'de')->willReturn('Erfurt');

        $openingHours = $this->prophesize(OpeningHours::class);
        $address = $this->prophesize(Address::class);
        $media = $this->prophesize(Media::class);

        $subject = new Parser(
            $genericFields->reveal(),
            $openingHours->reveal(),
            $address->reveal(),
            $media->reveal()
        );

        $result = $subject->getTitle($jsonLD, 'de');

        self::assertSame('Erfurt', $result);
    }

    /**
     * @test
     */
    public function returnsDescription(): void
    {
        $jsonLD = [
            'schema:description' => [
                '@language' => 'de',
                '@value' => 'Erfurt',
            ],
        ];

        $genericFields = $this->prophesize(GenericFields::class);
        $genericFields->getDescription($jsonLD, 'de')->willReturn('Erfurt');

        $openingHours = $this->prophesize(OpeningHours::class);
        $address = $this->prophesize(Address::class);
        $media = $this->prophesize(Media::class);

        $subject = new Parser(
            $genericFields->reveal(),
            $openingHours->reveal(),
            $address->reveal(),
            $media->reveal()
        );

        $result = $subject->getDescription($jsonLD, 'de');

        self::assertSame('Erfurt', $result);
    }

    /**
     * @test
     */
    public function returnsManagerId(): void
    {
        $genericFields = $this->prophesize(GenericFields::class);
        $openingHours = $this->prophesize(OpeningHours::class);
        $address = $this->prophesize(Address::class);
        $media = $this->prophesize(Media::class);

        $subject = new Parser(
            $genericFields->reveal(),
            $openingHours->reveal(),
            $address->reveal(),
            $media->reveal()
        );

        $result = $subject->getManagerId([
            'thuecat:contentResponsible' => [
                '@id' => 'https://example.com/resources/165868194223-manager',
            ],
        ]);

        self::assertSame('https://example.com/resources/165868194223-manager', $result);
    }

    /**
     * @test
     */
    public function returnsContainedInPlaceIds(): void
    {
        $genericFields = $this->prophesize(GenericFields::class);
        $openingHours = $this->prophesize(OpeningHours::class);
        $address = $this->prophesize(Address::class);
        $media = $this->prophesize(Media::class);

        $subject = new Parser(
            $genericFields->reveal(),
            $openingHours->reveal(),
            $address->reveal(),
            $media->reveal()
        );

        $result = $subject->getContainedInPlaceIds([
            'schema:containedInPlace' => [
                ['@id' => 'https://thuecat.org/resources/043064193523-jcyt'],
                ['@id' => 'https://thuecat.org/resources/349986440346-kbkf'],
                ['@id' => 'https://thuecat.org/resources/794900260253-wjab'],
                ['@id' => 'https://thuecat.org/resources/476888881990-xpwq'],
                ['@id' => 'https://thuecat.org/resources/573211638937-gmqb'],
            ],
        ]);

        self::assertSame([
            'https://thuecat.org/resources/043064193523-jcyt',
            'https://thuecat.org/resources/349986440346-kbkf',
            'https://thuecat.org/resources/794900260253-wjab',
            'https://thuecat.org/resources/476888881990-xpwq',
            'https://thuecat.org/resources/573211638937-gmqb',
        ], $result);
    }

    /**
     * @test
     */
    public function returnsLanguages(): void
    {
        $genericFields = $this->prophesize(GenericFields::class);
        $openingHours = $this->prophesize(OpeningHours::class);
        $address = $this->prophesize(Address::class);
        $media = $this->prophesize(Media::class);

        $subject = new Parser(
            $genericFields->reveal(),
            $openingHours->reveal(),
            $address->reveal(),
            $media->reveal()
        );

        $result = $subject->getLanguages([
            'schema:availableLanguage' => [
                0 => [
                    '@type' => 'thuecat:Language',
                    '@value' => 'thuecat:German',
                ],
                1 => [
                    '@type' => 'thuecat:Language',
                    '@value' => 'thuecat:English',
                ],
                2 => [
                    '@type' => 'thuecat:Language',
                    '@value' => 'thuecat:French',
                ],
            ],
        ]);

        self::assertSame([
            'de',
            'en',
            'fr',
        ], $result);
    }

    /**
     * @test
     */
    public function throwsExceptionOnUnkownLanguage(): void
    {
        $genericFields = $this->prophesize(GenericFields::class);
        $openingHours = $this->prophesize(OpeningHours::class);
        $address = $this->prophesize(Address::class);
        $media = $this->prophesize(Media::class);

        $subject = new Parser(
            $genericFields->reveal(),
            $openingHours->reveal(),
            $address->reveal(),
            $media->reveal()
        );

        $this->expectExceptionCode(1612367481);
        $result = $subject->getLanguages([
            'schema:availableLanguage' => [
                0 => [
                    '@type' => 'thuecat:Language',
                    '@value' => 'thuecat:Unkown',
                ],
            ],
        ]);
    }

    /**
     * @test
     */
    public function returnsNoLanguagesIfInfoIsMissing(): void
    {
        $genericFields = $this->prophesize(GenericFields::class);
        $openingHours = $this->prophesize(OpeningHours::class);
        $address = $this->prophesize(Address::class);
        $media = $this->prophesize(Media::class);

        $subject = new Parser(
            $genericFields->reveal(),
            $openingHours->reveal(),
            $address->reveal(),
            $media->reveal()
        );

        $result = $subject->getLanguages([]);

        self::assertSame([], $result);
    }

    /**
     * @test
     */
    public function returnsOpeningHours(): void
    {
        $jsonLD = [
            'schema:openingHoursSpecification' => [
                '@id' => 'genid-28b33237f71b41e3ad54a99e1da769b9-b13',
                'schema:opens' => [
                    '@type' => 'schema:Time',
                    '@value' => '10:00:00',
                ],
            ],
        ];
        $generatedOpeningHours = [
            'opens' => '10:00:00',
            'closes' => '',
            'from' => null,
            'through' => null,
            'daysOfWeek' => [],
        ];

        $genericFields = $this->prophesize(GenericFields::class);
        $openingHours = $this->prophesize(OpeningHours::class);
        $openingHours->get($jsonLD)->willReturn($generatedOpeningHours);
        $address = $this->prophesize(Address::class);
        $media = $this->prophesize(Media::class);

        $subject = new Parser(
            $genericFields->reveal(),
            $openingHours->reveal(),
            $address->reveal(),
            $media->reveal()
        );

        $result = $subject->getOpeningHours($jsonLD);

        self::assertSame($generatedOpeningHours, $result);
    }

    /**
     * @test
     */
    public function returnsAddress(): void
    {
        $jsonLD = [
            'schema:address' => [
                '@id' => 'genid-28b33237f71b41e3ad54a99e1da769b9-b0',
                'schema:addressLocality' => [
                    '@language' => 'de',
                    '@value' => 'Erfurt',
                ],
            ],
        ];
        $generatedAddress = [
            'street' => '',
            'zip' => '',
            'city' => 'Erfurt',
            'email' => '',
            'phone' => '',
            'fax' => '',
        ];

        $genericFields = $this->prophesize(GenericFields::class);
        $openingHours = $this->prophesize(OpeningHours::class);
        $address = $this->prophesize(Address::class);
        $address->get($jsonLD)->willReturn($generatedAddress);
        $media = $this->prophesize(Media::class);

        $subject = new Parser(
            $genericFields->reveal(),
            $openingHours->reveal(),
            $address->reveal(),
            $media->reveal()
        );

        $result = $subject->getAddress($jsonLD);

        self::assertSame($generatedAddress, $result);
    }

    /**
     * @test
     */
    public function returnsMedia(): void
    {
        $jsonLD = [
            'schema:photo' => [
                '@id' => 'https://thuecat.org/resources/dms_5099196',
            ],
            'schema:image' => [
                '@id' => 'https://thuecat.org/resources/dms_5099196',
            ],
        ];
        $generatedMedia = [
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
        ];

        $genericFields = $this->prophesize(GenericFields::class);
        $openingHours = $this->prophesize(OpeningHours::class);
        $address = $this->prophesize(Address::class);
        $media = $this->prophesize(Media::class);
        $media->get($jsonLD)->willReturn($generatedMedia);

        $subject = new Parser(
            $genericFields->reveal(),
            $openingHours->reveal(),
            $address->reveal(),
            $media->reveal()
        );

        $result = $subject->getMedia($jsonLD);

        self::assertSame($generatedMedia, $result);
    }
}
