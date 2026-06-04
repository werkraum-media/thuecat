<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional\TouristAttraction;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

/**
 * Covers the editor-curated "filtered" list variant: the editor presets filter
 * criteria in the backend (here via TypoScript settings, as the flexform would
 * deliver them), and listAction builds the demand from those settings.
 *
 * This is a proof of concept limited to the "town" criterion. Extend the
 * fixture settings and assertions as further filterable fields become available
 * in the imported records.
 */
class TouristAttractionFilteredTest extends AbstractFrontendTestCase
{
    protected function getDataSetFileName(): string
    {
        return 'TouristAttractionsForFiltered.php';
    }

    protected function getRenderingTypoScript(): string
    {
        return 'FilteredRendering.typoscript';
    }

    #[Test]
    public function showsOnlyAttractionsOfPresetTown(): void
    {
        $request = (new InternalRequest())->withPageId(10);

        $body = (string)$this->executeFrontendSubRequest($request)->getBody();

        // settings.towns = 1 (Erfurt) -> Stadtmuseum (1) and Domberg (2)
        self::assertStringContainsString('Stadtmuseum Erfurt', $body);
        self::assertStringContainsString('Domberg Erfurt', $body);
        self::assertStringNotContainsString('Goethehaus Weimar', $body);
    }

    #[Test]
    public function visitorCannotWidenPastTheLockedTown(): void
    {
        // Editor locked town 1; a tampered URL asking for town 2 must be ignored.
        $request = (new InternalRequest())
            ->withPageId(10)
            ->withQueryParams(['tx_thuecat_touristattractionlist' => ['demand' => ['towns' => [2]]]])
        ;

        $body = (string)$this->executeFrontendSubRequest($request)->getBody();

        self::assertStringContainsString('Stadtmuseum Erfurt', $body);
        self::assertStringNotContainsString('Goethehaus Weimar', $body);
    }
}
