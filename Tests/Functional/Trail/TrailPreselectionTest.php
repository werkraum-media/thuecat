<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional\Trail;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use WerkraumMedia\ThueCat\Tests\Functional\TouristAttraction\AbstractFrontendTestCase;

/**
 * The backend selection wins over the frontend query parameter.
 *
 * Every page here carries one show plugin whose pi_flexform holds
 * settings.selectedRecord; no TypoScript sets that value, so the flexform is
 * the only source under test.
 */
class TrailPreselectionTest extends AbstractFrontendTestCase
{
    protected function getDataSetFileName(): string
    {
        return 'TrailsForPreselection.php';
    }

    #[Test]
    public function showsTheConfiguredRecordWithoutAQueryParameter(): void
    {
        $request = (new InternalRequest())->withPageId(10);

        $body = (string)$this->executeFrontendSubRequest($request)->getBody();

        self::assertStringContainsString('Goethe-Erlebnisweg', $body);
        self::assertStringNotContainsString('Keine Daten vorhanden.', $body);
    }

    #[Test]
    public function configuredRecordWinsOverAQueryParameterNamingAnotherRecord(): void
    {
        $request = $this->detailRequest('tx_thuecat_trailshow', 'trail', '23', 10);

        $body = (string)$this->executeFrontendSubRequest($request)->getBody();

        self::assertStringContainsString('Goethe-Erlebnisweg', $body);
        self::assertStringNotContainsString(
            'Ilmtal-Radweg',
            $body,
            'The query parameter must not override the editor-configured record.'
        );
    }

    #[Test]
    public function configuredRecordWinsOverAQueryParameterNamingAHiddenRecord(): void
    {
        $request = $this->detailRequest('tx_thuecat_trailshow', 'trail', '20', 10);

        $body = (string)$this->executeFrontendSubRequest($request)->getBody();

        self::assertStringContainsString('Goethe-Erlebnisweg', $body);
        self::assertStringNotContainsString('Keine Daten vorhanden.', $body);
    }

    #[Test]
    public function configuredRecordWinsOverAQueryParameterNamingAnUnknownRecord(): void
    {
        $request = $this->detailRequest('tx_thuecat_trailshow', 'trail', '9999', 10);

        $body = (string)$this->executeFrontendSubRequest($request)->getBody();

        self::assertStringContainsString('Goethe-Erlebnisweg', $body);
        self::assertStringNotContainsString('Keine Daten vorhanden.', $body);
    }

    #[Test]
    public function withoutAConfiguredRecordTheQueryParameterDecides(): void
    {
        $request = $this->detailRequest('tx_thuecat_trailshow', 'trail', '23', 11);

        $body = (string)$this->executeFrontendSubRequest($request)->getBody();

        self::assertStringContainsString('Ilmtal-Radweg', $body);
    }

    #[Test]
    public function withoutAConfiguredRecordAndWithoutAParameterNothingIsShown(): void
    {
        $request = (new InternalRequest())->withPageId(11);

        $result = $this->executeFrontendSubRequest($request);

        self::assertSame(200, $result->getStatusCode());
        self::assertStringContainsString('Keine Daten vorhanden.', (string)$result->getBody());
    }

    #[Test]
    public function aHiddenConfiguredRecordShowsNoDataRatherThanTheQueryParameter(): void
    {
        $request = $this->detailRequest('tx_thuecat_trailshow', 'trail', '23', 12);

        $result = $this->executeFrontendSubRequest($request);
        $body = (string)$result->getBody();

        self::assertSame(200, $result->getStatusCode());
        self::assertStringContainsString('Keine Daten vorhanden.', $body);
        self::assertStringNotContainsString(
            'Ilmtal-Radweg',
            $body,
            'An unresolvable pick must not fall back to the query parameter.'
        );
        self::assertStringNotContainsString('Versteckter Weg', $body);
    }

    #[Test]
    public function aHiddenConfiguredRecordShowsNoDataWithoutAQueryParameter(): void
    {
        $request = (new InternalRequest())->withPageId(12);

        $result = $this->executeFrontendSubRequest($request);

        self::assertSame(200, $result->getStatusCode());
        self::assertStringContainsString('Keine Daten vorhanden.', (string)$result->getBody());
    }

    #[Test]
    public function theConfiguredRecordRendersItsTranslation(): void
    {
        $body = (string)$this->executeFrontendSubRequest($this->pageRequest(10, 1))->getBody();

        self::assertStringContainsString('Goethe experience trail', $body);
        self::assertStringNotContainsString('No data to show.', $body);
    }

    #[Test]
    public function aConfiguredRecordThatDoesNotExistShowsNoData(): void
    {
        $request = (new InternalRequest())->withPageId(15);

        $result = $this->executeFrontendSubRequest($request);

        self::assertSame(200, $result->getStatusCode());
        self::assertStringContainsString('Keine Daten vorhanden.', (string)$result->getBody());
    }

    #[Test]
    public function aConfiguredRecordThatDoesNotExistIgnoresTheQueryParameter(): void
    {
        $request = $this->detailRequest('tx_thuecat_trailshow', 'trail', '23', 15);

        $body = (string)$this->executeFrontendSubRequest($request)->getBody();

        self::assertStringContainsString('Keine Daten vorhanden.', $body);
        self::assertStringNotContainsString('Ilmtal-Radweg', $body);
    }

    #[Test]
    public function anUnresolvableConfiguredRecordEmitsNoKeywordsMetaTag(): void
    {
        $request = (new InternalRequest())->withPageId(12);

        $body = (string)$this->executeFrontendSubRequest($request)->getBody();

        self::assertDoesNotMatchRegularExpression('#<meta[^>]+name="keywords"#', $body);
    }
}
