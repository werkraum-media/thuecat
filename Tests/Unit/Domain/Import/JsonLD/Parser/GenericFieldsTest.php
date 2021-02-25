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
use WerkraumMedia\ThueCat\Domain\Import\JsonLD\Parser\GenericFields;
use WerkraumMedia\ThueCat\Domain\Import\JsonLD\Parser\LanguageValues;

/**
 * @covers WerkraumMedia\ThueCat\Domain\Import\JsonLD\Parser\GenericFields
 */
class GenericFieldsTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function canBeCreated(): void
    {
        $languageValues = $this->prophesize(LanguageValues::class);

        $subject = new GenericFields(
            $languageValues->reveal()
        );

        self::assertInstanceOf(GenericFields::class, $subject);
    }

    /**
     * @test
     */
    public function returnsTitle(): void
    {
        $siteLanguage = $this->prophesize(SiteLanguage::class);

        $languageValues = $this->prophesize(LanguageValues::class);
        $languageValues->getValueForLanguage([
            '@value' => 'DE Title',
        ], $siteLanguage->reveal())->willReturn('DE Title');

        $subject = new GenericFields(
            $languageValues->reveal()
        );

        $result = $subject->getTitle([
            'schema:name' => [
                '@value' => 'DE Title',
            ],
        ], $siteLanguage->reveal());

        self::assertSame('DE Title', $result);
    }

    /**
     * @test
     */
    public function returnsDescription(): void
    {
        $siteLanguage = $this->prophesize(SiteLanguage::class);

        $languageValues = $this->prophesize(LanguageValues::class);
        $languageValues->getValueForLanguage([
            '@value' => 'DE Description',
        ], $siteLanguage->reveal())->willReturn('DE Description');

        $subject = new GenericFields(
            $languageValues->reveal()
        );

        $result = $subject->getDescription([
            'schema:description' => [
                '@value' => 'DE Description',
            ],
        ], $siteLanguage->reveal());

        self::assertSame('DE Description', $result);
    }
}
