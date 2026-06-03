<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional\TouristAttraction;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\StreamFactory;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

class TouristAttractionSearchListTest extends AbstractFrontendTestCase
{
    protected function getDataSetFileName(): string
    {
        return 'TouristAttractionsForSearchList.php';
    }

    protected function getRenderingTypoScript(): string
    {
        return 'SearchListRendering.typoscript';
    }

    #[Test]
    public function combinedPageRendersFormAndList(): void
    {
        $request = (new InternalRequest())->withPageId(10);

        $result = $this->executeFrontendSubRequest($request);
        $body = (string)$result->getBody();

        self::assertSame(200, $result->getStatusCode());
        self::assertStringContainsString('name="thuecat[demand][searchword]"', $body);
        self::assertStringContainsString('Erfurt Frei 01', $body);
    }

    #[Test]
    public function searchPluginDoesNotRenderListAndListPluginDoesNotRenderForm(): void
    {
        $request = (new InternalRequest())->withPageId(10);

        $body = (string)$this->executeFrontendSubRequest($request)->getBody();

        // Exactly one form (search plugin), and no list articles appear before it.
        self::assertSame(1, substr_count($body, '<form'));
        $beforeForm = substr($body, 0, (int)strpos($body, '<form'));
        self::assertStringNotContainsString('Erfurt Frei', $beforeForm);
    }

    #[Test]
    public function getFilterByTownAndFreeNarrowsTheList(): void
    {
        $request = (new InternalRequest())
            ->withPageId(10)
            ->withQueryParams(['thuecat' => ['demand' => [
                'towns' => [1],
                'isAccessibleForFree' => '1',
            ]]])
        ;

        $body = (string)$this->executeFrontendSubRequest($request)->getBody();

        self::assertStringContainsString('Erfurt Frei 01', $body);
        self::assertStringNotContainsString('Erfurt Kostenpflichtig', $body);
        self::assertStringNotContainsString('Weimar Frei', $body);
    }

    #[Test]
    public function formRepopulatesSubmittedFilterState(): void
    {
        $request = (new InternalRequest())
            ->withPageId(10)
            ->withQueryParams(['thuecat' => ['demand' => [
                'towns' => [1],
                'isAccessibleForFree' => '1',
            ]]])
        ;

        $body = (string)$this->executeFrontendSubRequest($request)->getBody();

        self::assertMatchesRegularExpression('#<option value="1"[^>]*selected#', $body);
        self::assertMatchesRegularExpression(
            '#name="thuecat\[demand\]\[isAccessibleForFree\]"[^>]*checked#',
            $body
        );
    }

    #[Test]
    public function paginationLinksCarryTheDemand(): void
    {
        $request = (new InternalRequest())
            ->withPageId(10)
            ->withQueryParams(['thuecat' => ['demand' => [
                'towns' => [1],
                'isAccessibleForFree' => '1',
            ]]])
        ;

        $body = (string)$this->executeFrontendSubRequest($request)->getBody();

        // 15 free Erfurt records, itemsPerPage 10 -> a second page exists, and its
        // link must keep the active filter so paging does not reset the search.
        self::assertStringContainsString('thuecat%5BcurrentPage%5D=2', $body);
        self::assertStringContainsString('thuecat%5Bdemand%5D%5Btowns%5D%5B0%5D=1', $body);
        self::assertStringContainsString('thuecat%5Bdemand%5D%5BisAccessibleForFree%5D=1', $body);
    }

    #[Test]
    public function postRedirectsToCleanGetUrlWithDemand(): void
    {
        $request = (new InternalRequest())
            ->withPageId(10)
            ->withMethod('POST')
            ->withBody((new StreamFactory())->createStream(http_build_query([
                'thuecat' => ['demand' => ['searchword' => 'Erfurt Frei 01']],
            ])))
        ;

        $response = $this->executeFrontendSubRequest($request);

        self::assertSame(303, $response->getStatusCode());
        $location = $response->getHeaderLine('location');
        self::assertStringContainsString('thuecat%5Bdemand%5D%5Bsearchword%5D=Erfurt', $location);
        self::assertStringNotContainsString('__referrer', $location);
        self::assertStringNotContainsString('__trustedProperties', $location);
    }
}
