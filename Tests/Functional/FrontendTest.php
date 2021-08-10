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

namespace WerkraumMedia\ThueCat\Tests\Functional;

use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * @covers \
 */
class FrontendTest extends FunctionalTestCase
{
    protected $coreExtensionsToLoad = [
        'fluid_styled_content',
    ];

    protected $testExtensionsToLoad = [
        'typo3conf/ext/thuecat/',
    ];

    protected $pathsToLinkInTestInstance = [
        'typo3conf/ext/thuecat/Tests/Functional/Fixtures/Frontend/Sites/' => 'typo3conf/sites',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importDataSet('EXT:thuecat/Tests/Functional/Fixtures/Frontend/Content.xml');
        $this->setUpFrontendRootPage(1, [
            'EXT:thuecat/Tests/Functional/Fixtures/Frontend/Rendering.typoscript',
            'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
            'EXT:thuecat/Configuration/TypoScript/ContentElements/setup.typoscript',
        ]);
    }

    /**
     * @test
     */
    public function touristAttractionContentElementIsRendered(): void
    {
        $this->importDataSet('EXT:thuecat/Tests/Functional/Fixtures/Frontend/TouristAttractions.xml');

        $request = new InternalRequest();
        $request = $request->withPageId(2);

        $result = $this->executeFrontendRequest($request);

        self::assertSame(200, $result->getStatusCode());

        self::assertStringContainsString('Erste Attraktion (Beispielstadt)', (string)$result->getBody());
        self::assertStringContainsString('Die Beschreibung der Attraktion', (string)$result->getBody());

        self::assertStringContainsString('Highlight', (string)$result->getBody());

        self::assertStringContainsString('<img src="https://cms.thuecat.org/o/adaptive-media/image/5159216/Preview-1280x0/image" />', (string)$result->getBody());

        self::assertStringContainsString('Beispielstraße 1a', (string)$result->getBody());
        self::assertStringContainsString('99084', (string)$result->getBody());
        self::assertStringContainsString('Beispielstadt', (string)$result->getBody());
        self::assertStringContainsString('example@example.com', (string)$result->getBody());
        self::assertStringContainsString('(0)30 23125 000', (string)$result->getBody());

        self::assertStringContainsString('Monday: 09:30:00 - 18:00:00', (string)$result->getBody());

        self::assertStringContainsString('Führungen', (string)$result->getBody());
        self::assertStringContainsString('Immer samstags, um 11:15 Uhr findet eine öffentliche Führung durch das Museum statt. Dauer etwa 90 Minuten', (string)$result->getBody());

        self::assertStringContainsString('Erwachsene', (string)$result->getBody());
        self::assertStringContainsString('8,00 EUR', (string)$result->getBody());
        self::assertStringContainsString('pro Person', (string)$result->getBody());

        self::assertStringContainsString('11. Jh', (string)$result->getBody());

        self::assertStringContainsString('Toilette', (string)$result->getBody());
        self::assertStringContainsString('Behindertentoilette', (string)$result->getBody());
        self::assertStringContainsString('Wickelplatz', (string)$result->getBody());
        self::assertStringContainsString('familien- / kindgerecht', (string)$result->getBody());
    }

    /**
     * @test
     */
    public function pricesAreSorted(): void
    {
        $this->importDataSet('EXT:thuecat/Tests/Functional/Fixtures/Frontend/TouristAttractionWithPrices.xml');

        $request = new InternalRequest();
        $request = $request->withPageId(2);

        $result = $this->executeFrontendRequest($request);

        self::assertSame(200, $result->getStatusCode());

        self::assertStringContainsString('Attraktion mit Preisen', (string)$result->getBody());

        self::assertLessThan(
            mb_strpos((string)$result->getBody(), 'Familienkarte A'),
            mb_strpos((string)$result->getBody(), 'Erwachsene'),
            '"Familienkarte A" is rendered before "Erwachsene"'
        );
        self::assertLessThan(
            mb_strpos((string)$result->getBody(), 'Familienkarte B'),
            mb_strpos((string)$result->getBody(), 'Familienkarte A'),
            '"Familienkarte B" is rendered before "Familienkarte A"'
        );
        self::assertLessThan(
            mb_strpos((string)$result->getBody(), 'Schulklassen'),
            mb_strpos((string)$result->getBody(), 'Familienkarte B'),
            '"Schulklassen" is rendered before "Familienkarte B"'
        );
    }
}
