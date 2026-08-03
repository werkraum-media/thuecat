<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional\Caching;

use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use WerkraumMedia\ThueCat\Extension;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractCachingTestCase;

/**
 * A stored list is discarded when any record it displays changes, and only
 * then.
 *
 * 25 attractions at 10 per page (`PaginationRendering.typoscript`), so page 1
 * holds uids 1-10, page 2 holds 11-20 and page 3 holds 21-25.
 */
class ListInvalidationTest extends AbstractCachingTestCase
{
    protected function getDataSetFileName(): string
    {
        return 'TouristAttractionsForPagination.php';
    }

    protected function getRenderingTypoScript(): string
    {
        return 'PaginationRendering.typoscript';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpBackendUserForDataHandler();
    }

    /**
     * A record visible only on page 2 discards page 2 and leaves page 1.
     */
    #[Test]
    public function changingARecordDiscardsOnlyThePagesShowingIt(): void
    {
        $this->requestPage(1);
        $this->requestPage(2);
        self::assertCount(2, $this->listIdentifiers(), 'Both pages stored.');

        $pageOneBefore = $this->identifiersForRecords(Extension::CACHE_LIST, [1]);
        self::assertCount(1, $pageOneBefore, 'Page 1 shows attraction 1.');

        $this->saveRecord(15, ['title' => 'Attraction 15, neu benannt']);

        self::assertSame(
            $pageOneBefore,
            $this->identifiersForRecords(Extension::CACHE_LIST, [1]),
            'Page 1 does not show attraction 15, so it survives.'
        );
        self::assertSame(
            [],
            $this->identifiersForRecords(Extension::CACHE_LIST, [15]),
            'Page 2 shows attraction 15, so it is discarded.'
        );
    }

    #[Test]
    public function theNextRequestRendersTheSavedValue(): void
    {
        $this->requestPage(2);
        $this->saveRecord(15, ['title' => 'Attraction 15, neu benannt']);

        $body = (string)$this->requestPage(2)->getBody();

        self::assertStringContainsString('Attraction 15, neu benannt', $body);
    }

    /** A list matching nothing carries its table tag. */
    #[Test]
    public function anEmptyListIsDiscardedWhenTheTableChanges(): void
    {
        $this->request(10, ['demand' => ['searchword' => 'Nichts trifft zu']]);
        self::assertCount(
            1,
            $this->cacheIdentifiersTaggedWith(Extension::CACHE_LIST, 'tx_thuecat_tourist_attraction'),
            'The empty result is stored.'
        );

        $this->saveRecord(1, ['title' => 'Attraction 01, neu benannt']);

        self::assertSame(
            [],
            $this->cacheIdentifiersTaggedWith(Extension::CACHE_LIST, 'tx_thuecat_tourist_attraction'),
            'Any change to the table discards a list that matched nothing.'
        );
    }

    private function requestPage(int $currentPage): ResponseInterface
    {
        return $this->request(10, ['currentPage' => (string)$currentPage]);
    }

    /**
     * @return list<string>
     */
    private function listIdentifiers(): array
    {
        return $this->identifiersForRecords(Extension::CACHE_LIST, range(1, 25));
    }
}
