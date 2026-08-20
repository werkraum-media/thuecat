<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional\TouristAttraction;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Town;
use WerkraumMedia\ThueCat\Domain\Repository\Frontend\TouristAttractionRepository;

class TouristAttractionSeveralTownsTest extends AbstractFrontendTestCase
{
    protected function getDataSetFileName(): string
    {
        return 'TouristAttractionsInSeveralTowns.php';
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
    public function attractionInTwoTownsMatchesAFilterOnEither(): void
    {
        $erfurt = $this->requestWithSearch(['towns' => ['1']]);
        self::assertStringContainsString('Flughafen Erfurt-Weimar', $erfurt);
        self::assertStringContainsString('Stadtmuseum Erfurt', $erfurt);
        self::assertStringNotContainsString('Goethehaus Weimar', $erfurt);

        $weimar = $this->requestWithSearch(['towns' => ['2']]);
        self::assertStringContainsString('Flughafen Erfurt-Weimar', $weimar);
        self::assertStringContainsString('Goethehaus Weimar', $weimar);
        self::assertStringNotContainsString('Stadtmuseum Erfurt', $weimar);
    }

    #[Test]
    public function attractionInTwoSelectedTownsAppearsOnce(): void
    {
        $body = $this->requestWithSearch(['towns' => ['1', '2']]);

        self::assertSame(
            1,
            substr_count($body, 'Flughafen Erfurt-Weimar'),
            'Matching two selected towns must not duplicate the record.'
        );
    }

    #[Test]
    public function filteringOnATownNoRecordCarriesReturnsNothing(): void
    {
        $body = $this->requestWithSearch(['towns' => ['3']]);

        self::assertStringNotContainsString('Flughafen Erfurt-Weimar', $body);
        self::assertStringNotContainsString('Stadtmuseum Erfurt', $body);
        self::assertStringNotContainsString('Goethehaus Weimar', $body);
    }

    #[Test]
    public function filteringOnATownThatDoesNotExistReturnsNothingRatherThanEverything(): void
    {
        $body = $this->requestWithSearch(['towns' => ['999']]);

        self::assertStringNotContainsString('Flughafen Erfurt-Weimar', $body);
        self::assertStringNotContainsString('Stadtmuseum Erfurt', $body);
        self::assertStringNotContainsString('Goethehaus Weimar', $body);
    }

    #[Test]
    public function townFacetUnionsEveryTownItsAttractionsCarrySortedByTitle(): void
    {
        $towns = $this->get(TouristAttractionRepository::class)
            ->findTownsInStorageSortedByTitle([11])
        ;

        $titles = array_map(
            static fn (Town $town): string => $town->getTitle(),
            $towns
        );

        // Erfurt and Weimar each carried by two records, listed once; Jena is
        // stored but carried by none; the town-less record adds nothing.
        self::assertSame(['Erfurt', 'Weimar'], $titles);
    }
}
