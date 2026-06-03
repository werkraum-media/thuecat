<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional\TouristAttraction;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

class TouristAttractionSearchTest extends AbstractFrontendTestCase
{
    protected function getDataSetFileName(): string
    {
        return 'TouristAttractionsForList.php';
    }

    #[Test]
    public function searchFormIsRenderedOnSearchPage(): void
    {
        $request = (new InternalRequest())->withPageId(12);

        $result = $this->executeFrontendSubRequest($request);
        $body = (string)$result->getBody();

        self::assertSame(200, $result->getStatusCode());
        self::assertStringContainsString('<form', $body);
        self::assertStringContainsString('name="thuecat[demand][searchword]"', $body);
    }

    #[Test]
    public function searchFormActionTargetsListPage(): void
    {
        $request = (new InternalRequest())->withPageId(12);

        $body = (string)$this->executeFrontendSubRequest($request)->getBody();

        // form action attribute should point at the list page (uid 10, slug /list/)
        self::assertMatchesRegularExpression('#<form[^>]+action="[^"]*/list/[^"]*"#', $body);
    }

    #[Test]
    public function searchFormDoesNotRenderResultList(): void
    {
        $request = (new InternalRequest())->withPageId(12);

        $body = (string)$this->executeFrontendSubRequest($request)->getBody();

        self::assertStringNotContainsString('Stadtmuseum Erfurt', $body);
        self::assertStringNotContainsString('Domberg Erfurt', $body);
    }

    #[Test]
    public function searchFormPrefillsValueFromUrl(): void
    {
        $request = (new InternalRequest())
            ->withPageId(12)
            ->withQueryParams(['thuecat' => ['demand' => ['searchword' => 'Stadtmuseum']]])
        ;

        $body = (string)$this->executeFrontendSubRequest($request)->getBody();

        self::assertMatchesRegularExpression('#<input[^>]+name="thuecat\[demand\]\[searchword\]"[^>]+value="Stadtmuseum"#', $body);
    }
}
