<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional\Trail;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use WerkraumMedia\ThueCat\Tests\Functional\TouristAttraction\AbstractFrontendTestCase;

class TrailShowTest extends AbstractFrontendTestCase
{
    protected function getDataSetFileName(): string
    {
        return 'TrailsForShow.php';
    }

    #[Test]
    public function showsTrailTitleAndDescription(): void
    {
        $request = $this->detailRequest('tx_thuecat_trailshow', 'trail', '21');

        $result = $this->executeFrontendSubRequest($request);

        self::assertSame(200, $result->getStatusCode());
        $body = (string)$result->getBody();
        self::assertStringContainsString('Goethe-Erlebnisweg', $body);
        self::assertStringContainsString('Beschreibung des Goethe-Erlebniswegs', $body);
    }

    #[Test]
    public function showsRelatedKeywords(): void
    {
        $request = $this->detailRequest('tx_thuecat_trailshow', 'trail', '21');

        $keywords = $this->renderedSection($request, 'relation', 'keywords');

        self::assertStringContainsString('Themenweg', $keywords);
        self::assertStringContainsString('Fahrradfreundlich', $keywords);
    }

    #[Test]
    public function showsSeasonMonthsResolvedFromTheBitmask(): void
    {
        $request = $this->detailRequest('tx_thuecat_trailshow', 'trail', '21');

        $season = $this->renderedSection($request, 'trail', 'season');

        self::assertStringContainsString('March', $season);
        self::assertStringContainsString('October', $season);
        self::assertStringNotContainsString('February', $season);
        self::assertStringNotContainsString('November', $season);
    }

    #[Test]
    public function showsNoSeasonSectionWhenTheMaskIsEmpty(): void
    {
        $request = $this->detailRequest('tx_thuecat_trailshow', 'trail', '20');

        $body = (string)$this->executeFrontendSubRequest($request)->getBody();

        self::assertStringNotContainsString('data-trail="season"', $body);
    }

    #[Test]
    public function showsNoKeywordSectionWhenTheTrailCarriesNone(): void
    {
        $request = $this->detailRequest('tx_thuecat_trailshow', 'trail', '20');

        $body = (string)$this->executeFrontendSubRequest($request)->getBody();

        self::assertStringContainsString('Weg ohne Schlagworte', $body);
        self::assertStringNotContainsString('data-relation="keywords"', $body);
    }

    #[Test]
    public function withoutTrailParameterShowsNoDataLabel(): void
    {
        $request = (new InternalRequest())->withPageId(10);

        $result = $this->executeFrontendSubRequest($request);

        self::assertSame(200, $result->getStatusCode());
        $body = (string)$result->getBody();
        self::assertStringContainsString('Keine Daten vorhanden.', $body);
        self::assertDoesNotMatchRegularExpression('#<meta[^>]+name="keywords"#', $body);
    }

    #[Test]
    public function hiddenTrailShowsNoDataLabel(): void
    {
        $request = $this->detailRequest('tx_thuecat_trailshow', 'trail', '23');

        $result = $this->executeFrontendSubRequest($request);

        self::assertSame(200, $result->getStatusCode());
        $body = (string)$result->getBody();
        self::assertStringNotContainsString('Versteckter Weg', $body);
        self::assertStringContainsString('Keine Daten vorhanden.', $body);
    }

    // The record carries keywords, so an unresolved trail must not emit them.
    #[Test]
    public function hiddenTrailEmitsNoKeywordsMetaTag(): void
    {
        $request = $this->detailRequest('tx_thuecat_trailshow', 'trail', '23');

        $body = (string)$this->executeFrontendSubRequest($request)->getBody();

        self::assertDoesNotMatchRegularExpression('#<meta[^>]+name="keywords"#', $body);
    }
}
