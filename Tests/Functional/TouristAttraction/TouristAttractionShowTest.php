<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional\TouristAttraction;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

class TouristAttractionShowTest extends AbstractFrontendTestCase
{
    protected function getDataSetFileName(): string
    {
        return 'TouristAttractionsForShow.php';
    }

    protected function getRenderingTypoScript(): string
    {
        return 'ShowRendering.typoscript';
    }

    #[Test]
    public function showsContainingOrganisationsAndPlaces(): void
    {
        $request = $this->generateRequestWithCHash('21');

        $organisations = $this->renderedSection($request, 'relation', 'containedInOrganisation');
        self::assertStringContainsString('Erfurt Tourismus GmbH', $organisations);

        $places = $this->renderedSection($request, 'relation', 'containedInPlace');
        self::assertStringContainsString('Domplatz Erfurt', $places);
    }

    #[Test]
    public function showsNoRelationSectionsWhenTheAttractionIsContainedInNothing(): void
    {
        // Attraction 24 carries neither relation: an empty set renders no
        // container at all, rather than an empty one.
        $body = (string)$this->executeFrontendSubRequest(
            $this->generateRequestWithCHash('24')
        )->getBody();

        self::assertStringNotContainsString('data-relation="containedInOrganisation"', $body);
        self::assertStringNotContainsString('data-relation="containedInPlace"', $body);
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
