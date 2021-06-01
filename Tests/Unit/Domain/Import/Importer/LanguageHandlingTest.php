<?php

namespace WerkraumMedia\ThueCat\Tests\Unit\Domain\Import\Importer;

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
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
use WerkraumMedia\ThueCat\Domain\Import\Importer\LanguageHandling;

/**
 * @covers \WerkraumMedia\ThueCat\Domain\Import\Importer\LanguageHandling
 */
class LanguageHandlingTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function canBeCreated(): void
    {
        $siteFinder = $this->prophesize(SiteFinder::class);

        $subject = new LanguageHandling(
            $siteFinder->reveal()
        );

        self::assertInstanceOf(LanguageHandling::class, $subject);
    }

    /**
     * @test
     */
    public function returnsAllLanguagesForGivenPageUid(): void
    {
        $language = $this->prophesize(SiteLanguage::class);
        $language->getTwoLetterIsoCode()->willReturn('de');
        $language->getLanguageId()->willReturn(2);

        $site = $this->prophesize(Site::class);
        $site->getLanguages()->willReturn([$language->reveal()]);

        $siteFinder = $this->prophesize(SiteFinder::class);
        $siteFinder->getSiteByPageId(10)->willReturn($site->reveal());

        $subject = new LanguageHandling(
            $siteFinder->reveal()
        );

        $result = $subject->getLanguages(10);

        self::assertCount(1, $result);
        self::assertSame(2, $result[0]->getLanguageId());
        self::assertSame('de', $result[0]->getTwoLetterIsoCode());
    }

    /**
     * @test
     */
    public function returnsDefaultLanguageForGivenPageUid(): void
    {
        $language = $this->prophesize(SiteLanguage::class);
        $language->getTwoLetterIsoCode()->willReturn('de');
        $language->getLanguageId()->willReturn(2);

        $site = $this->prophesize(Site::class);
        $site->getDefaultLanguage()->willReturn($language->reveal());

        $siteFinder = $this->prophesize(SiteFinder::class);
        $siteFinder->getSiteByPageId(10)->willReturn($site->reveal());

        $subject = new LanguageHandling(
            $siteFinder->reveal()
        );

        $result = $subject->getDefaultLanguage(10);

        self::assertInstanceOf(SiteLanguage::class, $result);
        self::assertSame(2, $result->getLanguageId());
        self::assertSame('de', $result->getTwoLetterIsoCode());
    }
}
