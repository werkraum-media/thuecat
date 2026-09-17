<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional\TouristAttraction;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

/**
 * The backend selection wins over the frontend query parameter.
 *
 * Every page here carries one show plugin whose pi_flexform holds
 * settings.selectedRecord; no TypoScript sets that value, so the flexform is
 * the only source under test.
 */
class TouristAttractionPreselectionTest extends AbstractFrontendTestCase
{
    protected function getDataSetFileName(): string
    {
        return 'TouristAttractionsForPreselection.php';
    }

    #[Test]
    public function showsTheConfiguredRecordWithoutAQueryParameter(): void
    {
        $request = (new InternalRequest())->withPageId(10);

        $body = (string)$this->executeFrontendSubRequest($request)->getBody();

        self::assertStringContainsString('Stadtmuseum Erfurt', $body);
        self::assertStringNotContainsString('Keine Daten vorhanden.', $body);
    }

    #[Test]
    public function configuredRecordWinsOverAQueryParameterNamingAnotherRecord(): void
    {
        $request = $this->detailRequest(
            'tx_thuecat_touristattractionshow',
            'attraction',
            '23',
            10
        );

        $body = (string)$this->executeFrontendSubRequest($request)->getBody();

        self::assertStringContainsString('Stadtmuseum Erfurt', $body);
        self::assertStringNotContainsString(
            'Domplatz Erfurt',
            $body,
            'The query parameter must not override the editor-configured record.'
        );
    }

    #[Test]
    public function configuredRecordWinsOverAQueryParameterNamingAHiddenRecord(): void
    {
        $request = $this->detailRequest(
            'tx_thuecat_touristattractionshow',
            'attraction',
            '20',
            10
        );

        $body = (string)$this->executeFrontendSubRequest($request)->getBody();

        self::assertStringContainsString('Stadtmuseum Erfurt', $body);
        self::assertStringNotContainsString('Keine Daten vorhanden.', $body);
    }

    #[Test]
    public function configuredRecordWinsOverAQueryParameterNamingAnUnknownRecord(): void
    {
        $request = $this->detailRequest(
            'tx_thuecat_touristattractionshow',
            'attraction',
            '9999',
            10
        );

        $body = (string)$this->executeFrontendSubRequest($request)->getBody();

        self::assertStringContainsString('Stadtmuseum Erfurt', $body);
        self::assertStringNotContainsString('Keine Daten vorhanden.', $body);
    }

    #[Test]
    public function withoutAConfiguredRecordTheQueryParameterDecides(): void
    {
        $request = $this->detailRequest(
            'tx_thuecat_touristattractionshow',
            'attraction',
            '23',
            11
        );

        $body = (string)$this->executeFrontendSubRequest($request)->getBody();

        self::assertStringContainsString('Domplatz Erfurt', $body);
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
        $request = $this->detailRequest(
            'tx_thuecat_touristattractionshow',
            'attraction',
            '23',
            12
        );

        $result = $this->executeFrontendSubRequest($request);
        $body = (string)$result->getBody();

        self::assertSame(200, $result->getStatusCode());
        self::assertStringContainsString('Keine Daten vorhanden.', $body);
        self::assertStringNotContainsString(
            'Domplatz Erfurt',
            $body,
            'An unresolvable pick must not fall back to the query parameter.'
        );
        self::assertStringNotContainsString('Verstecktes Stadtmuseum', $body);
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

        self::assertStringContainsString('City Museum Erfurt', $body);
        self::assertStringNotContainsString('No data to show.', $body);
    }

    // French is strict with fallbacks 1,0: the English translation stands in
    // for a record that has no French one.
    #[Test]
    public function theConfiguredRecordFollowsTheLanguageFallbackChain(): void
    {
        $body = (string)$this->executeFrontendSubRequest($this->pageRequest(10, 2))->getBody();

        self::assertStringContainsString('City Museum Erfurt', $body);
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
        $request = $this->detailRequest(
            'tx_thuecat_touristattractionshow',
            'attraction',
            '23',
            15
        );

        $body = (string)$this->executeFrontendSubRequest($request)->getBody();

        self::assertStringContainsString('Keine Daten vorhanden.', $body);
        self::assertStringNotContainsString('Domplatz Erfurt', $body);
    }

    #[Test]
    public function anUnresolvableConfiguredRecordEmitsNoKeywordsMetaTag(): void
    {
        $request = (new InternalRequest())->withPageId(12);

        $body = (string)$this->executeFrontendSubRequest($request)->getBody();

        self::assertDoesNotMatchRegularExpression('#<meta[^>]+name="keywords"#', $body);
    }
}
