<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional\Caching;

use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use WerkraumMedia\ThueCat\Extension;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractCachingTestCase;

/**
 * The rendered filter form is stored per search plugin, page, demand and
 * language, without the pagination page.
 *
 * The fixture pairs search plugins with different siblings: page 10 has a
 * filtered list (towns locked), page 20 a plain list, page 30 no list at all.
 */
class SearchMaskCacheTest extends AbstractCachingTestCase
{
    protected function getDataSetFileName(): string
    {
        return 'TouristAttractionsForResolver.php';
    }

    protected function getRenderingTypoScript(): string
    {
        return 'ResolverRendering.typoscript';
    }

    #[Test]
    public function aRenderedMaskIsStored(): void
    {
        $cold = $this->maskIdentifiers();

        $this->requestMask(20);

        self::assertSame([], $cold, 'Cache starts cold.');
        self::assertCount(1, $this->maskIdentifiers(), 'The mask leaves one entry.');
    }

    /** The mask is identical across the pagination pages of one demand. */
    #[Test]
    public function pagingReusesOneStoredMask(): void
    {
        $this->requestPaged(20, 1);
        $afterFirstPage = $this->storedMasks();
        self::assertCount(1, $afterFirstPage, 'Page 1 stored its mask.');

        $this->requestPaged(20, 2);

        self::assertSame(
            $afterFirstPage,
            $this->storedMasks(),
            'Paging must not build a second mask.'
        );
    }

    /**
     * Page 10 locks the town filter, page 20 offers it. Keying on the page
     * keeps them apart without resolving the sibling first.
     */
    #[Test]
    public function differentSiblingListsAreDifferentMasks(): void
    {
        $this->requestMask(20);
        $afterPlainList = $this->maskIdentifiers();

        $this->requestMask(10);

        self::assertCount(1, $afterPlainList, 'Plain-list page stored its mask.');
        self::assertCount(
            2,
            $this->maskIdentifiers(),
            'The filtered-list page renders its own mask.'
        );
    }

    #[Test]
    public function aDifferentSelectionIsItsOwnMask(): void
    {
        $this->requestMask(20);
        $unselected = $this->maskIdentifiers();

        $this->requestMask(20, ['demand' => ['searchword' => 'Museum']]);

        self::assertCount(1, $unselected, 'Mask without selection stored.');
        self::assertCount(
            2,
            $this->maskIdentifiers(),
            'The mask reflects the selection, so it is its own entry.'
        );
    }

    /**
     * Page 51 is the translation of page 50, so both are requested as page 50
     * and told apart by the language alone.
     */
    #[Test]
    public function aDifferentLanguageIsItsOwnMask(): void
    {
        $this->requestMask(50);
        $german = $this->allMaskIdentifiers();

        $this->requestMask(50, [], 1);

        self::assertCount(1, $german, 'German mask stored.');
        self::assertCount(2, $this->allMaskIdentifiers(), 'English renders its own mask.');
    }

    /**
     * A language whose options are untranslated offers none. Such a mask must
     * still be invalidated, which per-record tags cannot do.
     */
    #[Test]
    public function aMaskWithoutOptionsFallsBackToTheTableTag(): void
    {
        $this->requestMask(20, [], 2);

        self::assertCount(1, $this->allMaskIdentifiers(), 'The mask is stored.');
        self::assertCount(
            1,
            $this->cacheIdentifiersTaggedWith(Extension::CACHE_SEARCH_MASK, 'tx_thuecat_town'),
            'With no option to tag, the table itself is the tag.'
        );
    }

    /** Option lists are DB-backed, so a renamed town must not survive. */
    #[Test]
    public function renamingATownDiscardsStoredMasks(): void
    {
        $this->setUpBackendUserForDataHandler();
        $this->requestMask(20);
        self::assertCount(1, $this->allMaskIdentifiers(), 'Mask stored.');

        $this->saveRecord(1, ['title' => 'Erfurt, neu benannt'], 'tx_thuecat_town');

        self::assertSame([], $this->allMaskIdentifiers(), 'The stored mask is discarded.');
    }

    #[Test]
    public function theNextRenderShowsTheNewTownName(): void
    {
        $this->setUpBackendUserForDataHandler();
        $this->requestMask(20);

        $this->saveRecord(1, ['title' => 'Erfurt, neu benannt'], 'tx_thuecat_town');
        $body = (string)$this->requestMask(20)->getBody();

        self::assertStringContainsString('Erfurt, neu benannt', $body);
    }

    /** Paging travels in the list namespace; the search action has no such argument. */
    private function requestPaged(int $pageId, int $currentPage): ResponseInterface
    {
        return $this->request($pageId, ['currentPage' => (string)$currentPage]);
    }

    /**
     * @param array<string, mixed> $arguments
     */
    private function requestMask(int $pageId, array $arguments = [], int $languageId = 0): ResponseInterface
    {
        return $this->request(
            $pageId,
            $arguments,
            'tx_thuecat_touristattractionsearch',
            $languageId
        );
    }

    /**
     * @return array<string, string>
     */
    private function storedMasks(): array
    {
        return $this->storedContent(Extension::CACHE_SEARCH_MASK, $this->maskIdentifiers());
    }

    /**
     * Masks tagged with the town table, which every page here offers.
     *
     * @return list<string>
     */
    private function maskIdentifiers(): array
    {
        return $this->cacheIdentifiersTaggedWith(
            Extension::CACHE_SEARCH_MASK,
            'tx_thuecat_town'
        );
    }

    /**
     * Every stored mask, read from the table rather than by tag: a mask with
     * empty option lists carries no tag to look up.
     *
     * @return list<string>
     */
    private function allMaskIdentifiers(): array
    {
        $rows = $this->get(ConnectionPool::class)
            ->getConnectionForTable('cache_tx_thuecat_searchmask')
            ->select(['identifier'], 'cache_tx_thuecat_searchmask')
            ->fetchFirstColumn()
        ;

        $identifiers = [];
        foreach ($rows as $identifier) {
            if (is_string($identifier)) {
                $identifiers[] = $identifier;
            }
        }
        sort($identifiers);

        return $identifiers;
    }
}
