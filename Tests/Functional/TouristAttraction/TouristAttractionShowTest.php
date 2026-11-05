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
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractFrontendTestCase;

class TouristAttractionShowTest extends AbstractFrontendTestCase
{
    private int $originalExecTime = 0;

    // Inside the fixture's current opening hours span and before its event. The frontend application
    // builds its date aspect from EXEC_TIME on every request, so a date aspect set here would not survive.
    protected function setUp(): void
    {
        parent::setUp();

        $this->originalExecTime = is_int($GLOBALS['EXEC_TIME'] ?? null) ? $GLOBALS['EXEC_TIME'] : time();
        $GLOBALS['EXEC_TIME'] = (new DateTimeImmutable('2026-10-15T12:00:00', new DateTimeZone('Europe/Berlin')))->getTimestamp();
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
        $request = $this->generateRequestWithCHash('21');

        $organisations = $this->renderedSection($request, 'relation', 'containedInOrganisation');
        self::assertStringContainsString('Erfurt Tourismus GmbH', $organisations);

        $places = $this->renderedSection($request, 'relation', 'containedInPlace');
        self::assertStringContainsString('Domplatz Erfurt', $places);
        self::assertStringContainsString('Fischmarkt Erfurt', $places);
    }

    #[Test]
    public function showsTheManagingOrganisation(): void
    {
        $managedBy = $this->renderedSection($this->generateRequestWithCHash('21'), 'relation', 'managedBy');

        self::assertStringContainsString('Erfurt Tourismus GmbH', $managedBy);
    }

    #[Test]
    public function translatedAttractionShowsTheTranslatedManagingOrganisation(): void
    {
        $request = $this->detailRequest('tx_thuecat_touristattractionshow', 'attraction', '21', 10, 1);

        $managedBy = $this->renderedSection($request, 'relation', 'managedBy');

        self::assertStringContainsString('Erfurt Tourism Ltd.', $managedBy);
    }

    #[Test]
    public function showsNoRelationSectionsWhenTheAttractionIsContainedInNothing(): void
    {
        // Attraction 24 carries neither relation: an empty set renders no
        // container at all, rather than an empty one.
        $body = $this->executeFrontendSubRequestAndReturnBody(
            $this->generateRequestWithCHash('24')
        );

        self::assertStringNotContainsString('data-relation="containedInOrganisation"', $body);
        self::assertStringNotContainsString('data-relation="containedInPlace"', $body);
    }

    #[Test]
    public function showsTheEventsHostedAtTheAttraction(): void
    {
        $request = $this->generateRequestWithCHash('21');

        $events = $this->renderedSection($request, 'relation', 'hostsEvents');

        self::assertStringContainsString('Lange Nacht der Museen', $events);
        self::assertStringContainsString('29.11.2026', $events);
    }

    #[Test]
    public function translatedAttractionShowsTheEventsOfItsDefaultLanguageRecord(): void
    {
        $request = $this->detailRequest('tx_thuecat_touristattractionshow', 'attraction', '21', 10, 1);

        $events = $this->renderedSection($request, 'relation', 'hostsEvents');

        self::assertStringContainsString('Long Night of Museums', $events);
    }

    #[Test]
    public function showsNoEventSectionWhenTheAttractionHostsNone(): void
    {
        $body = $this->executeFrontendSubRequestAndReturnBody(
            $this->generateRequestWithCHash('24')
        );

        self::assertStringNotContainsString('data-relation="hostsEvents"', $body);
    }

    #[Test]
    public function showsTheVisitInformation(): void
    {
        $visitInfo = $this->renderedSection($this->generateRequestWithCHash('27'), 'attraction', 'visitInfo');

        self::assertStringContainsString('Geschichte erleben', $visitInfo);
        self::assertStringContainsString('1293', $visitInfo);
        self::assertStringContainsString('Toilette', $visitInfo);
        self::assertStringContainsString('kostenfreies WLAN', $visitInfo);
        self::assertStringContainsString('Museumsshop', $visitInfo);
        self::assertStringContainsString('Gotik', $visitInfo);
        self::assertStringContainsString('Fahrradständer', $visitInfo);
        self::assertStringContainsString('Barzahlung', $visitInfo);
        self::assertStringContainsString('Audioguide', $visitInfo);
        self::assertStringContainsString('Fotografieren erlaubt', $visitInfo);
        self::assertStringContainsString('Tiere erlaubt', $visitInfo);
        self::assertStringContainsString('kein freier Eintritt', $visitInfo);
        self::assertStringContainsString('öffentlich zugänglich', $visitInfo);
        self::assertStringContainsString('Englisch', $visitInfo);
    }

    #[Test]
    public function showsTheWebsiteAndTheDistanceToPublicTransport(): void
    {
        $location = $this->renderedSection($this->generateRequestWithCHash('27'), 'attraction', 'location');

        self::assertStringContainsString('https://example.com/vollstaendiges-museum', $location);
        self::assertStringContainsString('200', $location);
        self::assertStringContainsString('CityBus', $location);
    }

    #[Test]
    public function showsTheOffersWithTheirPrices(): void
    {
        $offers = $this->renderedSection($this->generateRequestWithCHash('27'), 'attraction', 'offers');

        self::assertStringContainsString('Öffentliche Führung', $offers);
        self::assertStringContainsString('Jeden Sonntag', $offers);
        self::assertStringContainsString('Erwachsene', $offers);
        self::assertStringContainsString('8,00', $offers);
    }

    #[Test]
    public function showsTheAccessibilitySpecification(): void
    {
        $accessibility = $this->renderedSection($this->generateRequestWithCHash('27'), 'attraction', 'accessibility');

        self::assertStringContainsString('barrierefrei', $accessibility);
        self::assertStringContainsString('Stufenloser Zugang', $accessibility);
    }

    #[Test]
    public function showsTheParkingFacilitiesNearByInFull(): void
    {
        $parking = $this->renderedSection($this->generateRequestWithCHash('27'), 'relation', 'parkingFacilityNearBy');

        self::assertStringContainsString('Parkhaus Domplatz', $parking);
        self::assertStringContainsString('Parkhaus mit direktem Zugang zum Domplatz', $parking);
        self::assertMatchesRegularExpression('#Pkw</dt>\s*<dd>400</dd>#', $parking);
        self::assertMatchesRegularExpression('#Menschen mit Behinderung</dt>\s*<dd>8</dd>#', $parking);
        self::assertMatchesRegularExpression('#Busse</dt>\s*<dd>4</dd>#', $parking);
        self::assertMatchesRegularExpression('#Elektrofahrzeuge</dt>\s*<dd>12</dd>#', $parking);
        self::assertMatchesRegularExpression('#Motorräder</dt>\s*<dd>10</dd>#', $parking);
        self::assertStringContainsString('Montag', $parking);
        self::assertStringContainsString('07:00', $parking);
        self::assertStringContainsString('22:00', $parking);
        self::assertStringContainsString('Domplatz 1', $parking);
        self::assertStringContainsString('99084', $parking);
        self::assertStringContainsString('Toilette', $parking);
        self::assertStringContainsString('kostenfreies WLAN', $parking);
        self::assertStringContainsString('Fahrradständer', $parking);
        self::assertStringContainsString('Barzahlung', $parking);
        self::assertStringContainsString('150', $parking);
        self::assertStringContainsString('Tram', $parking);
        self::assertStringContainsString('Tagesticket', $parking);
        self::assertStringContainsString('12,00', $parking);
    }

    #[Test]
    public function listsTheRelationsOfAParkingFacilityNearByByName(): void
    {
        $parking = $this->renderedSection($this->generateRequestWithCHash('27'), 'relation', 'parkingFacilityNearBy');

        self::assertMatchesRegularExpression('#<dd class="town">Erfurt</dd>#', $parking);
        self::assertMatchesRegularExpression('#<dd class="organisation">Erfurt Tourismus GmbH</dd>#', $parking);
        self::assertMatchesRegularExpression('#<dd class="place">Fischmarkt Erfurt</dd>#', $parking);
    }

    #[Test]
    public function showsTheInlineParkingFacilitiesOfAVenue(): void
    {
        $parking = $this->renderedSection($this->generateRequestWithCHash('25'), 'venue', 'parkingFacilities');

        self::assertStringContainsString('Tiefgarage des Hotels', $parking);
        self::assertStringContainsString('Zufahrt über den Hof', $parking);
        self::assertMatchesRegularExpression('#Pkw</dt>\s*<dd>40</dd>#', $parking);
        self::assertMatchesRegularExpression('#Menschen mit Behinderung</dt>\s*<dd>2</dd>#', $parking);
        self::assertStringContainsString('Dienstag', $parking);
        self::assertStringContainsString('06:00', $parking);
    }

    #[Test]
    public function showsTheContainingTrails(): void
    {
        $trails = $this->renderedSection($this->generateRequestWithCHash('27'), 'relation', 'containedInTrail');

        self::assertStringContainsString('Lutherweg', $trails);
    }

    #[Test]
    public function showsTheCategoriesAndKeywords(): void
    {
        $request = $this->generateRequestWithCHash('27');

        self::assertStringContainsString('Museum', $this->renderedSection($request, 'relation', 'categories'));
        self::assertStringContainsString('romantisch', $this->renderedSection($request, 'relation', 'keywords'));
    }

    #[Test]
    public function showsNoSectionOfAnEmptyFieldGroup(): void
    {
        $body = $this->executeFrontendSubRequestAndReturnBody($this->generateRequestWithCHash('24'));

        self::assertStringNotContainsString('data-attraction=', $body);
        self::assertStringNotContainsString('data-relation="parkingFacilityNearBy"', $body);
        self::assertStringNotContainsString('data-relation="containedInTrail"', $body);
        self::assertStringNotContainsString('data-relation="categories"', $body);
        self::assertStringNotContainsString('data-relation="keywords"', $body);
    }

    #[Test]
    public function showsTheVenueFieldsOfAMeetingVenue(): void
    {
        $venue = $this->renderedSection($this->generateRequestWithCHash('25'), 'venue', 'meeting');

        self::assertStringContainsString('250', $venue);
        self::assertStringContainsString('Beamer', $venue);
        self::assertStringContainsString('Flipchart', $venue);
        self::assertStringContainsString('12,5 km', $venue);
    }

    #[Test]
    public function showsTheMeetingRoomTableOfAMeetingVenue(): void
    {
        $rooms = $this->renderedSection($this->generateRequestWithCHash('25'), 'venue', 'meetingRooms');

        self::assertStringContainsString('180,50', $rooms);
        self::assertStringContainsString('4,20', $rooms);
        self::assertStringContainsString('220', $rooms);
        self::assertStringContainsString('Tageslicht', $rooms);
        self::assertAppearInOrder($rooms, 'Saal Erfurt<', 'Saal Erfurt Teil A', 'Raum Weimar');
    }

    #[Test]
    public function showsTheDetailsOfEachMeetingRoom(): void
    {
        $details = $this->renderedSection($this->generateRequestWithCHash('25'), 'venue', 'meetingRoomDetails');

        self::assertStringContainsString('Großer Saal mit Domblick', $details);
        self::assertStringContainsString('https://example.com/saal-erfurt', $details);
        self::assertMatchesRegularExpression('#Räume</dt>\s*<dd>2</dd>#', $details);
        self::assertStringContainsString('Terrasse', $details);
        self::assertStringContainsString('https://example.com/saal-buchen', $details);
        self::assertMatchesRegularExpression('#Buchbar</dt>\s*<dd>ja</dd>#', $details);
        self::assertAppearInOrder($details, 'Saal Erfurt<', 'Saal Erfurt Teil A<', 'Raum Weimar<');
    }

    #[Test]
    public function linksEachRoomOfTheTableToItsDetails(): void
    {
        $request = $this->generateRequestWithCHash('25');

        $table = $this->renderedSection($request, 'venue', 'meetingRooms');
        self::assertMatchesRegularExpression('#<a href="\#meeting-room-1">Saal Erfurt</a>#', $table);
        self::assertMatchesRegularExpression('#<a href="\#meeting-room-3">Saal Erfurt Teil A</a>#', $table);

        $details = $this->renderedSection($request, 'venue', 'meetingRoomDetails');
        self::assertStringContainsString('id="meeting-room-1"', $details);
        self::assertStringContainsString('id="meeting-room-3"', $details);
    }

    #[Test]
    public function listsTheRelationsOfAMeetingRoomByName(): void
    {
        $details = $this->renderedSection($this->generateRequestWithCHash('25'), 'venue', 'meetingRoomDetails');

        self::assertMatchesRegularExpression('#<dd class="organisation">Erfurt Tourismus GmbH</dd>#', $details);
        self::assertMatchesRegularExpression('#<dd class="category">Kongressraum</dd>#', $details);
        self::assertMatchesRegularExpression('#<dd class="keyword">barrierefrei</dd>#', $details);
        self::assertMatchesRegularExpression('#<dd class="room">Saal Erfurt Teil A</dd>#', $details);
    }

    #[Test]
    public function showsTheVenueTypeAndTheMeetingDescription(): void
    {
        $venue = $this->renderedSection($this->generateRequestWithCHash('25'), 'venue', 'meeting');

        self::assertStringContainsString('Tagungshotel', $venue);
        self::assertStringContainsString('Tagen mit Blick auf den Dom', $venue);
    }

    #[Test]
    public function showsTheLodgingFactsOfAVenue(): void
    {
        $lodging = $this->renderedSection($this->generateRequestWithCHash('25'), 'venue', 'lodging');

        self::assertStringContainsString('16:00', $lodging);
        self::assertStringContainsString('11:00', $lodging);
        self::assertMatchesRegularExpression('#Zimmer</dt>\s*<dd>160</dd>#', $lodging);
        self::assertMatchesRegularExpression('#Einzelzimmer</dt>\s*<dd>5</dd>#', $lodging);
        self::assertMatchesRegularExpression('#Doppelzimmer</dt>\s*<dd>147</dd>#', $lodging);
        self::assertMatchesRegularExpression('#Zweibettzimmer</dt>\s*<dd>2</dd>#', $lodging);
        self::assertMatchesRegularExpression('#Suiten</dt>\s*<dd>8</dd>#', $lodging);
        self::assertStringContainsString('***', $lodging);
        self::assertStringContainsString('Sauna', $lodging);
        self::assertStringContainsString('mittel', $lodging);
    }

    #[Test]
    public function showsTheGastronomyFactsOfAVenue(): void
    {
        $gastronomy = $this->renderedSection($this->generateRequestWithCHash('25'), 'venue', 'gastronomy');

        self::assertStringContainsString('Restaurant', $gastronomy);
        self::assertStringContainsString('Regionale Küche', $gastronomy);
        self::assertMatchesRegularExpression('#Sitzplätze innen</dt>\s*<dd>120</dd>#', $gastronomy);
        self::assertMatchesRegularExpression('#Sitzplätze außen</dt>\s*<dd>40</dd>#', $gastronomy);
        self::assertMatchesRegularExpression('#Biergarten</dt>\s*<dd>ja</dd>#', $gastronomy);
        self::assertMatchesRegularExpression('#Catering</dt>\s*<dd>ja</dd>#', $gastronomy);
        self::assertMatchesRegularExpression('#Lieferservice</dt>\s*<dd>nein</dd>#', $gastronomy);
        self::assertMatchesRegularExpression('#Durchgehend warme Küche</dt>\s*<dd>ja</dd>#', $gastronomy);
        self::assertMatchesRegularExpression('#Späte Küche</dt>\s*<dd>nein</dd>#', $gastronomy);
        self::assertMatchesRegularExpression('#Selbstbedienung</dt>\s*<dd>nein</dd>#', $gastronomy);
        self::assertMatchesRegularExpression('#Speisen zum Mitnehmen</dt>\s*<dd>nein</dd>#', $gastronomy);
        self::assertMatchesRegularExpression('#Reservierung möglich</dt>\s*<dd>ja</dd>#', $gastronomy);
        self::assertMatchesRegularExpression('#Drive-in</dt>\s*<dd>nein</dd>#', $gastronomy);
    }

    #[Test]
    public function showsTheVisitFactsOfAVenue(): void
    {
        $visit = $this->renderedSection($this->generateRequestWithCHash('25'), 'venue', 'visit');

        self::assertStringContainsString('Angebot im Indoorbereich', $visit);
        self::assertStringContainsString('Führung mit Anmeldung', $visit);
        self::assertMatchesRegularExpression('#Rauchen erlaubt</dt>\s*<dd>nein</dd>#', $visit);
        self::assertMatchesRegularExpression('#Altersempfehlung</dt>\s*<dd>12</dd>#', $visit);
        self::assertStringContainsString('https://example.com/tickets', $visit);
        self::assertMatchesRegularExpression('#Buchbar</dt>\s*<dd>ja</dd>#', $visit);
        self::assertStringContainsString('Fahrradverleih', $visit);
        self::assertStringContainsString('eigenes Orchester', $visit);
        self::assertStringContainsString('Kulturgeschichte', $visit);
        self::assertMatchesRegularExpression('#Freilichtmuseum</dt>\s*<dd>nein</dd>#', $visit);
        self::assertStringContainsString('Familien', $visit);
        self::assertStringContainsString('Senioren', $visit);
    }

    #[Test]
    public function showsTheBuildingFactsOfAVenue(): void
    {
        $building = $this->renderedSection($this->generateRequestWithCHash('25'), 'venue', 'building');

        self::assertStringContainsString('UNESCO Welterbe (Kultur)', $building);
        self::assertMatchesRegularExpression('#Ruine</dt>\s*<dd>nein</dd>#', $building);
        self::assertStringContainsString('1762', $building);
        self::assertStringContainsString('360.000 m²', $building);
        self::assertStringContainsString('Heinrich Gentz', $building);
        self::assertStringContainsString('Christoph Martin Wieland', $building);
    }

    #[Test]
    public function showsTheLinksOfAVenue(): void
    {
        $links = $this->renderedSection($this->generateRequestWithCHash('25'), 'venue', 'links');

        self::assertStringContainsString('https://example.com/karte', $links);
        self::assertStringContainsString('https://example.com/same-a', $links);
        self::assertStringContainsString('https://example.com/same-b', $links);
        self::assertStringContainsString('https://example.com/kennung', $links);
        self::assertStringContainsString('https://example.com/portal', $links);
    }

    #[Test]
    public function showsTheAdditionalTextsOfAVenue(): void
    {
        $texts = $this->renderedSection($this->generateRequestWithCHash('25'), 'venue', 'texts');

        self::assertStringContainsString('Motorradfreundlicher Hotel- und Gastronomiebetrieb', $texts);
        self::assertStringContainsString('Text aus dem Kulturportal', $texts);
    }

    #[Test]
    public function showsTheRelationsOfAVenue(): void
    {
        $relations = $this->renderedSection($this->generateRequestWithCHash('25'), 'venue', 'relations');

        self::assertStringContainsString('Erfurt Tourismus GmbH', $relations);
        self::assertStringContainsString('Lutherweg', $relations);
        self::assertStringContainsString('Domplatz Erfurt', $relations);
    }

    #[Test]
    public function showsNoVenueGroupWithoutData(): void
    {
        $body = $this->executeFrontendSubRequestAndReturnBody($this->generateRequestWithCHash('26'));

        foreach (['lodging', 'gastronomy', 'visit', 'building', 'links', 'texts', 'relations', 'media', 'parkingFacilities', 'meetingRoomDetails'] as $group) {
            self::assertStringNotContainsString('data-venue="' . $group . '"', $body);
        }
    }

    #[Test]
    public function showsNoVenueOutputForAPlainAttraction(): void
    {
        $body = $this->executeFrontendSubRequestAndReturnBody(
            $this->generateRequestWithCHash('21')
        );

        self::assertStringContainsString('Stadtmuseum Erfurt', $body);
        self::assertStringNotContainsString('data-venue=', $body);
    }

    #[Test]
    public function showsNoMeetingRoomTableForAVenueWithoutRooms(): void
    {
        $request = $this->generateRequestWithCHash('26');

        self::assertStringContainsString('40', $this->renderedSection($request, 'venue', 'meeting'));
        self::assertStringNotContainsString(
            'data-venue="meetingRooms"',
            $this->executeFrontendSubRequestAndReturnBody($request)
        );
    }

    #[Test]
    public function showsAttractionTitle(): void
    {
        $request = $this->generateRequestWithCHash('21');

        $result = $this->executeFrontendSubRequest($request);

        self::assertSame(200, $result->getStatusCode());
        self::assertStringContainsString('Stadtmuseum Erfurt', (string)$result->getBody());
    }

    #[Test]
    public function withoutAttractionParameterShowsNoDataLabel(): void
    {
        $request = (new InternalRequest())->withPageId(10);

        $result = $this->executeFrontendSubRequest($request);

        self::assertSame(200, $result->getStatusCode());
        self::assertStringContainsString('Keine Daten vorhanden.', (string)$result->getBody());
    }

    #[Test]
    public function hiddenAttractionShowsNoDataLabel(): void
    {
        $request = $this->generateRequestWithCHash('20');

        $result = $this->executeFrontendSubRequest($request);

        self::assertSame(200, $result->getStatusCode());
        self::assertStringNotContainsString('Verstecktes Stadtmuseum', (string)$result->getBody());
        self::assertStringContainsString('Keine Daten vorhanden.', (string)$result->getBody());
    }

    #[Test]
    public function rendersBothOpenSpansOfALunchBreakUnderOneWeekday(): void
    {
        $request = $this->generateRequestWithCHash('21');

        $section = $this->renderedSection($request, 'openinghours-format', 'perDayTable');

        // Monday keeps BOTH open spans either
        // side of the lunch break (08:00–12:00 and 13:00–18:00).
        self::assertStringContainsString('Montag', $section);
        self::assertStringContainsString('08:00', $section);
        self::assertStringContainsString('12:00', $section);
        self::assertStringContainsString('13:00', $section);
        self::assertStringContainsString('18:00', $section);
    }

    #[Test]
    public function rendersSpecialPublicHolidayHours(): void
    {
        $request = $this->generateRequestWithCHash('21');

        $section = $this->renderedSection($request, 'openinghours-format', 'perDayTable');

        self::assertStringContainsString('Sonderöffnungszeiten', $section);
        self::assertStringContainsString('Feiertags', $section);
        self::assertStringContainsString('09:00', $section);
    }

    #[Test]
    public function rendersFuturePeriodAfterCurrentOne(): void
    {
        $request = $this->generateRequestWithCHash('21');

        $section = $this->renderedSection($request, 'openinghours-format', 'perDayTable');

        // Future period (Sunday 2026-11-02 – 2027-03-25) renders distinctly.
        self::assertStringContainsString('Sonntag', $section);
        self::assertStringContainsString('02.11.2026', $section);
    }

    #[Test]
    public function mergedByWeekdayFormatCollapsesDaysSharingTheSameHours(): void
    {
        $request = $this->generateRequestWithCHash('21');

        $section = $this->renderedSection($request, 'openinghours-format', 'mergedByWeekday');

        // Monday and Tuesday share identical spans, so the merged format lists
        // them in one grouped row — a marker the per-day format never produces.
        self::assertStringContainsString('Montag, Dienstag', $section);
        self::assertStringContainsString('08:00', $section);
        self::assertStringContainsString('13:00', $section);
    }

    #[Test]
    public function mergedByWeekdayRangesFormatCollapsesConsecutiveDaysIntoARange(): void
    {
        $request = $this->generateRequestWithCHash('21');

        $section = $this->renderedSection($request, 'openinghours-format', 'mergedByWeekdayRanges');

        // Monday and Tuesday are consecutive and share spans, so the ranges
        // format collapses them to "Montag–Dienstag" rather than listing both.
        self::assertStringContainsString('Montag&ndash;Dienstag', $section);
        self::assertStringNotContainsString('Montag, Dienstag', $section);
    }

    private function generateRequestWithCHash(string $attractionUid): InternalRequest
    {
        return $this->detailRequest('tx_thuecat_touristattractionshow', 'attraction', $attractionUid);
    }
}
