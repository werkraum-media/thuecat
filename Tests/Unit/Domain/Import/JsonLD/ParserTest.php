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
        $openingHours = $this->prophesize(OpeningHours::class);
        $subject = new Parser(
            $openingHours->reveal()
        );

        self::assertInstanceOf(Parser::class, $subject);
    }

    /**
     * @test
     */
    public function returnsId(): void
    {
        $openingHours = $this->prophesize(OpeningHours::class);
        $subject = new Parser(
            $openingHours->reveal()
        );
        $result = $subject->getId([
            '@id' => 'https://example.com/resources/165868194223-id',
        ]);

        self::assertSame('https://example.com/resources/165868194223-id', $result);
    }

    /**
     * @test
     */
    public function returnsManagerId(): void
    {
        $openingHours = $this->prophesize(OpeningHours::class);
        $subject = new Parser(
            $openingHours->reveal()
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
     * @dataProvider titles
     */
    public function returnsTitle(array $jsonLD, string $language, string $expected): void
    {
        $openingHours = $this->prophesize(OpeningHours::class);
        $subject = new Parser(
            $openingHours->reveal()
        );
        $result = $subject->getTitle($jsonLD, $language);
        self::assertSame($expected, $result);
    }

    public function titles(): array
    {
        return [
            'json has multiple lanugages, one matches' => [
                'jsonLD' => [
                    'schema:name' => [
                        [
                            '@language' => 'de',
                            '@value' => 'DE Title',
                        ],
                        [
                            '@language' => 'fr',
                            '@value' => 'FR Title',
                        ],
                    ],
                ],
                'language' => 'de',
                'expected' => 'DE Title',
            ],
            'json has multiple lanugages, no language specified' => [
                'jsonLD' => [
                    'schema:name' => [
                        [
                            '@language' => 'de',
                            '@value' => 'DE Title',
                        ],
                        [
                            '@language' => 'fr',
                            '@value' => 'FR Title',
                        ],
                    ],
                ],
                'language' => '',
                'expected' => 'DE Title',
            ],
            'json has multiple languages, none matches' => [
                'jsonLD' => [
                    'schema:name' => [
                        [
                            '@language' => 'de',
                            '@value' => 'DE Title',
                        ],
                        [
                            '@language' => 'fr',
                            '@value' => 'FR Title',
                        ],
                    ],
                ],
                'language' => 'en',
                'expected' => '',
            ],
            'json has multiple languages, missing @language key' => [
                'jsonLD' => [
                    'schema:name' => [
                        [
                            '@value' => 'DE Title',
                        ],
                        [
                            '@value' => 'FR Title',
                        ],
                    ],
                ],
                'language' => 'en',
                'expected' => '',
            ],
            'json has single language, that one matches' => [
                'jsonLD' => [
                    'schema:name' => [
                        '@language' => 'de',
                        '@value' => 'DE Title',
                    ],
                ],
                'language' => 'de',
                'expected' => 'DE Title',
            ],
            'json contains single language, but another is requested' => [
                'jsonLD' => [
                    'schema:name' => [
                        '@language' => 'de',
                        '@value' => 'DE Title',
                    ],
                ],
                'language' => 'en',
                'expected' => '',
            ],
            'json contains single language, no language specified' => [
                'jsonLD' => [
                    'schema:name' => [
                        '@language' => 'de',
                        '@value' => 'DE Title',
                    ],
                ],
                'language' => '',
                'expected' => 'DE Title',
            ],
            'json contains single language, missing @language key' => [
                'jsonLD' => [
                    'schema:name' => [
                        '@value' => 'DE Title',
                    ],
                ],
                'language' => '',
                'expected' => '',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider descriptions
     */
    public function returnsDescription(array $jsonLD, string $language, string $expected): void
    {
        $openingHours = $this->prophesize(OpeningHours::class);
        $subject = new Parser(
            $openingHours->reveal()
        );
        $result = $subject->getDescription($jsonLD, $language);
        self::assertSame($expected, $result);
    }

    public function descriptions(): array
    {
        return [
            'json has multiple lanugages, one matches' => [
                'jsonLD' => [
                    'schema:description' => [
                        [
                            '@language' => 'de',
                            '@value' => 'DE Description',
                        ],
                        [
                            '@language' => 'fr',
                            '@value' => 'FR Description',
                        ],
                    ],
                ],
                'language' => 'de',
                'expected' => 'DE Description',
            ],
            'json has multiple lanugages, no language specified' => [
                'jsonLD' => [
                    'schema:description' => [
                        [
                            '@language' => 'de',
                            '@value' => 'DE Description',
                        ],
                        [
                            '@language' => 'fr',
                            '@value' => 'FR Description',
                        ],
                    ],
                ],
                'language' => '',
                'expected' => 'DE Description',
            ],
            'json has multiple languages, none matches' => [
                'jsonLD' => [
                    'schema:description' => [
                        [
                            '@language' => 'de',
                            '@value' => 'DE Description',
                        ],
                        [
                            '@language' => 'fr',
                            '@value' => 'FR Description',
                        ],
                    ],
                ],
                'language' => 'en',
                'expected' => '',
            ],
            'json has multiple languages, missing @language key' => [
                'jsonLD' => [
                    'schema:description' => [
                        [
                            '@value' => 'DE Description',
                        ],
                        [
                            '@value' => 'FR Description',
                        ],
                    ],
                ],
                'language' => 'en',
                'expected' => '',
            ],
            'json has single language, that one matches' => [
                'jsonLD' => [
                    'schema:description' => [
                        '@language' => 'de',
                        '@value' => 'DE Description',
                    ],
                ],
                'language' => 'de',
                'expected' => 'DE Description',
            ],
            'json contains single language, but another is requested' => [
                'jsonLD' => [
                    'schema:description' => [
                        '@language' => 'de',
                        '@value' => 'DE Description',
                    ],
                ],
                'language' => 'en',
                'expected' => '',
            ],
            'json contains single language, no language specified' => [
                'jsonLD' => [
                    'schema:description' => [
                        '@language' => 'de',
                        '@value' => 'DE Description',
                    ],
                ],
                'language' => '',
                'expected' => 'DE Description',
            ],
            'json contains single language, missing @language key' => [
                'jsonLD' => [
                    'schema:description' => [
                        '@value' => 'DE Description',
                    ],
                ],
                'language' => '',
                'expected' => '',
            ],
        ];
    }

    /**
     * @test
     */
    public function returnsContainedInPlaceIds(): void
    {
        $openingHours = $this->prophesize(OpeningHours::class);
        $subject = new Parser(
            $openingHours->reveal()
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
        $openingHours = $this->prophesize(OpeningHours::class);
        $subject = new Parser(
            $openingHours->reveal()
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
        $openingHours = $this->prophesize(OpeningHours::class);
        $subject = new Parser(
            $openingHours->reveal()
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
        $openingHours = $this->prophesize(OpeningHours::class);
        $subject = new Parser(
            $openingHours->reveal()
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

        $openingHours = $this->prophesize(OpeningHours::class);
        $openingHours->get($jsonLD)->willReturn($generatedOpeningHours);

        $subject = new Parser(
            $openingHours->reveal()
        );

        $result = $subject->getOpeningHours($jsonLD);

        self::assertSame($generatedOpeningHours, $result);
    }
}
