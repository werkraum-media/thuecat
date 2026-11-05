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
    public function showsTheParkingFacilitiesNearBy(): void
    {
        $parking = $this->renderedSection($this->generateRequestWithCHash('27'), 'relation', 'parkingFacilityNearBy');

        self::assertStringContainsString('Parkhaus Domplatz', $parking);
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
