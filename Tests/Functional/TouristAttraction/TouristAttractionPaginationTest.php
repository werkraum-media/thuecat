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
            ->withQueryParams(['thuecat' => ['currentPage' => '2']])
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

        self::assertStringContainsString('thuecat%5BcurrentPage%5D=2', $body);
        self::assertStringContainsString('thuecat%5BcurrentPage%5D=3', $body);
    }

    #[Test]
    public function rendersPaginationSummaryWithItemsPerPageAndPageCount(): void
    {
        $request = (new InternalRequest())->withPageId(10);

        $body = (string)$this->executeFrontendSubRequest($request)->getBody();

        // 25 fixtures, itemsPerPage = 10 => 3 pages
        self::assertStringContainsString('10 Einträge pro Seite, 3 Seiten insgesamt', $body);
    }
}
