<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional\Caching;

use PHPUnit\Framework\Attributes\Test;
use WerkraumMedia\ThueCat\Extension;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractCachingTestCase;

/**
 * Trails take part in the teaser cache on the same terms as any other record
 * type, and their entries stay distinct from another table's.
 *
 * Assertions are on the cache, not the body: a body looks correct whenever the
 * cache is cold, so it cannot tell a reused entry from a fresh one.
 */
class TrailTeaserCacheTest extends AbstractCachingTestCase
{
    protected function getDataSetFileName(): string
    {
        return 'TrailsForCaching.php';
    }

    #[Test]
    public function aRenderedTeaserIsStored(): void
    {
        $cold = $this->trailTeaserIdentifiers();

        $this->request(10);

        self::assertSame([], $cold, 'Cache starts cold.');
        self::assertCount(
            2,
            $this->trailTeaserIdentifiers(),
            'Both trails of the list must leave a teaser entry.'
        );
        $this->assertCachedEntriesHaveContent(Extension::CACHE_TEASER, 'tx_thuecat_trail_10');
    }

    #[Test]
    public function aStoredTeaserHoldsItsRecordsMarkup(): void
    {
        $this->request(10);

        $identifiers = $this->cacheIdentifiersTaggedWith(Extension::CACHE_TEASER, 'tx_thuecat_trail_10');
        self::assertCount(1, $identifiers, 'Trail 10 must leave exactly one entry.');

        self::assertStringContainsString(
            'Goethe-Erlebnisweg',
            $this->cachedContent(Extension::CACHE_TEASER, $identifiers[0])
        );
    }

    #[Test]
    public function aSecondRequestReusesTheStoredTeasers(): void
    {
        $this->request(10);
        $stored = $this->storedContent(Extension::CACHE_TEASER, $this->trailTeaserIdentifiers());

        $this->request(10);

        self::assertSame(
            $stored,
            $this->storedContent(Extension::CACHE_TEASER, $this->trailTeaserIdentifiers()),
            'A second request must serve the stored entries rather than replace them.'
        );
    }

    /**
     * Uids are unique only within a table, so the table has to be part of the
     * teaser identity. Both lists link the same detail page, which removes the
     * other component that would otherwise tell the two entries apart.
     */
    #[Test]
    public function aTrailAndAnAttractionSharingAUidGetSeparateEntries(): void
    {
        $this->request(10);
        $this->request(13, [], 'tx_thuecat_touristattractionlistselected');

        $trail = $this->cacheIdentifiersTaggedWith(Extension::CACHE_TEASER, 'tx_thuecat_trail_10');
        $attraction = $this->cacheIdentifiersTaggedWith(
            Extension::CACHE_TEASER,
            'tx_thuecat_tourist_attraction_10'
        );

        self::assertCount(1, $trail);
        self::assertCount(1, $attraction);
        self::assertNotSame($trail[0], $attraction[0], 'One uid, two tables, two entries.');

        self::assertStringContainsString(
            'Goethe-Erlebnisweg',
            $this->cachedContent(Extension::CACHE_TEASER, $trail[0])
        );
        self::assertStringContainsString(
            'Stadtmuseum Erfurt',
            $this->cachedContent(Extension::CACHE_TEASER, $attraction[0])
        );
    }

    /**
     * @return list<string>
     */
    private function trailTeaserIdentifiers(): array
    {
        return $this->identifiersForRecords(
            Extension::CACHE_TEASER,
            [10, 11],
            'tx_thuecat_trail'
        );
    }
}
