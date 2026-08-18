<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional\TouristAttraction;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

class TouristAttractionPaginationTest extends AbstractFrontendTestCase
{
    protected function getDataSetFileName(): string
    {
        return 'TouristAttractionsForPagination.php';
    }

    protected function getRenderingTypoScript(): string
    {
        return 'PaginationRendering.typoscript';
    }

    #[Test]
    public function firstPageShowsOnlyConfiguredNumberOfItems(): void
    {
        $request = (new InternalRequest())->withPageId(10);

        $body = (string)$this->executeFrontendSubRequest($request)->getBody();

        self::assertStringContainsString('Attraction 01', $body);
        self::assertStringContainsString('Attraction 10', $body);
        self::assertStringNotContainsString('Attraction 11', $body);
    }

    #[Test]
    public function secondPageShowsNextItems(): void
    {
        $request = (new InternalRequest())
            ->withPageId(10)
            ->withQueryParams(['tx_thuecat_touristattractionlist' => ['currentPage' => '2']])
        ;

        $body = (string)$this->executeFrontendSubRequest($request)->getBody();

        self::assertStringContainsString('Attraction 11', $body);
        self::assertStringContainsString('Attraction 20', $body);
        self::assertStringNotContainsString('Attraction 01<', $body);
        self::assertStringNotContainsString('Attraction 21', $body);
    }

    #[Test]
    public function rendersAPaginationLinkPerPage(): void
    {
        $request = (new InternalRequest())->withPageId(10);

        $body = (string)$this->executeFrontendSubRequest($request)->getBody();

        self::assertStringContainsString('tx_thuecat_touristattractionlist%5BcurrentPage%5D=2', $body);
        self::assertStringContainsString('tx_thuecat_touristattractionlist%5BcurrentPage%5D=3', $body);
    }

    #[Test]
    public function rendersPaginationSummaryWithItemsPerPageAndPageCount(): void
    {
        $request = (new InternalRequest())->withPageId(10);

        $body = (string)$this->executeFrontendSubRequest($request)->getBody();

        // 25 fixtures, itemsPerPage = 10 => 3 pages
        self::assertStringContainsString('10 Einträge pro Seite, 3 Seiten insgesamt', $body);
    }

    // 3.5: a keyword-filtered list must keep its selection across pages, both in
    // the links it renders and in the result set the next page returns.
    #[Test]
    public function keywordSelectionIsCarriedIntoPaginationLinks(): void
    {
        $request = (new InternalRequest())
            ->withPageId(10)
            ->withQueryParams(['tx_thuecat_touristattractionlist' => ['demand' => ['keywords' => ['501']]]])
        ;

        $body = (string)$this->executeFrontendSubRequest($request)->getBody();

        // 13 odd-numbered attractions carry the keyword => 2 pages.
        self::assertStringContainsString('10 Einträge pro Seite, 2 Seiten insgesamt', $body);
        self::assertStringContainsString(
            'tx_thuecat_touristattractionlist%5Bdemand%5D%5Bkeywords%5D%5B0%5D=501',
            $body
        );
    }

    #[Test]
    public function keywordFilterStillAppliesOnTheSecondPage(): void
    {
        $request = (new InternalRequest())
            ->withPageId(10)
            ->withQueryParams(['tx_thuecat_touristattractionlist' => [
                'demand' => ['keywords' => ['501']],
                'currentPage' => '2',
            ]])
        ;

        $body = (string)$this->executeFrontendSubRequest($request)->getBody();

        // Page 1 holds the first ten odd ones (01-19); page 2 the rest (21-25).
        self::assertStringContainsString('Attraction 21', $body);
        self::assertStringContainsString('Attraction 25', $body);
        // An even-numbered attraction would mean the filter was dropped.
        self::assertStringNotContainsString('Attraction 22', $body);
    }
}
