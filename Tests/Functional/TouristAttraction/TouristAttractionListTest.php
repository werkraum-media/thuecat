<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional\TouristAttraction;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

class TouristAttractionListTest extends AbstractFrontendTestCase
{
    protected function getDataSetFileName(): string
    {
        return 'TouristAttractionsForList.php';
    }

    #[Test]
    public function listShowsAllAttractionsWithoutSearch(): void
    {
        $request = (new InternalRequest())->withPageId(10);

        $result = $this->executeFrontendSubRequest($request);

        self::assertSame(200, $result->getStatusCode());
        self::assertStringContainsString('Stadtmuseum Erfurt', (string)$result->getBody());
        self::assertStringContainsString('Domberg Erfurt', (string)$result->getBody());
    }

    #[Test]
    public function listWithEmptySearchWordReturnsAllAttractions(): void
    {
        $request = (new InternalRequest())
            ->withPageId(10)
            ->withQueryParams(['tx_thuecat_touristattractionlist' => ['demand' => ['searchword' => '']]])
        ;

        $result = $this->executeFrontendSubRequest($request);

        self::assertSame(200, $result->getStatusCode());
        self::assertStringContainsString('Stadtmuseum Erfurt', (string)$result->getBody());
        self::assertStringContainsString('Domberg Erfurt', (string)$result->getBody());
    }

    #[Test]
    public function listFiltersBySearchWord(): void
    {
        $request = (new InternalRequest())
            ->withPageId(10)
            ->withQueryParams(['tx_thuecat_touristattractionlist' => ['demand' => ['searchword' => 'Stadtmuseum']]])
        ;

        $result = $this->executeFrontendSubRequest($request);

        self::assertSame(200, $result->getStatusCode());
        self::assertStringContainsString('Stadtmuseum Erfurt', (string)$result->getBody());
        self::assertStringNotContainsString('Domberg Erfurt', (string)$result->getBody());
    }

    #[Test]
    public function listShowsEmptyMessageWhenSearchHasNoMatches(): void
    {
        $request = (new InternalRequest())
            ->withPageId(10)
            ->withQueryParams(['tx_thuecat_touristattractionlist' => ['demand' => ['searchword' => 'GibtEsNicht']]])
        ;

        $result = $this->executeFrontendSubRequest($request);

        self::assertSame(200, $result->getStatusCode());
        self::assertStringNotContainsString('Stadtmuseum Erfurt', (string)$result->getBody());
        self::assertStringNotContainsString('Domberg Erfurt', (string)$result->getBody());
        self::assertStringContainsString('Keine Einträge vorhanden.', (string)$result->getBody());
    }

    #[Test]
    public function listFindsAllMatchesAcrossTitleSubstring(): void
    {
        $request = (new InternalRequest())
            ->withPageId(10)
            ->withQueryParams(['tx_thuecat_touristattractionlist' => ['demand' => ['searchword' => 'Erfurt']]])
        ;

        $result = $this->executeFrontendSubRequest($request);

        self::assertSame(200, $result->getStatusCode());
        self::assertStringContainsString('Stadtmuseum Erfurt', (string)$result->getBody());
        self::assertStringContainsString('Domberg Erfurt', (string)$result->getBody());
    }
}
