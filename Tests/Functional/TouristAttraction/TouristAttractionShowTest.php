<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional\TouristAttraction;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Page\CacheHashCalculator;
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

    /**
     * Render the request and return only the markup of the
     * <section data-{$attribute}="..."> block, so assertions cannot accidentally
     * match a sibling section's output. The next marked section (same data
     * attribute) bounds this one; partials may emit their own plain <section>, so
     * the boundary keys on the data attribute, not on <section> alone.
     */
    private function renderedSection(InternalRequest $request, string $attribute, string $value): string
    {
        $body = (string)$this->executeFrontendSubRequest($request)->getBody();

        $marker = '<section data-' . $attribute . '="';
        $open = $marker . $value . '">';
        $start = strpos($body, $open);
        self::assertNotFalse($start, 'Section ' . $attribute . '="' . $value . '" not rendered.');

        $rest = substr($body, $start + strlen($open));
        $end = strpos($rest, $marker);

        return $end === false ? $rest : substr($rest, 0, $end);
    }

    /**
     * The attraction parameter is cacheable, so the request needs a valid cHash
     * (a real list link carries one); compute it the same way the core does.
     */
    private function generateRequestWithCHash(string $attractionUid): InternalRequest
    {
        $queryParams = ['tx_thuecat_touristattractionshow' => ['attraction' => $attractionUid]];
        $cacheHashCalculator = GeneralUtility::makeInstance(CacheHashCalculator::class);
        $cHash = $cacheHashCalculator->generateForParameters(
            http_build_query($queryParams + ['id' => 10])
        );

        return (new InternalRequest())
            ->withPageId(10)
            ->withQueryParams($queryParams + ['cHash' => $cHash])
        ;
    }
}
