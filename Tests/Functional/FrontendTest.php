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

use Codappix\Typo3PhpDatasets\TestingFramework;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class FrontendTest extends FunctionalTestCase
{
    use TestingFramework;

    protected function setUp(): void
    {
        $this->coreExtensionsToLoad = [
            'fluid_styled_content',
        ];

        $this->testExtensionsToLoad = [
            'werkraummedia/thuecat',
            'typo3conf/ext/thuecat/Tests/Functional/Fixtures/Frontend/Extensions/example/',
        ];

        $this->pathsToLinkInTestInstance = [
            'typo3conf/ext/thuecat/Tests/Functional/Fixtures/Frontend/Sites/' => 'typo3conf/sites',
        ];

        parent::setUp();

        $this->importPHPDataSet(__DIR__ . '/Fixtures/Frontend/Content.php');
        $this->setUpFrontendRootPage(1, [
            'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
            'EXT:thuecat/Configuration/TypoScript/ContentElements/setup.typoscript',
            'EXT:thuecat/Tests/Functional/Fixtures/Frontend/Rendering.typoscript',
        ]);
    }

    #[Test]
    public function touristAttractionContentElementIsRendered(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Frontend/TouristAttractions.php');

        $request = new InternalRequest();
        $request = $request->withPageId(2);

        $result = $this->executeFrontendSubRequest($request);

        self::assertSame(200, $result->getStatusCode());

        self::assertStringContainsString('Erste Attraktion (Beispielstadt)', (string)$result->getBody());
        self::assertStringContainsString('Die Beschreibung der Attraktion', (string)$result->getBody());

        self::assertStringContainsString('Highlight', (string)$result->getBody());

        self::assertStringContainsString('<img class="img-fluid" src="https://cms.thuecat.org/o/adaptive-media/image/5159216/Preview-1280x0/image" />', (string)$result->getBody());
        self::assertStringContainsString('ⓒ Image Author', (string)$result->getBody());

        self::assertStringContainsString('Beispielstraße 1a', (string)$result->getBody());
        self::assertStringContainsString('99084', (string)$result->getBody());
        self::assertStringContainsString('Beispielstadt', (string)$result->getBody());
        self::assertStringContainsString('example@example.com', (string)$result->getBody());
        self::assertStringContainsString('(0)30 23125 000', (string)$result->getBody());
        self::assertStringContainsString('https://example.com/attraction', (string)$result->getBody());

        self::assertStringContainsString('Führungen', (string)$result->getBody());
        self::assertStringContainsString('(Führung)', (string)$result->getBody());
        self::assertStringContainsString('Immer samstags, um 11:15 Uhr findet eine öffentliche Führung durch das Museum statt. Dauer etwa 90 Minuten', (string)$result->getBody());

        self::assertStringContainsString('Erwachsene', (string)$result->getBody());
        self::assertStringContainsString('(Eintritt)', (string)$result->getBody());
        self::assertStringContainsString('8,00 EUR', (string)$result->getBody());
        self::assertStringContainsString('pro Person', (string)$result->getBody());

        self::assertStringContainsString('11. Jh', (string)$result->getBody());

        self::assertStringContainsString('Toilette', (string)$result->getBody());
        self::assertStringContainsString('Behindertentoilette', (string)$result->getBody());
        self::assertStringContainsString('Wickelplatz', (string)$result->getBody());
        self::assertStringContainsString('familien- / kindgerecht', (string)$result->getBody());

        self::assertStringContainsString('Spielplatz', (string)$result->getBody());
        self::assertStringContainsString('Ruhezone mit Sitzmöglichkeit oder separate Sitzmöglichkeit', (string)$result->getBody());
        self::assertStringContainsString('Souvenirshop', (string)$result->getBody());
        self::assertStringContainsString('Spielecke / Spielbereich', (string)$result->getBody());

        self::assertStringContainsString('Museumsshop', (string)$result->getBody());
        self::assertStringContainsString('Pädagogisches Angebot', (string)$result->getBody());
        self::assertStringContainsString('kein weiterer Service', (string)$result->getBody());

        self::assertStringContainsString('Heimatschutzarchitektur', (string)$result->getBody());
        self::assertStringContainsString('Art Déco', (string)$result->getBody());
        self::assertStringContainsString('Jugendstil', (string)$result->getBody());
        self::assertStringContainsString('Barock', (string)$result->getBody());
        self::assertStringContainsString('Bauhaus', (string)$result->getBody());
        self::assertStringContainsString('Brutalismus', (string)$result->getBody());
        self::assertStringContainsString('Klassizismus', (string)$result->getBody());
        self::assertStringContainsString('Konstruktivismus', (string)$result->getBody());
        self::assertStringContainsString('Kritischer Regionalismus', (string)$result->getBody());
        self::assertStringContainsString('Dekonstruktivismus', (string)$result->getBody());
        self::assertStringContainsString('Expressionismus', (string)$result->getBody());
        self::assertStringContainsString('Funktionalismus', (string)$result->getBody());
        self::assertStringContainsString('Gotik', (string)$result->getBody());
        self::assertStringContainsString('Neogotik', (string)$result->getBody());
        self::assertStringContainsString('High-Tech-Architektur', (string)$result->getBody());
        self::assertStringContainsString('Historismus', (string)$result->getBody());
        self::assertStringContainsString('Internationaler Stil', (string)$result->getBody());
        self::assertStringContainsString('Minimalismus', (string)$result->getBody());
        self::assertStringContainsString('Moderne', (string)$result->getBody());
        self::assertStringContainsString('Neoklassizismus', (string)$result->getBody());
        self::assertStringContainsString('Neorenaissance', (string)$result->getBody());
        self::assertStringContainsString('Neues Bauen', (string)$result->getBody());
        self::assertStringContainsString('Neue Sachlichkeit', (string)$result->getBody());
        self::assertStringContainsString('Organische Architektur', (string)$result->getBody());
        self::assertStringContainsString('Postmoderne', (string)$result->getBody());
        self::assertStringContainsString('Rationalismus', (string)$result->getBody());
        self::assertStringContainsString('Renaissance', (string)$result->getBody());
        self::assertStringContainsString('Rokoko', (string)$result->getBody());
        self::assertStringContainsString('Romanik', (string)$result->getBody());
        self::assertStringContainsString('keine Angabe', (string)$result->getBody());

        self::assertStringContainsString('Fahrradboxen', (string)$result->getBody());
        self::assertStringContainsString('Fahrradständer / -boxen', (string)$result->getBody());
        self::assertStringContainsString('Fahrradständer', (string)$result->getBody());
        self::assertStringContainsString('Bushaltepunkt (für Ein- und Ausstieg) vorhanden', (string)$result->getBody());
        self::assertStringContainsString('E-Bike-Ladestation', (string)$result->getBody());
        self::assertStringContainsString('E-Auto-Ladestation', (string)$result->getBody());
        self::assertStringContainsString('keine Angabe', (string)$result->getBody());

        self::assertStringContainsString('AliPay', (string)$result->getBody());
        self::assertStringContainsString('American Express', (string)$result->getBody());
        self::assertStringContainsString('ApplePay', (string)$result->getBody());
        self::assertStringContainsString('Barzahlung', (string)$result->getBody());
        self::assertStringContainsString('EC', (string)$result->getBody());
        self::assertStringContainsString('Sofortüberweisung', (string)$result->getBody());
        self::assertStringContainsString('Rechnung', (string)$result->getBody());
        self::assertStringContainsString('MasterCard', (string)$result->getBody());
        self::assertStringContainsString('PayPal', (string)$result->getBody());
        self::assertStringContainsString('Visa', (string)$result->getBody());

        self::assertStringContainsString('App für mobile Endgeräte', (string)$result->getBody());
        self::assertStringContainsString('Audioguide', (string)$result->getBody());
        self::assertStringContainsString('Augmented Reality', (string)$result->getBody());
        self::assertStringContainsString('Videoguide', (string)$result->getBody());
        self::assertStringContainsString('Virtual Reality', (string)$result->getBody());
        self::assertStringContainsString('kein digitales Angebot', (string)$result->getBody());

        self::assertStringContainsString('Fotolizenz kostenpflichtig', (string)$result->getBody());
        self::assertStringContainsString('Fotografieren erlaubt', (string)$result->getBody());
        self::assertStringContainsString('Fotografieren nicht gestattet', (string)$result->getBody());
        self::assertStringContainsString('some free text value for photography', (string)$result->getBody());

        self::assertStringContainsString('250 Meter', (string)$result->getBody());

        self::assertStringNotContainsString('Keine tiere erlaubt', (string)$result->getBody());
        self::assertStringNotContainsString('Tiere erlaubt', (string)$result->getBody());

        self::assertStringNotContainsString('kein freier Eintritt', (string)$result->getBody());
        self::assertStringNotContainsString('freier Eintritt', (string)$result->getBody());

        self::assertStringNotContainsString('nicht öffentlich zugänglich', (string)$result->getBody());
        self::assertStringNotContainsString('öffentlich zugänglich', (string)$result->getBody());

        self::assertStringNotContainsString('Englisch', (string)$result->getBody());
        self::assertStringNotContainsString('Französisch', (string)$result->getBody());

        self::assertStringContainsString('Parkhäuser in der Nähe', (string)$result->getBody());
        self::assertStringContainsString('Parkhaus Domplatz', (string)$result->getBody());
        self::assertStringContainsString('Bechtheimer Str. 1', (string)$result->getBody());
        self::assertStringContainsString('99084 Erfurt', (string)$result->getBody());
        self::assertStringContainsString('info@stadtwerke-erfurt.de', (string)$result->getBody());
        self::assertStringContainsString('+49 361 5640', (string)$result->getBody());
        self::assertStringContainsString('Q-Park Anger 1 Parkhaus', (string)$result->getBody());
        self::assertStringContainsString('Anger 1', (string)$result->getBody());
        self::assertStringContainsString('99084 Erfurt', (string)$result->getBody());
        self::assertStringContainsString('servicecenter@q-park.de', (string)$result->getBody());
        self::assertStringContainsString('+49 218 18190290', (string)$result->getBody());

        self::assertStringContainsString('barrierefrei', (string)$result->getBody());
        self::assertStringContainsString('barrierefrei für taube Menschen', (string)$result->getBody());
        self::assertStringContainsString('nicht zertifiziert für Menschen mit kognitiven Beeinträchtigungen', (string)$result->getBody());
        self::assertStringContainsString('nicht zertifiziert für Menschen mit Hörbehinderung', (string)$result->getBody());
        self::assertStringContainsString('teilweise barrierefrei für Menschen mit Sehbehinderung', (string)$result->getBody());
        self::assertStringContainsString('nicht zertifiziert für blinde Menschen', (string)$result->getBody());
        self::assertStringContainsString('teilweise barrierefrei für Menschen mit Gehbehinderung', (string)$result->getBody());
        self::assertStringContainsString('teilweise barrierefrei für Rollstuhlfahrer', (string)$result->getBody());

        self::assertStringContainsString('Kurzbeschreibung Alle Generationen', (string)$result->getBody());
        self::assertStringContainsString('Deutsche Beschreibung von shortDescriptionAccessibilityAllGenerations', (string)$result->getBody());
        self::assertStringContainsString('Kurzbeschreibung Allergiker', (string)$result->getBody());
        self::assertStringContainsString('Deutsche Beschreibung von shortDescriptionAccessibilityAllergic', (string)$result->getBody());
        self::assertStringContainsString('Kurzbeschreibung Hörbehinderte / Gehörlos', (string)$result->getBody());
        self::assertStringContainsString('Deutsche Beschreibung von shortDescriptionAccessibilityDeaf', (string)$result->getBody());
        self::assertStringContainsString('Kurzbeschreibung Kognitive Beeinträchtigungen', (string)$result->getBody());
        self::assertStringContainsString('Deutsche Beschreibung von shortDescriptionAccessibilityMental', (string)$result->getBody());
        self::assertStringContainsString('Kurzbeschreibung Sehbehinderung / Blinde', (string)$result->getBody());
        self::assertStringContainsString('Deutsche Beschreibung von shortDescriptionAccessibilityVisual', (string)$result->getBody());
        self::assertStringContainsString('Kurzbeschreibung Gehbehindert/Rollstuhl', (string)$result->getBody());
        self::assertStringContainsString('Deutsche Beschreibung von shortDescriptionAccessibilityWalking', (string)$result->getBody());

        self::assertStringContainsString('Induktive Höranlage/ -schleife', (string)$result->getBody());
        self::assertStringContainsString('Blinksignal bei Anklopfen an die Zimmertür', (string)$result->getBody());
        self::assertStringContainsString('Spezielle Angebote für gehörlose Menschen', (string)$result->getBody());
        self::assertStringContainsString('Spezielle Angbote für Menschen mit Hörbehinderung', (string)$result->getBody());
        self::assertStringContainsString('Optische Bestätigung des Notrufs im Aufzug', (string)$result->getBody());
        self::assertStringContainsString('Farbliches oder bildhaftes Leitsystem', (string)$result->getBody());
        self::assertStringContainsString('Informationen in leichter Sprache (Führung, Begleitheft o.ä.)', (string)$result->getBody());
        self::assertStringContainsString('Informationen mit Piktogrammen oder Bildern', (string)$result->getBody());
        self::assertStringContainsString('Assistenzhunde willkommen', (string)$result->getBody());
        self::assertStringContainsString('Durchgehendes Leitsystem mit Bodenindikatoren', (string)$result->getBody());
        self::assertStringContainsString('Informationen in Braille- oder Prismenschrift', (string)$result->getBody());
        self::assertStringContainsString('Angebote in bildhafter Sprache (Führung, Audioguide o.ä.)', (string)$result->getBody());
        self::assertStringContainsString('Spezielle Angebote für blinde Menschen', (string)$result->getBody());
        self::assertStringContainsString('Spezielle Angbote für Menschen mit Sehbehinderung', (string)$result->getBody());
        self::assertStringContainsString('Taktile Angebote (Tastmodell, Lageplan o.ä.)', (string)$result->getBody());
        self::assertStringContainsString('Visuell kontrastierende Stufenkanten', (string)$result->getBody());
        self::assertStringContainsString('Alle nutzbaren Räume und Einrichtungen stufenlos bzw. über Aufzug erreichbar', (string)$result->getBody());
        self::assertStringContainsString('80 cm Mindestbreite aller Durchgänge / Türen', (string)$result->getBody());
        self::assertStringContainsString('Einstiegshilfe Schwimmbecken', (string)$result->getBody());
        self::assertStringContainsString('Haltegriff in der Dusche', (string)$result->getBody());
        self::assertStringContainsString('Beidseitige Handläufe an allen Treppen', (string)$result->getBody());
        self::assertStringContainsString('Klappbarer Haltegriff am WC', (string)$result->getBody());
        self::assertStringContainsString('WC seitlich anfahrbar', (string)$result->getBody());
        self::assertStringContainsString('Bewegungsfläche der Dusche min. 1m x 1m', (string)$result->getBody());
        self::assertStringContainsString('90 cm Mindestbreite aller Durchgänge / Türen', (string)$result->getBody());
        self::assertStringContainsString('Pflegebett', (string)$result->getBody());
        self::assertStringContainsString('Parkplatz für Menschen mit Behinderung', (string)$result->getBody());
        self::assertStringContainsString('70 cm Mindestbreite aller Durchgänge / Türen', (string)$result->getBody());
        self::assertStringContainsString('Duschstuhl oder sitz', (string)$result->getBody());
        self::assertStringContainsString('Spezielle Angbote für Menschen mit Gehbehinderung', (string)$result->getBody());
        self::assertStringContainsString('Spezielle Angebote für Rollstuhlfahrer', (string)$result->getBody());
        self::assertStringContainsString('Stufenloser Zugang zum Gebäude/ Objekt/ Gelände', (string)$result->getBody());
        self::assertStringContainsString('Stufenlose Dusche', (string)$result->getBody());
        self::assertStringContainsString('WC für Menschen mit Behinderung', (string)$result->getBody());
    }

    #[Test]
    public function touristAttractionContentElementRespectsEditorialSorting(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Frontend/TouristAttractions.php');
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Frontend/SecondTouristAttraction.php');

        $this->getConnectionPool()->getConnectionForTable('tt_content')->update(
            'tt_content',
            [
                'records' => '2,1',
            ],
            ['uid' => 2]
        );

        $request = new InternalRequest();
        $request = $request->withPageId(2);

        $html = (string)$this->executeFrontendSubRequest($request)->getBody();

        self::assertGreaterThan(
            stripos($html, 'Eine weitere Attraktion'),
            stripos($html, 'Erste Attraktion')
        );
    }

    #[Test]
    public function touristAttractionWithPetsFalse(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Frontend/TouristAttractionsForPets.php');

        $request = new InternalRequest();
        $request = $request->withPageId(4);

        $result = $this->executeFrontendSubRequest($request);

        self::assertSame(200, $result->getStatusCode());

        self::assertStringContainsString('Keine Tiere erlaubt', (string)$result->getBody());
    }

    #[Test]
    public function touristAttractionWithPetsTrue(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Frontend/TouristAttractionsForPets.php');

        $request = new InternalRequest();
        $request = $request->withPageId(5);

        $result = $this->executeFrontendSubRequest($request);

        self::assertSame(200, $result->getStatusCode());

        self::assertStringContainsString('Tiere erlaubt', (string)$result->getBody());
    }

    #[Test]
    public function touristAttractionWithIsAccessibleForFreeFalse(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Frontend/TouristAttractionsForIsAccessibleForFree.php');

        $request = new InternalRequest();
        $request = $request->withPageId(4);

        $result = $this->executeFrontendSubRequest($request);

        self::assertSame(200, $result->getStatusCode());

        self::assertStringContainsString('kein freier Eintritt', (string)$result->getBody());
    }

    #[Test]
    public function touristAttractionWithIsAccessibleForFreeTrue(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Frontend/TouristAttractionsForIsAccessibleForFree.php');

        $request = new InternalRequest();
        $request = $request->withPageId(5);

        $result = $this->executeFrontendSubRequest($request);

        self::assertSame(200, $result->getStatusCode());

        self::assertStringContainsString('freier Eintritt', (string)$result->getBody());
    }

    #[Test]
    public function touristAttractionWithPublicAccessFalse(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Frontend/TouristAttractionsForPublicAccess.php');

        $request = new InternalRequest();
        $request = $request->withPageId(4);

        $result = $this->executeFrontendSubRequest($request);

        self::assertSame(200, $result->getStatusCode());

        self::assertStringContainsString('nicht öffentlich zugänglich', (string)$result->getBody());
    }

    #[Test]
    public function touristAttractionWithPublicAccessTrue(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Frontend/TouristAttractionsForPublicAccess.php');

        $request = new InternalRequest();
        $request = $request->withPageId(5);

        $result = $this->executeFrontendSubRequest($request);

        self::assertSame(200, $result->getStatusCode());

        self::assertStringContainsString('öffentlich zugänglich', (string)$result->getBody());
    }

    #[Test]
    public function pricesAreSorted(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Frontend/TouristAttractionWithPrices.php');

        $request = new InternalRequest();
        $request = $request->withPageId(2);

        $result = $this->executeFrontendSubRequest($request);

        self::assertSame(200, $result->getStatusCode());

        self::assertStringContainsString('Attraktion mit Preisen', (string)$result->getBody());

        self::assertGreaterThan(
            mb_strpos((string)$result->getBody(), 'Erwachsene'),
            mb_strpos((string)$result->getBody(), 'Familienkarte A'),
            '"Erwachsene" is not rendered before "Familienkarte A"'
        );
        self::assertGreaterThan(
            mb_strpos((string)$result->getBody(), 'Familienkarte A'),
            mb_strpos((string)$result->getBody(), 'Familienkarte B'),
            '"Familienkarte A" is not rendered before "Familienkarte B"'
        );
        self::assertGreaterThan(
            mb_strpos((string)$result->getBody(), 'Familienkarte B'),
            mb_strpos((string)$result->getBody(), 'Schulklassen'),
            '"Familienkarte B" is not rendered before "Schulklassen"'
        );
    }

    #[Test]
    public function offersAreSortedByType(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Frontend/TouristAttractionWithOfferTypes.php');

        $request = new InternalRequest();
        $request = $request->withPageId(2);

        $result = $this->executeFrontendSubRequest($request);

        self::assertSame(200, $result->getStatusCode());

        self::assertStringContainsString('Attraktion mit Angebotstypen', (string)$result->getBody());

        self::assertGreaterThan(
            mb_strpos((string)$result->getBody(), 'Eintritt 1'),
            mb_strpos((string)$result->getBody(), 'Eintritt 2'),
            '"Eintritt 1" is not rendered before "Eintritt 2"'
        );
        self::assertGreaterThan(
            mb_strpos((string)$result->getBody(), 'Eintritt 2'),
            mb_strpos((string)$result->getBody(), 'Führungen'),
            '"Eintritt 2" is not rendered before "Führungen"'
        );
        self::assertGreaterThan(
            mb_strpos((string)$result->getBody(), 'Führungen'),
            mb_strpos((string)$result->getBody(), 'Parkgebühr'),
            '"Führungen" is not rendered before "Parkgebühr"'
        );
        self::assertGreaterThan(
            mb_strpos((string)$result->getBody(), 'Parkgebühr'),
            mb_strpos((string)$result->getBody(), 'Verkostung'),
            '"Parkgebühr" is not rendered before "Verkostung"'
        );
    }

    #[Test]
    public function openingHoursAreFilteredByThough(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Frontend/TouristAttractionsOpeningHours.php');

        $hidden = new DateTimeImmutable('yesterday');
        $available = new DateTimeImmutable('tomorrow');

        $this->getConnectionPool()
            ->getConnectionForTable('tx_thuecat_tourist_attraction')
            ->update(
                'tx_thuecat_tourist_attraction',
                ['opening_hours' => json_encode([
                    [
                        'closes' => '14:00:00',
                        'opens' => '13:00:00',
                        'daysOfWeek' => ['Sunday'],
                        'from' => [
                            'date' => $hidden->modify('-1 day')->format('Y-m-d') . ' 00:00:00.000000',
                            'timezone' => 'UTC',
                            'timezone_type' => 3,
                        ],
                        'through' => [
                            'date' => $hidden->format('Y-m-d') . ' 00:00:00.000000',
                            'timezone' => 'UTC',
                            'timezone_type' => 3,
                        ],
                    ],
                    [
                        'closes' => '16:00:00',
                        'opens' => '15:00:00',
                        'daysOfWeek' => ['Sunday'],
                        'from' => [
                            'date' => $available->modify('-1 day')->format('Y-m-d') . ' 00:00:00.000000',
                            'timezone' => 'UTC',
                            'timezone_type' => 3,
                        ],
                        'through' => [
                            'date' => $available->format('Y-m-d') . ' 00:00:00.000000',
                            'timezone' => 'UTC',
                            'timezone_type' => 3,
                        ],
                    ],
                ])],
                ['uid' => 1]
            )
        ;

        $request = new InternalRequest();
        $request = $request->withPageId(2);

        $result = (string)$this->executeFrontendSubRequest($request)->getBody();

        self::assertStringNotContainsString('14:00', $result);
        self::assertStringNotContainsString('13:00', $result);
        self::assertStringNotContainsString('16:00:00', $result);
        self::assertStringContainsString('16:00', $result);
        self::assertStringNotContainsString('15:00:00', $result);
        self::assertStringContainsString('15:00', $result);

        self::assertStringNotContainsString($hidden->modify('-1 day')->format('d.m.Y'), $result);
        self::assertStringNotContainsString($hidden->format('d.m.Y'), $result);
        self::assertStringContainsString($available->modify('-1 day')->format('d.m.Y'), $result);
        self::assertStringContainsString($available->format('d.m.Y'), $result);
    }

    #[Test]
    public function openingHoursAreSortedByFrom(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Frontend/TouristAttractionsOpeningHours.php');

        $fromFirstOpening = new DateTimeImmutable('today');
        $fromSecondOpening = new DateTimeImmutable('+2 days');
        $fromThirdOpening = new DateTimeImmutable('+7 days');

        $this->getConnectionPool()
            ->getConnectionForTable('tx_thuecat_tourist_attraction')
            ->update(
                'tx_thuecat_tourist_attraction',
                ['opening_hours' => json_encode([
                    [
                        'closes' => '17:00:00',
                        'opens' => '13:00:00',
                        'daysOfWeek' => ['Sunday'],
                        'from' => [
                            'date' => $fromThirdOpening->format('Y-m-d') . ' 00:00:00.000000',
                            'timezone' => 'UTC',
                            'timezone_type' => 3,
                        ],
                        'through' => [
                            'date' => $fromThirdOpening->modify('+1 day')->format('Y-m-d') . ' 00:00:00.000000',
                            'timezone' => 'UTC',
                            'timezone_type' => 3,
                        ],
                    ],
                    [
                        'closes' => '17:00:00',
                        'opens' => '13:00:00',
                        'daysOfWeek' => ['Sunday'],
                        'from' => [
                            'date' => $fromFirstOpening->format('Y-m-d') . ' 00:00:00.000000',
                            'timezone' => 'UTC',
                            'timezone_type' => 3,
                        ],
                        'through' => [
                            'date' => $fromFirstOpening->modify('+1 day')->format('Y-m-d') . ' 00:00:00.000000',
                            'timezone' => 'UTC',
                            'timezone_type' => 3,
                        ],
                    ],
                    [
                        'closes' => '17:00:00',
                        'opens' => '13:00:00',
                        'daysOfWeek' => ['Sunday'],
                        'from' => [
                            'date' => $fromSecondOpening->format('Y-m-d') . ' 00:00:00.000000',
                            'timezone' => 'UTC',
                            'timezone_type' => 3,
                        ],
                        'through' => [
                            'date' => $fromSecondOpening->modify('+1 day')->format('Y-m-d') . ' 00:00:00.000000',
                            'timezone' => 'UTC',
                            'timezone_type' => 3,
                        ],
                    ],
                ])],
                ['uid' => 1]
            )
        ;

        $request = new InternalRequest();
        $request = $request->withPageId(2);

        $result = (string)$this->executeFrontendSubRequest($request)->getBody();

        $positionFirstHour = mb_strpos($result, $fromFirstOpening->format('d.m.Y'));
        $positionSecondHour = mb_strpos($result, $fromSecondOpening->format('d.m.Y'));
        $positionThirdHour = mb_strpos($result, $fromThirdOpening->format('d.m.Y'));

        self::assertLessThan($positionThirdHour, $positionSecondHour, 'Third hour does not come after second hour.');
        self::assertLessThan($positionSecondHour, $positionFirstHour, 'Second hour does not come after first hour.');
    }

    #[Test]
    public function specialOpeningHoursAreRendered(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Frontend/TouristAttractionsOpeningHours.php');

        $hidden = new DateTimeImmutable('yesterday');
        $available = new DateTimeImmutable('tomorrow');
        $available2 = new DateTimeImmutable('+3 days');

        $this->getConnectionPool()
            ->getConnectionForTable('tx_thuecat_tourist_attraction')
            ->update(
                'tx_thuecat_tourist_attraction',
                ['special_opening_hours' => json_encode([
                    [
                        'closes' => '12:00:00',
                        'opens' => '11:00:00',
                        'daysOfWeek' => ['Sunday'],
                        'from' => [
                            'date' => $hidden->modify('-1 day')->format('Y-m-d') . ' 00:00:00.000000',
                            'timezone' => 'UTC',
                            'timezone_type' => 3,
                        ],
                        'through' => [
                            'date' => $hidden->format('Y-m-d') . ' 00:00:00.000000',
                            'timezone' => 'UTC',
                            'timezone_type' => 3,
                        ],
                    ],
                    [
                        'closes' => '14:00:00',
                        'opens' => '13:00:00',
                        'daysOfWeek' => ['Sunday'],
                        'from' => [
                            'date' => $available2->modify('-1 day')->format('Y-m-d') . ' 00:00:00.000000',
                            'timezone' => 'UTC',
                            'timezone_type' => 3,
                        ],
                        'through' => [
                            'date' => $available2->format('Y-m-d') . ' 00:00:00.000000',
                            'timezone' => 'UTC',
                            'timezone_type' => 3,
                        ],
                    ],
                    [
                        'closes' => '16:00:00',
                        'opens' => '15:00:00',
                        'daysOfWeek' => ['Sunday'],
                        'from' => [
                            'date' => $available->modify('-1 day')->format('Y-m-d') . ' 00:00:00.000000',
                            'timezone' => 'UTC',
                            'timezone_type' => 3,
                        ],
                        'through' => [
                            'date' => $available->format('Y-m-d') . ' 00:00:00.000000',
                            'timezone' => 'UTC',
                            'timezone_type' => 3,
                        ],
                    ],
                ])],
                ['uid' => 1]
            )
        ;

        $request = new InternalRequest();
        $request = $request->withPageId(2);

        $result = (string)$this->executeFrontendSubRequest($request)->getBody();

        self::assertStringNotContainsString('11:00', $result);
        self::assertStringNotContainsString('12:00', $result);
        self::assertStringNotContainsString('14:00:00', $result);
        self::assertStringContainsString('14:00', $result);
        self::assertStringNotContainsString('13:00:00', $result);
        self::assertStringContainsString('13:00', $result);
        self::assertStringNotContainsString('16:00:00', $result);
        self::assertStringContainsString('16:00', $result);
        self::assertStringNotContainsString('15:00:00', $result);
        self::assertStringContainsString('15:00', $result);

        self::assertStringNotContainsString($hidden->modify('-1 day')->format('d.m.Y'), $result, 'Filtered date is shown');
        self::assertStringNotContainsString($hidden->format('d.m.Y'), $result, 'Filtered date is shown');
        self::assertStringContainsString($available->modify('-1 day')->format('d.m.Y'), $result, 'First special opening hour is missing');
        self::assertStringContainsString($available->format('d.m.Y'), $result, 'First special opening hour is missing');
        self::assertStringContainsString($available2->modify('-1 day')->format('d.m.Y'), $result, 'Second special opening hour is missing');
        self::assertStringContainsString($available2->format('d.m.Y'), $result, 'Second special opening hour is missing');

        $positionFirstHour = mb_strpos($result, $available->format('d.m.Y'));
        $positionSecondHour = mb_strpos($result, $available2->format('d.m.Y'));

        self::assertLessThan($positionSecondHour, $positionFirstHour, 'Second hour does not come after first hour.');
    }

    #[Test]
    public function editorialImagesOfTouristAttractionAreRenderedForDefaultLanguage(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Frontend/TouristAttractionWithEditorialImages.php');

        $request = new InternalRequest();
        $request = $request->withPageId(2);

        $html = (string)$this->executeFrontendSubRequest($request)->getBody();

        self::assertStringContainsString(
            '<img src="/fileadmin/tourismus/images/inhalte/sehenswertes/parks_gaerten/hirschgarten/2998_Spielplaetze_Hirschgarten.jpg" width="" height="" alt="" />',
            $html
        );
        self::assertStringContainsString(
            '<img src="/fileadmin/tourismus/images/inhalte/sehenswertes/sehenswuerdigkeiten/Petersberg/20_Erfurt-Schriftzug_Petersberg_2021__c_Stadtverwaltung_Erfurt_CC-BY-NC-SA.JPG" width="" height="" alt="" />',
            $html,
        );
    }
}
