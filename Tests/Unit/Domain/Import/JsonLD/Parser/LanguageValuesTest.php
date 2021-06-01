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
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use WerkraumMedia\ThueCat\Domain\Import\JsonLD\Parser\LanguageValues;

/**
 * @covers \WerkraumMedia\ThueCat\Domain\Import\JsonLD\Parser\LanguageValues
 */
class LanguageValuesTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function canBeCreated(): void
    {
        $subject = new LanguageValues(
        );

        self::assertInstanceOf(LanguageValues::class, $subject);
    }

    /**
     * @test
     * @dataProvider setups
     */
    public function returnsValue(array $jsonLD, string $language, string $expected): void
    {
        $siteLanguage = $this->prophesize(SiteLanguage::class);
        $siteLanguage->getTwoLetterIsoCode()->willReturn($language);

        $subject = new LanguageValues(
        );

        $result = $subject->getValueForLanguage($jsonLD, $siteLanguage->reveal());

        self::assertSame($expected, $result);
    }

    public function setups(): array
    {
        return [
            'has multiple lanugages, one matches' => [
                'jsonLD' => [
                    [
                        '@language' => 'de',
                        '@value' => 'DE value',
                    ],
                    [
                        '@language' => 'fr',
                        '@value' => 'FR value',
                    ],
                ],
                'language' => 'de',
                'expected' => 'DE value',
            ],
            'has multiple languages, none matches' => [
                'jsonLD' => [
                    [
                        '@language' => 'de',
                        '@value' => 'DE value',
                    ],
                    [
                        '@language' => 'fr',
                        '@value' => 'FR value',
                    ],
                ],
                'language' => 'en',
                'expected' => '',
            ],
            'has multiple languages, missing @language key' => [
                'jsonLD' => [
                    [
                        '@value' => 'DE value',
                    ],
                    [
                        '@value' => 'FR value',
                    ],
                ],
                'language' => 'en',
                'expected' => '',
            ],
            'has single language, that one matches' => [
                'jsonLD' => [
                    '@language' => 'de',
                    '@value' => 'DE value',
                ],
                'language' => 'de',
                'expected' => 'DE value',
            ],
            'has single language, but another is requested' => [
                'jsonLD' => [
                    '@language' => 'de',
                    '@value' => 'DE value',
                ],
                'language' => 'en',
                'expected' => '',
            ],
            'has single language, missing @language key' => [
                'jsonLD' => [
                    '@value' => 'DE value',
                ],
                'language' => '',
                'expected' => '',
            ],
        ];
    }
}
