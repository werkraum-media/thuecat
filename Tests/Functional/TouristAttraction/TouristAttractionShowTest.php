<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS extension "thuecat".
 *
 * Copyright (C) werkraum-media <https://werkraum-media.de/>
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * For the full license text, see the LICENSE file distributed with this
 * extension.
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 */

namespace WerkraumMedia\ThueCat\Tests\Functional\TouristAttraction;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractFrontendTestCase;

final class TouristAttractionShowTest extends AbstractFrontendTestCase
{
    private int $originalExecTime = 0;

    // Inside the fixture's current opening hours span and before its event. The frontend application
    // builds its date aspect from EXEC_TIME on every request, so a date aspect set here would not survive.
    protected function setUp(): void
    {
        parent::setUp();

        $this->originalExecTime = is_int($GLOBALS['EXEC_TIME'] ?? null) ? $GLOBALS['EXEC_TIME'] : time();
        $GLOBALS['EXEC_TIME'] = new DateTimeImmutable('2026-10-15T12:00:00', new DateTimeZone('Europe/Berlin'))->getTimestamp();
    }

    protected function tearDown(): void
    {
        $GLOBALS['EXEC_TIME'] = $this->originalExecTime;

        parent::tearDown();
    }

    protected function getDataSetFileName(): string
    {
        return 'TouristAttractionsForShow.php';
    }

    #[Test]
    public function showsContainingOrganisationsAndPlaces(): void
    {
        $request = $this->attractionShowRequest('21');

        $organisations = $this->renderedSection($request, 'relation', 'containedInOrganisation');
        $this->assertStringContainsString('Erfurt Tourismus GmbH', $organisations);

        $places = $this->renderedSection($request, 'relation', 'containedInPlace');
        $this->assertStringContainsString('Domplatz Erfurt', $places);
        $this->assertStringContainsString('Fischmarkt Erfurt', $places);
    }

    #[Test]
    public function showsTheManagingOrganisation(): void
    {
        $managedBy = $this->renderedSection($this->attractionShowRequest('21'), 'relation', 'managedBy');

        $this->assertStringContainsString('Erfurt Tourismus GmbH', $managedBy);
    }

    #[Test]
    public function translatedAttractionShowsTheTranslatedManagingOrganisation(): void
    {
        $request = $this->attractionShowRequest('21', 10, 1);

        $managedBy = $this->renderedSection($request, 'relation', 'managedBy');

        $this->assertStringContainsString('Erfurt Tourism Ltd.', $managedBy);
    }

    #[Test]
    public function showsNoRelationSectionsWhenTheAttractionIsContainedInNothing(): void
    {
        // Attraction 24 carries neither relation: an empty set renders no
        // container at all, rather than an empty one.
        $body = $this->executeFrontendSubRequestAndReturnBody(
            $this->attractionShowRequest('24')
        );

        self::assertSectionNotRendered($body, 'relation', 'containedInOrganisation');
        self::assertSectionNotRendered($body, 'relation', 'containedInPlace');
    }

    #[Test]
    public function showsTheEventsHostedAtTheAttraction(): void
    {
        $request = $this->attractionShowRequest('21');

        $events = $this->renderedSection($request, 'relation', 'hostsEvents');

        $this->assertStringContainsString('Lange Nacht der Museen', $events);
        $this->assertStringContainsString('29.11.2026', $events);
    }

    #[Test]
    public function translatedAttractionShowsTheEventsOfItsDefaultLanguageRecord(): void
    {
        $request = $this->attractionShowRequest('21', 10, 1);

        $events = $this->renderedSection($request, 'relation', 'hostsEvents');

        $this->assertStringContainsString('Long Night of Museums', $events);
    }

    #[Test]
    public function showsNoEventSectionWhenTheAttractionHostsNone(): void
    {
        $body = $this->executeFrontendSubRequestAndReturnBody(
            $this->attractionShowRequest('24')
        );

        self::assertSectionNotRendered($body, 'relation', 'hostsEvents');
    }

    #[Test]
    public function showsTheVisitInformation(): void
    {
        $visitInfo = $this->renderedSection($this->attractionShowRequest('27'), 'attraction', 'visitInfo');

        $this->assertStringContainsString('Geschichte erleben', $visitInfo);
        $this->assertStringContainsString('1293', $visitInfo);
        $this->assertStringContainsString('Toilette', $visitInfo);
        $this->assertStringContainsString('kostenfreies WLAN', $visitInfo);
        $this->assertStringContainsString('Museumsshop', $visitInfo);
        $this->assertStringContainsString('Gotik', $visitInfo);
        $this->assertStringContainsString('Fahrradständer', $visitInfo);
        $this->assertStringContainsString('Barzahlung', $visitInfo);
        $this->assertStringContainsString('Audioguide', $visitInfo);
        $this->assertStringContainsString('Fotografieren erlaubt', $visitInfo);
        $this->assertStringContainsString('Tiere erlaubt', $visitInfo);
        $this->assertStringContainsString('kein freier Eintritt', $visitInfo);
        $this->assertStringContainsString('öffentlich zugänglich', $visitInfo);
        $this->assertStringContainsString('Englisch', $visitInfo);
    }

    #[Test]
    public function showsTheWebsiteAndTheDistanceToPublicTransport(): void
    {
        $location = $this->renderedSection($this->attractionShowRequest('27'), 'attraction', 'location');

        $this->assertStringContainsString('https://example.com/vollstaendiges-museum', $location);
        $this->assertStringContainsString('200', $location);
        $this->assertStringContainsString('CityBus', $location);
    }

    #[Test]
    public function showsTheOffersWithTheirPrices(): void
    {
        $offers = $this->renderedSection($this->attractionShowRequest('27'), 'attraction', 'offers');

        $this->assertStringContainsString('Öffentliche Führung', $offers);
        $this->assertStringContainsString('Jeden Sonntag', $offers);
        $this->assertStringContainsString('Erwachsene', $offers);
        $this->assertStringContainsString('8,00', $offers);
    }

    #[Test]
    public function showsTheAccessibilitySpecification(): void
    {
        $accessibility = $this->renderedSection($this->attractionShowRequest('27'), 'attraction', 'accessibility');

        $this->assertStringContainsString('barrierefrei', $accessibility);
        $this->assertStringContainsString('Stufenloser Zugang', $accessibility);
    }

    #[Test]
    public function showsTheParkingFacilitiesNearByInFull(): void
    {
        $parking = $this->renderedSection($this->attractionShowRequest('27'), 'relation', 'parkingFacilityNearBy');

        $this->assertStringContainsString('Parkhaus Domplatz', $parking);
        $this->assertStringContainsString('Parkhaus mit direktem Zugang zum Domplatz', $parking);
        $this->assertMatchesRegularExpression('#Pkw</dt>\s*<dd>400</dd>#', $parking);
        $this->assertMatchesRegularExpression('#Menschen mit Behinderung</dt>\s*<dd>8</dd>#', $parking);
        $this->assertMatchesRegularExpression('#Busse</dt>\s*<dd>4</dd>#', $parking);
        $this->assertMatchesRegularExpression('#Elektrofahrzeuge</dt>\s*<dd>12</dd>#', $parking);
        $this->assertMatchesRegularExpression('#Motorräder</dt>\s*<dd>10</dd>#', $parking);
        $this->assertStringContainsString('Montag', $parking);
        $this->assertStringContainsString('07:00', $parking);
        $this->assertStringContainsString('22:00', $parking);
        $this->assertStringContainsString('Domplatz 1', $parking);
        $this->assertStringContainsString('99084', $parking);
        $this->assertStringContainsString('Toilette', $parking);
        $this->assertStringContainsString('kostenfreies WLAN', $parking);
        $this->assertStringContainsString('Fahrradständer', $parking);
        $this->assertStringContainsString('Barzahlung', $parking);
        $this->assertStringContainsString('150', $parking);
        $this->assertStringContainsString('Tram', $parking);
        $this->assertStringContainsString('Tagesticket', $parking);
        $this->assertStringContainsString('12,00', $parking);
    }

    #[Test]
    public function listsTheRelationsOfAParkingFacilityNearByByName(): void
    {
        $parking = $this->renderedSection($this->attractionShowRequest('27'), 'relation', 'parkingFacilityNearBy');

        $this->assertMatchesRegularExpression('#<dd class="town">Erfurt</dd>#', $parking);
        $this->assertMatchesRegularExpression('#<dd class="organisation">Erfurt Tourismus GmbH</dd>#', $parking);
        $this->assertMatchesRegularExpression('#<dd class="place">Fischmarkt Erfurt</dd>#', $parking);
    }

    #[Test]
    public function showsTheInlineParkingFacilitiesOfAVenue(): void
    {
        $parking = $this->renderedSection($this->attractionShowRequest('25'), 'venue', 'parkingFacilities');

        $this->assertStringContainsString('Tiefgarage des Hotels', $parking);
        $this->assertStringContainsString('Zufahrt über den Hof', $parking);
        $this->assertMatchesRegularExpression('#Pkw</dt>\s*<dd>40</dd>#', $parking);
        $this->assertMatchesRegularExpression('#Menschen mit Behinderung</dt>\s*<dd>2</dd>#', $parking);
        $this->assertStringContainsString('Dienstag', $parking);
        $this->assertStringContainsString('06:00', $parking);
    }

    #[Test]
    public function showsTheContainingTrails(): void
    {
        $trails = $this->renderedSection($this->attractionShowRequest('27'), 'relation', 'containedInTrail');

        $this->assertStringContainsString('Lutherweg', $trails);
    }

    #[Test]
    public function showsTheCategoriesAndKeywords(): void
    {
        $request = $this->attractionShowRequest('27');

        $this->assertStringContainsString('Museum', $this->renderedSection($request, 'relation', 'categories'));
        $this->assertStringContainsString('romantisch', $this->renderedSection($request, 'relation', 'keywords'));
    }

    #[Test]
    public function showsTheManagingOrganisationInFull(): void
    {
        $organisation = $this->renderedSection($this->attractionShowRequest('27'), 'relation', 'managedBy');

        $this->assertStringContainsString('Erfurt Tourismus GmbH', $organisation);
        $this->assertStringContainsString('Die Tourismusgesellschaft der Stadt', $organisation);
        $this->assertStringContainsString('Benediktsplatz 1', $organisation);
        $this->assertMatchesRegularExpression('#<dd class="event">Lange Nacht der Museen</dd>#', $organisation);
    }

    #[Test]
    public function showsTheContainingOrganisationsInFull(): void
    {
        $organisations = $this->renderedSection($this->attractionShowRequest('27'), 'relation', 'containedInOrganisation');

        $this->assertStringContainsString('Die Tourismusgesellschaft der Stadt', $organisations);
        $this->assertStringContainsString('Benediktsplatz 1', $organisations);
    }

    #[Test]
    public function showsTheTownsInFull(): void
    {
        $towns = $this->renderedSection($this->attractionShowRequest('27'), 'relation', 'towns');

        $this->assertStringContainsString('Erfurt', $towns);
        $this->assertStringContainsString('Landeshauptstadt Thüringens', $towns);
        $this->assertStringContainsString('Fischmarkt 1', $towns);
        $this->assertMatchesRegularExpression('#<dd class="organisation">Erfurt Tourismus GmbH</dd>#', $towns);
        $this->assertMatchesRegularExpression('#<dd class="event">Lange Nacht der Museen</dd>#', $towns);
    }

    #[Test]
    public function summarisesEachContainingPlace(): void
    {
        $places = $this->renderedSection($this->attractionShowRequest('27'), 'relation', 'containedInPlace');

        $this->assertStringContainsString('Domplatz Erfurt', $places);
        $this->assertStringContainsString('Platz vor dem Dom', $places);
        $this->assertStringContainsString('Domstufen 3', $places);
        $this->assertStringContainsString('Tourist Information Erfurt', $places);
        $this->assertStringContainsString('Beratung und Tickets', $places);
        $this->assertStringContainsString('Benediktsplatz 2', $places);
        $this->assertStringContainsString('Fischmarkt Erfurt', $places);
        $this->assertStringContainsString('Historischer Marktplatz', $places);
        $this->assertStringContainsString('Fischmarkt 2', $places);
        $this->assertMatchesRegularExpression('#<dd class="event">Lange Nacht der Museen</dd>#', $places);
    }

    #[Test]
    public function showsTheFullAddressOfTheAttraction(): void
    {
        $address = $this->renderedSection($this->attractionShowRequest('27'), 'relation', 'address');

        $this->assertStringContainsString('Domstraße 5', $address);
        $this->assertStringContainsString('99084', $address);
        $this->assertStringContainsString('Erfurt', $address);
        $this->assertStringContainsString('Thüringen', $address);
        $this->assertStringContainsString('Deutschland', $address);
        $this->assertStringContainsString('museum@example.com', $address);
        $this->assertStringContainsString('+49 361 1111', $address);
        $this->assertStringContainsString('+49 361 1112', $address);
        $this->assertStringContainsString('data-lat="50.975"', $address);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function sectionsOfRelatedRecordsWithAnAddress(): iterable
    {
        yield 'parking facility near by' => ['parkingFacilityNearBy'];
        yield 'managing organisation' => ['managedBy'];
        yield 'town' => ['towns'];
        yield 'containing place' => ['containedInPlace'];
    }

    #[Test]
    #[DataProvider('sectionsOfRelatedRecordsWithAnAddress')]
    public function showsTheFullAddressOfARelatedRecord(string $section): void
    {
        $related = $this->renderedSection($this->attractionShowRequest('27'), 'relation', $section);

        $this->assertStringContainsString('Thüringen', $related);
        $this->assertStringContainsString('Deutschland', $related);
        $this->assertStringContainsString('kontakt@example.com', $related);
        $this->assertStringContainsString('+49 361 0000', $related);
        $this->assertStringContainsString('+49 361 0001', $related);
    }

    #[Test]
    public function showsNoSectionOfAnEmptyFieldGroup(): void
    {
        $body = $this->executeFrontendSubRequestAndReturnBody($this->attractionShowRequest('24'));

        self::assertSectionNotRendered($body, 'attraction');
        self::assertSectionNotRendered($body, 'relation', 'parkingFacilityNearBy');
        self::assertSectionNotRendered($body, 'relation', 'containedInTrail');
        self::assertSectionNotRendered($body, 'relation', 'categories');
        self::assertSectionNotRendered($body, 'relation', 'keywords');
    }

    #[Test]
    public function showsTheVenueFieldsOfAMeetingVenue(): void
    {
        $venue = $this->renderedSection($this->attractionShowRequest('25'), 'venue', 'meeting');

        $this->assertStringContainsString('250', $venue);
        $this->assertStringContainsString('Beamer', $venue);
        $this->assertStringContainsString('Flipchart', $venue);
        $this->assertStringContainsString('12,5 km', $venue);
    }

    #[Test]
    public function showsTheMeetingRoomTableOfAMeetingVenue(): void
    {
        $rooms = $this->renderedSection($this->attractionShowRequest('25'), 'venue', 'meetingRooms');

        $this->assertStringContainsString('180,50', $rooms);
        $this->assertStringContainsString('4,20', $rooms);
        $this->assertStringContainsString('220', $rooms);
        $this->assertStringContainsString('Tageslicht', $rooms);
        self::assertAppearInOrder($rooms, 'Saal Erfurt<', 'Saal Erfurt Teil A', 'Raum Weimar');
    }

    #[Test]
    public function showsTheDetailsOfEachMeetingRoom(): void
    {
        $details = $this->renderedSection($this->attractionShowRequest('25'), 'venue', 'meetingRoomDetails');

        $this->assertStringContainsString('Großer Saal mit Domblick', $details);
        $this->assertStringContainsString('https://example.com/saal-erfurt', $details);
        $this->assertMatchesRegularExpression('#Räume</dt>\s*<dd>2</dd>#', $details);
        $this->assertStringContainsString('Terrasse', $details);
        $this->assertStringContainsString('https://example.com/saal-buchen', $details);
        $this->assertMatchesRegularExpression('#Buchbar</dt>\s*<dd>ja</dd>#', $details);
        self::assertAppearInOrder($details, 'Saal Erfurt<', 'Saal Erfurt Teil A<', 'Raum Weimar<');
    }

    #[Test]
    public function linksEachRoomOfTheTableToItsDetails(): void
    {
        $request = $this->attractionShowRequest('25');

        $table = $this->renderedSection($request, 'venue', 'meetingRooms');
        $this->assertMatchesRegularExpression('#<a href="\#meeting-room-1">Saal Erfurt</a>#', $table);
        $this->assertMatchesRegularExpression('#<a href="\#meeting-room-3">Saal Erfurt Teil A</a>#', $table);

        $details = $this->renderedSection($request, 'venue', 'meetingRoomDetails');
        $this->assertStringContainsString('id="meeting-room-1"', $details);
        $this->assertStringContainsString('id="meeting-room-3"', $details);
    }

    #[Test]
    public function listsTheRelationsOfAMeetingRoomByName(): void
    {
        $details = $this->renderedSection($this->attractionShowRequest('25'), 'venue', 'meetingRoomDetails');

        $this->assertMatchesRegularExpression('#<dd class="organisation">Erfurt Tourismus GmbH</dd>#', $details);
        $this->assertMatchesRegularExpression('#<dd class="category">Kongressraum</dd>#', $details);
        $this->assertMatchesRegularExpression('#<dd class="keyword">barrierefrei</dd>#', $details);
        $this->assertMatchesRegularExpression('#<dd class="room">Saal Erfurt Teil A</dd>#', $details);
    }

    #[Test]
    public function showsTheVenueTypeAndTheMeetingDescription(): void
    {
        $venue = $this->renderedSection($this->attractionShowRequest('25'), 'venue', 'meeting');

        $this->assertStringContainsString('Tagungshotel', $venue);
        $this->assertStringContainsString('Tagen mit Blick auf den Dom', $venue);
    }

    #[Test]
    public function showsTheLodgingFactsOfAVenue(): void
    {
        $lodging = $this->renderedSection($this->attractionShowRequest('25'), 'venue', 'lodging');

        $this->assertStringContainsString('16:00', $lodging);
        $this->assertStringContainsString('11:00', $lodging);
        $this->assertMatchesRegularExpression('#Zimmer</dt>\s*<dd>160</dd>#', $lodging);
        $this->assertMatchesRegularExpression('#Einzelzimmer</dt>\s*<dd>5</dd>#', $lodging);
        $this->assertMatchesRegularExpression('#Doppelzimmer</dt>\s*<dd>147</dd>#', $lodging);
        $this->assertMatchesRegularExpression('#Zweibettzimmer</dt>\s*<dd>2</dd>#', $lodging);
        $this->assertMatchesRegularExpression('#Suiten</dt>\s*<dd>8</dd>#', $lodging);
        $this->assertStringContainsString('***', $lodging);
        $this->assertStringContainsString('Sauna', $lodging);
        $this->assertStringContainsString('mittel', $lodging);
    }

    #[Test]
    public function showsTheGastronomyFactsOfAVenue(): void
    {
        $gastronomy = $this->renderedSection($this->attractionShowRequest('25'), 'venue', 'gastronomy');

        $this->assertStringContainsString('Restaurant', $gastronomy);
        $this->assertStringContainsString('Regionale Küche', $gastronomy);
        $this->assertMatchesRegularExpression('#Sitzplätze innen</dt>\s*<dd>120</dd>#', $gastronomy);
        $this->assertMatchesRegularExpression('#Sitzplätze außen</dt>\s*<dd>40</dd>#', $gastronomy);
        $this->assertMatchesRegularExpression('#Biergarten</dt>\s*<dd>ja</dd>#', $gastronomy);
        $this->assertMatchesRegularExpression('#Catering</dt>\s*<dd>ja</dd>#', $gastronomy);
        $this->assertMatchesRegularExpression('#Lieferservice</dt>\s*<dd>nein</dd>#', $gastronomy);
        $this->assertMatchesRegularExpression('#Durchgehend warme Küche</dt>\s*<dd>ja</dd>#', $gastronomy);
        $this->assertMatchesRegularExpression('#Späte Küche</dt>\s*<dd>nein</dd>#', $gastronomy);
        $this->assertMatchesRegularExpression('#Selbstbedienung</dt>\s*<dd>nein</dd>#', $gastronomy);
        $this->assertMatchesRegularExpression('#Speisen zum Mitnehmen</dt>\s*<dd>nein</dd>#', $gastronomy);
        $this->assertMatchesRegularExpression('#Reservierung möglich</dt>\s*<dd>ja</dd>#', $gastronomy);
        $this->assertMatchesRegularExpression('#Drive-in</dt>\s*<dd>nein</dd>#', $gastronomy);
    }

    #[Test]
    public function showsTheVisitFactsOfAVenue(): void
    {
        $visit = $this->renderedSection($this->attractionShowRequest('25'), 'venue', 'visit');

        $this->assertStringContainsString('Angebot im Indoorbereich', $visit);
        $this->assertStringContainsString('Führung mit Anmeldung', $visit);
        $this->assertMatchesRegularExpression('#Rauchen erlaubt</dt>\s*<dd>nein</dd>#', $visit);
        $this->assertMatchesRegularExpression('#Altersempfehlung</dt>\s*<dd>12</dd>#', $visit);
        $this->assertStringContainsString('https://example.com/tickets', $visit);
        $this->assertMatchesRegularExpression('#Buchbar</dt>\s*<dd>ja</dd>#', $visit);
        $this->assertStringContainsString('Fahrradverleih', $visit);
        $this->assertStringContainsString('eigenes Orchester', $visit);
        $this->assertStringContainsString('Kulturgeschichte', $visit);
        $this->assertMatchesRegularExpression('#Freilichtmuseum</dt>\s*<dd>nein</dd>#', $visit);
        $this->assertStringContainsString('Familien', $visit);
        $this->assertStringContainsString('Senioren', $visit);
    }

    #[Test]
    public function showsTheBuildingFactsOfAVenue(): void
    {
        $building = $this->renderedSection($this->attractionShowRequest('25'), 'venue', 'building');

        $this->assertStringContainsString('UNESCO Welterbe (Kultur)', $building);
        $this->assertMatchesRegularExpression('#Ruine</dt>\s*<dd>nein</dd>#', $building);
        $this->assertStringContainsString('1762', $building);
        $this->assertStringContainsString('360.000 m²', $building);
        $this->assertStringContainsString('Heinrich Gentz', $building);
        $this->assertStringContainsString('Christoph Martin Wieland', $building);
    }

    #[Test]
    public function showsTheLinksOfAVenue(): void
    {
        $links = $this->renderedSection($this->attractionShowRequest('25'), 'venue', 'links');

        $this->assertStringContainsString('https://example.com/karte', $links);
        $this->assertStringContainsString('https://example.com/same-a', $links);
        $this->assertStringContainsString('https://example.com/same-b', $links);
        $this->assertStringContainsString('https://example.com/kennung', $links);
        $this->assertStringContainsString('https://example.com/portal', $links);
    }

    #[Test]
    public function showsTheAdditionalTextsOfAVenue(): void
    {
        $texts = $this->renderedSection($this->attractionShowRequest('25'), 'venue', 'texts');

        $this->assertStringContainsString('Motorradfreundlicher Hotel- und Gastronomiebetrieb', $texts);
        $this->assertStringContainsString('Text aus dem Kulturportal', $texts);
    }

    #[Test]
    public function showsTheRelationsOfAVenue(): void
    {
        $relations = $this->renderedSection($this->attractionShowRequest('25'), 'venue', 'relations');

        $this->assertStringContainsString('Erfurt Tourismus GmbH', $relations);
        $this->assertStringContainsString('Lutherweg', $relations);
        $this->assertStringContainsString('Domplatz Erfurt', $relations);
    }

    #[Test]
    public function showsNoVenueGroupWithoutData(): void
    {
        $body = $this->executeFrontendSubRequestAndReturnBody($this->attractionShowRequest('26'));

        foreach (['lodging', 'gastronomy', 'visit', 'building', 'links', 'texts', 'relations', 'media', 'parkingFacilities', 'meetingRoomDetails'] as $group) {
            self::assertSectionNotRendered($body, 'venue', $group);
        }
    }

    #[Test]
    public function showsNoVenueOutputForAPlainAttraction(): void
    {
        $body = $this->executeFrontendSubRequestAndReturnBody(
            $this->attractionShowRequest('21')
        );

        $this->assertStringContainsString('Stadtmuseum Erfurt', $body);
        self::assertSectionNotRendered($body, 'venue');
    }

    #[Test]
    public function showsNoMeetingRoomTableForAVenueWithoutRooms(): void
    {
        $request = $this->attractionShowRequest('26');

        $this->assertStringContainsString('40', $this->renderedSection($request, 'venue', 'meeting'));
        self::assertSectionNotRendered($this->executeFrontendSubRequestAndReturnBody($request), 'venue', 'meetingRooms');
    }

    #[Test]
    public function showsAttractionTitle(): void
    {
        $request = $this->attractionShowRequest('21');

        $result = $this->executeFrontendSubRequest($request);

        $this->assertSame(200, $result->getStatusCode());
        $this->assertStringContainsString('Stadtmuseum Erfurt', (string)$result->getBody());
    }

    #[Test]
    public function withoutAttractionParameterShowsNoDataLabel(): void
    {
        $request = new InternalRequest()->withPageId(10);

        $result = $this->executeFrontendSubRequest($request);

        $this->assertSame(200, $result->getStatusCode());
        $this->assertStringContainsString('Keine Daten vorhanden.', (string)$result->getBody());
    }

    #[Test]
    public function hiddenAttractionShowsNoDataLabel(): void
    {
        $request = $this->attractionShowRequest('20');

        $result = $this->executeFrontendSubRequest($request);

        $this->assertSame(200, $result->getStatusCode());
        $this->assertStringNotContainsString('Verstecktes Stadtmuseum', (string)$result->getBody());
        $this->assertStringContainsString('Keine Daten vorhanden.', (string)$result->getBody());
    }

    #[Test]
    public function rendersBothOpenSpansOfALunchBreakUnderOneWeekday(): void
    {
        $request = $this->attractionShowRequest('21');

        $section = $this->renderedSection($request, 'openinghours-format', 'perDayTable');

        // Monday keeps BOTH open spans either
        // side of the lunch break (08:00–12:00 and 13:00–18:00).
        $this->assertStringContainsString('Montag', $section);
        $this->assertStringContainsString('08:00', $section);
        $this->assertStringContainsString('12:00', $section);
        $this->assertStringContainsString('13:00', $section);
        $this->assertStringContainsString('18:00', $section);
    }

    #[Test]
    public function rendersSpecialPublicHolidayHours(): void
    {
        $request = $this->attractionShowRequest('21');

        $section = $this->renderedSection($request, 'openinghours-format', 'perDayTable');

        $this->assertStringContainsString('Sonderöffnungszeiten', $section);
        $this->assertStringContainsString('Feiertags', $section);
        $this->assertStringContainsString('09:00', $section);
    }

    #[Test]
    public function rendersFuturePeriodAfterCurrentOne(): void
    {
        $request = $this->attractionShowRequest('21');

        $section = $this->renderedSection($request, 'openinghours-format', 'perDayTable');

        // Future period (Sunday 2026-11-02 – 2027-03-25) renders distinctly.
        $this->assertStringContainsString('Sonntag', $section);
        $this->assertStringContainsString('02.11.2026', $section);
    }

    #[Test]
    public function mergedByWeekdayFormatCollapsesDaysSharingTheSameHours(): void
    {
        $request = $this->attractionShowRequest('21');

        $section = $this->renderedSection($request, 'openinghours-format', 'mergedByWeekday');

        // Monday and Tuesday share identical spans, so the merged format lists
        // them in one grouped row — a marker the per-day format never produces.
        $this->assertStringContainsString('Montag, Dienstag', $section);
        $this->assertStringContainsString('08:00', $section);
        $this->assertStringContainsString('13:00', $section);
    }

    #[Test]
    public function mergedByWeekdayRangesFormatCollapsesConsecutiveDaysIntoARange(): void
    {
        $request = $this->attractionShowRequest('21');

        $section = $this->renderedSection($request, 'openinghours-format', 'mergedByWeekdayRanges');

        // Monday and Tuesday are consecutive and share spans, so the ranges
        // format collapses them to "Montag–Dienstag" rather than listing both.
        $this->assertStringContainsString('Montag&ndash;Dienstag', $section);
        $this->assertStringNotContainsString('Montag, Dienstag', $section);
    }
}
