<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional\Caching;

use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractCachingTestCase;

/**
 * The list plugin is cacheable while `currentPage` is excluded from the cHash,
 * so pagination pages share one page-cache identifier. Asserting on the body
 * cannot see this: every other test starts from a cold cache, where the wrong
 * entry does not exist yet.
 */
class PageCacheTest extends AbstractCachingTestCase
{
    protected function getDataSetFileName(): string
    {
        return 'TouristAttractionsForPagination.php';
    }

    protected function getRenderingTypoScript(): string
    {
        return 'PaginationRendering.typoscript';
    }

    /**
     * Guard for the tests below: if this fails, the harness cannot observe page
     * caching at all and a green result elsewhere proves nothing.
     */
    #[Test]
    public function theHarnessObservesPageCacheEntries(): void
    {
        $cold = $this->pageCacheIdentifiers();
        $this->requestPage();
        $warm = $this->pageCacheIdentifiers();

        self::assertSame([], $cold, 'Cache starts cold.');
        self::assertCount(
            1,
            $warm,
            'A list request must leave exactly one page-cache entry.'
        );
    }

    #[Test]
    public function repeatingOneRequestReusesItsEntry(): void
    {
        $this->requestPage();
        $afterFirst = $this->pageCacheIdentifiers();

        $this->requestPage();

        self::assertSame(
            $afterFirst,
            $this->pageCacheIdentifiers(),
            'The same request must not create a second entry.'
        );
    }

    /**
     * The plugin is USER_INT, so its output never enters the page cache. The
     * page shell still caches — one entry for the page, shared by both requests,
     * with the list rendered per request inside it.
     */
    #[Test]
    public function paginationPagesShareThePageShellButNotTheirContent(): void
    {
        $this->requestPage();
        $afterFirst = $this->pageCacheIdentifiers();

        $this->requestPage(2);

        self::assertSame(
            $afterFirst,
            $this->pageCacheIdentifiers(),
            'Paging must not add page-cache entries; the list is rendered per request.'
        );
    }

    #[Test]
    public function pageTwoIsServedItsOwnContentWhenPageOneIsCached(): void
    {
        $this->requestPage();

        $body = (string)$this->requestPage(2)->getBody();

        self::assertStringContainsString('Attraction 11', $body, 'Page 2 content.');
        self::assertStringNotContainsString('Attraction 01<', $body, 'Page 1 content leaked.');
    }

    private function requestPage(?int $currentPage = null): ResponseInterface
    {
        $arguments = $currentPage === null ? [] : ['currentPage' => (string)$currentPage];

        return $this->request(10, $arguments);
    }

    /**
     * @return list<string>
     */
    private function pageCacheIdentifiers(): array
    {
        return $this->cacheIdentifiersTaggedWith('pages', 'pageId_10');
    }
}
