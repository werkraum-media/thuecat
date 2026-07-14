<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional\TouristAttraction;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

class TouristAttractionFilterTest extends AbstractFrontendTestCase
{
    protected function getDataSetFileName(): string
    {
        return 'TouristAttractionsForFilter.php';
    }

    /**
     * @param array<string, mixed> $search
     */
    private function requestWithSearch(array $search): string
    {
        $request = (new InternalRequest())
            ->withPageId(10)
            ->withQueryParams(['tx_thuecat_touristattractionlist' => ['demand' => $search]])
        ;

        return (string)$this->executeFrontendSubRequest($request)->getBody();
    }

    #[Test]
    public function withoutFilterShowsAllAttractions(): void
    {
        $body = $this->requestWithSearch([]);

        self::assertStringContainsString('Stadtmuseum Erfurt', $body);
        self::assertStringContainsString('Domberg Erfurt', $body);
        self::assertStringContainsString('Goethehaus Weimar', $body);
    }

    #[Test]
    public function filtersByTown(): void
    {
        $body = $this->requestWithSearch(['towns' => ['1']]);

        self::assertStringContainsString('Stadtmuseum Erfurt', $body);
        self::assertStringContainsString('Domberg Erfurt', $body);
        self::assertStringNotContainsString('Goethehaus Weimar', $body);
    }

    #[Test]
    public function filtersByMultipleTowns(): void
    {
        $body = $this->requestWithSearch(['towns' => ['1', '2']]);

        self::assertStringContainsString('Stadtmuseum Erfurt', $body);
        self::assertStringContainsString('Domberg Erfurt', $body);
        self::assertStringContainsString('Goethehaus Weimar', $body);
    }

    #[Test]
    public function filtersByPetsAllowed(): void
    {
        $body = $this->requestWithSearch(['petsAllowed' => '1']);

        self::assertStringContainsString('Stadtmuseum Erfurt', $body);
        self::assertStringContainsString('Goethehaus Weimar', $body);
        self::assertStringNotContainsString('Domberg Erfurt', $body);
    }

    #[Test]
    public function filtersByIsAccessibleForFree(): void
    {
        $body = $this->requestWithSearch(['isAccessibleForFree' => '1']);

        self::assertStringContainsString('Stadtmuseum Erfurt', $body);
        self::assertStringNotContainsString('Domberg Erfurt', $body);
        self::assertStringNotContainsString('Goethehaus Weimar', $body);
    }

    #[Test]
    public function filtersByPublicAccess(): void
    {
        $body = $this->requestWithSearch(['publicAccess' => '1']);

        self::assertStringContainsString('Stadtmuseum Erfurt', $body);
        self::assertStringNotContainsString('Domberg Erfurt', $body);
        self::assertStringNotContainsString('Goethehaus Weimar', $body);
    }

    #[Test]
    public function filtersByCategory(): void
    {
        // Category 10 (Museum) is related only to Stadtmuseum Erfurt.
        $body = $this->requestWithSearch(['categories' => ['10']]);

        self::assertStringContainsString('Stadtmuseum Erfurt', $body);
        self::assertStringNotContainsString('Domberg Erfurt', $body);
        self::assertStringNotContainsString('Goethehaus Weimar', $body);
    }

    #[Test]
    public function filtersByMultipleCategories(): void
    {
        // Museum (10) → Stadtmuseum, Haus (12) → Goethehaus; Kirche (11) excluded.
        $body = $this->requestWithSearch(['categories' => ['10', '12']]);

        self::assertStringContainsString('Stadtmuseum Erfurt', $body);
        self::assertStringContainsString('Goethehaus Weimar', $body);
        self::assertStringNotContainsString('Domberg Erfurt', $body);
    }

    #[Test]
    public function combinesTownAndFlagWithAndLogic(): void
    {
        $body = $this->requestWithSearch(['towns' => ['1'], 'petsAllowed' => '1']);

        self::assertStringContainsString('Stadtmuseum Erfurt', $body);
        self::assertStringNotContainsString('Domberg Erfurt', $body);
        self::assertStringNotContainsString('Goethehaus Weimar', $body);
    }

    #[Test]
    public function filtersBySearchword(): void
    {
        $body = $this->requestWithSearch(['searchword' => 'Goethehaus']);

        self::assertStringContainsString('Goethehaus Weimar', $body);
        self::assertStringNotContainsString('Stadtmuseum Erfurt', $body);
        self::assertStringNotContainsString('Domberg Erfurt', $body);
    }
}
