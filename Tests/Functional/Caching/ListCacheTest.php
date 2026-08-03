<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional\Caching;

use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use WerkraumMedia\ThueCat\Extension;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractCachingTestCase;

/**
 * A rendered list is stored per plugin, demand, pagination page and language.
 *
 * 25 attractions and one list plugin, at `settings.itemsPerPage = 10` from
 * `PaginationRendering.typoscript`: page 1 shows 1-10, page 2 shows 11-20.
 */
class ListCacheTest extends AbstractCachingTestCase
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
    public function aRenderedListIsStored(): void
    {
        $cold = $this->listIdentifiers();

        $this->requestPage();

        self::assertSame([], $cold, 'Cache starts cold.');
        self::assertCount(1, $this->listIdentifiers(), 'The list leaves one entry.');
    }

    #[Test]
    public function repeatingARequestReusesItsEntry(): void
    {
        $this->requestPage();
        $afterFirst = $this->storedLists();

        $this->requestPage();

        self::assertSame($afterFirst, $this->storedLists(), 'No second entry, same markup.');
    }

    /** Pagination pages must not share an entry. */
    #[Test]
    public function eachPaginationPageIsItsOwnEntry(): void
    {
        $this->requestPage();
        $afterPageOne = $this->listIdentifiers();

        $this->requestPage(2);
        $afterPageTwo = $this->listIdentifiers();

        self::assertCount(1, $afterPageOne, 'Page 1 stored.');
        self::assertCount(2, $afterPageTwo, 'Page 2 must not reuse page 1.');
    }

    /** Asserted on entries: a body looks right whenever the cache is cold. */
    #[Test]
    public function pageTwoIsServedItsOwnEntry(): void
    {
        $this->requestPage();

        $this->requestPage(2);

        $stored = $this->storedLists();
        self::assertCount(2, $stored, 'One entry per pagination page.');

        $combined = implode("\n", $stored);
        self::assertStringContainsString('Attraction 01', $combined, 'Page 1 content.');
        self::assertStringContainsString('Attraction 11', $combined, 'Page 2 content.');

        // No entry may hold both pages' content.
        foreach ($stored as $identifier => $markup) {
            self::assertFalse(
                str_contains($markup, 'Attraction 01') && str_contains($markup, 'Attraction 11'),
                'Entry "' . $identifier . '" mixes both pagination pages.'
            );
        }
    }

    #[Test]
    public function aDifferentDemandIsItsOwnEntry(): void
    {
        $this->requestPage();
        $unfiltered = $this->listIdentifiers();

        $this->requestWithDemand(['searchword' => 'Attraction 07']);

        self::assertCount(1, $unfiltered, 'Unfiltered list stored.');
        self::assertCount(2, $this->listIdentifiers(), 'A filtered list is its own entry.');
    }

    /**
     * Driven through a real request, so the canonical form has to survive
     * Extbase argument mapping and not only direct construction.
     *
     * The fixture has no towns or categories, so equivalence is shown with an
     * empty versus absent filter; value ordering is covered at unit level.
     */
    #[Test]
    public function equivalentDemandsShareOneEntry(): void
    {
        $this->requestWithDemand(['searchword' => 'Attraction 07', 'towns' => []]);
        $afterFirst = $this->listIdentifiers();
        self::assertCount(1, $afterFirst, 'First demand stored.');

        $this->requestWithDemand(['searchword' => 'Attraction 07']);

        self::assertSame(
            $afterFirst,
            $this->listIdentifiers(),
            'An empty filter and an absent one are the same demand.'
        );
    }

    /**
     * A list matching nothing has no record to tag, so it falls back to the
     * table tag.
     */
    #[Test]
    public function aListMatchingNothingIsTaggedWithItsTable(): void
    {
        $this->requestWithDemand(['searchword' => 'Nichts trifft zu']);

        $entries = $this->cacheIdentifiersTaggedWith(
            Extension::CACHE_LIST,
            'tx_thuecat_tourist_attraction'
        );

        self::assertCount(1, $entries, 'The empty result is stored and tagged.');
    }

    private function requestPage(?int $currentPage = null): ResponseInterface
    {
        $arguments = $currentPage === null ? [] : ['currentPage' => (string)$currentPage];

        return $this->request(10, $arguments);
    }

    /**
     * @param array<string, mixed> $demand
     */
    private function requestWithDemand(array $demand): ResponseInterface
    {
        return $this->request(10, ['demand' => $demand]);
    }

    /**
     * @return array<string, string>
     */
    private function storedLists(): array
    {
        return $this->storedContent(Extension::CACHE_LIST, $this->listIdentifiers());
    }

    /**
     * @return list<string>
     */
    private function listIdentifiers(): array
    {
        return $this->identifiersForRecords(Extension::CACHE_LIST, range(1, 25));
    }
}
