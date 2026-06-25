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

        $body = (string)$this->executeFrontendSubRequest($request)->getBody();

        // Monday keeps BOTH open spans either
        // side of the lunch break (08:00–12:00 and 13:00–18:00).
        self::assertStringContainsString('Montag', $body);
        self::assertStringContainsString('08:00', $body);
        self::assertStringContainsString('12:00', $body);
        self::assertStringContainsString('13:00', $body);
        self::assertStringContainsString('18:00', $body);
    }

    #[Test]
    public function rendersSpecialPublicHolidayHours(): void
    {
        $request = $this->generateRequestWithCHash('21');

        $body = (string)$this->executeFrontendSubRequest($request)->getBody();

        self::assertStringContainsString('Sonderöffnungszeiten', $body);
        self::assertStringContainsString('Feiertags', $body);
        self::assertStringContainsString('09:00', $body);
    }

    #[Test]
    public function rendersFuturePeriodAfterCurrentOne(): void
    {
        $request = $this->generateRequestWithCHash('21');

        $body = (string)$this->executeFrontendSubRequest($request)->getBody();

        // Future period (Sunday 2026-11-02 – 2027-03-25) renders distinctly.
        self::assertStringContainsString('Sonntag', $body);
        self::assertStringContainsString('02.11.2026', $body);
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
