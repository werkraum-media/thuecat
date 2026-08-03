<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional\Caching;

use PHPUnit\Framework\Attributes\Test;
use WerkraumMedia\ThueCat\Extension;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractCachingTestCase;

/**
 * A teaser is rendered once and reused by every list showing it.
 *
 * Assertions are on the cache, not the body: a body looks correct whenever the
 * cache is cold, so it cannot tell a reused entry from a fresh one.
 *
 * Two list plugins over one storage differ only in the detail page they link
 * (page 10 → detail 20, page 13 → detail 21), both showing attractions 10, 11.
 */
class TeaserCacheTest extends AbstractCachingTestCase
{
    protected function getDataSetFileName(): string
    {
        return 'TouristAttractionsForCaching.php';
    }

    /**
     * Guard for the tests below: the detail page comes from the plugin's own
     * flexform, which Extbase parses without consulting a data structure. If
     * that stops holding, both lists key alike for an unrelated reason.
     */
    #[Test]
    public function eachListLinksItsOwnDetailPage(): void
    {
        $first = (string)$this->request(10)->getBody();
        $second = (string)$this->request(13)->getBody();

        self::assertStringContainsString('/detail/', $first, 'List on page 10 links detail page 20.');
        self::assertStringContainsString('/other-detail/', $second, 'List on page 13 links detail page 21.');
    }

    #[Test]
    public function aRenderedTeaserIsStored(): void
    {
        $cold = $this->teaserIdentifiers();

        $this->request(10);

        self::assertSame([], $cold, 'Cache starts cold.');
        self::assertCount(
            2,
            $this->teaserIdentifiers(),
            'Both attractions of the list must leave a teaser entry.'
        );
        $this->assertCachedEntriesHaveContent(
            Extension::CACHE_TEASER,
            'tx_thuecat_tourist_attraction_10'
        );
    }

    /** An entry holds the record's own markup, not merely something. */
    #[Test]
    public function aStoredTeaserHoldsItsRecordsMarkup(): void
    {
        $this->request(10);

        $stored = [];
        foreach ($this->teaserIdentifiers() as $identifier) {
            $stored[] = $this->cachedContent(Extension::CACHE_TEASER, $identifier);
        }
        $combined = implode("\n", $stored);

        self::assertStringContainsString('Stadtmuseum Erfurt', $combined);
        self::assertStringContainsString('Domberg Erfurt', $combined);
        self::assertStringContainsString('/detail/', $combined, 'Teaser links its detail page.');
    }

    /** What the visitor sees is what the cache holds. */
    #[Test]
    public function theStoredTeaserIsWhatTheListServes(): void
    {
        $body = (string)$this->request(10)->getBody();

        foreach ($this->teaserIdentifiers() as $identifier) {
            $stored = trim($this->cachedContent(Extension::CACHE_TEASER, $identifier));
            self::assertNotSame('', $stored, 'Entry holds nothing but whitespace.');
            self::assertStringContainsString(
                $stored,
                $body,
                'The rendered list must contain the stored teaser verbatim.'
            );
        }
    }

    /** An attraction rendered elsewhere costs nothing to show again. */
    #[Test]
    public function aSecondListReusesTheStoredTeasers(): void
    {
        $this->request(10);
        $afterFirstList = $this->teaserIdentifiers();
        self::assertCount(2, $afterFirstList, 'Nothing stored means nothing to reuse.');
        $before = $this->storedTeasers();

        $this->request(10);

        self::assertSame(
            $afterFirstList,
            $this->teaserIdentifiers(),
            'Repeating a list must not write further teaser entries.'
        );
        self::assertSame(
            $before,
            $this->storedTeasers(),
            'The stored markup must be served, not rebuilt.'
        );
    }

    /**
     * The same attraction linked from two lists with different detail pages
     * renders different HTML, so it is a different entry.
     */
    #[Test]
    public function aDifferentDetailPageIsADifferentTeaser(): void
    {
        $this->request(10);
        $afterFirstList = $this->teaserIdentifiers();

        $this->request(13);
        $afterSecondList = $this->teaserIdentifiers();

        self::assertCount(2, $afterFirstList, 'First list stores its two teasers.');
        self::assertCount(
            4,
            $afterSecondList,
            'The second list links another detail page, so its teasers are their own entries.'
        );

        // The point of keying on the detail page: the two entries for one
        // attraction differ, and each links its own page.
        $links = [];
        foreach ($afterSecondList as $identifier) {
            $links[] = $this->cachedContent(Extension::CACHE_TEASER, $identifier);
        }
        $combined = implode("\n", $links);

        self::assertStringContainsString('/detail/', $combined, 'Teasers of the first list.');
        self::assertStringContainsString('/other-detail/', $combined, 'Teasers of the second list.');
    }

    /**
     * An unseen list costs only the teasers it adds: page 14 shows attractions
     * 10, 11 and 12 and links the same detail page as page 10.
     */
    #[Test]
    public function aListReusesWhatIsStoredAndRendersOnlyTheRest(): void
    {
        $this->request(10);
        $existing = $this->storedTeasers();
        self::assertCount(2, $existing, 'Attractions 10 and 11 are stored.');

        $this->request(14);
        $afterWiderList = $this->storedTeasers();

        self::assertCount(3, $afterWiderList, 'Only the third attraction is added.');
        foreach ($existing as $identifier => $markup) {
            self::assertArrayHasKey($identifier, $afterWiderList, 'Stored entry survives.');
            self::assertSame(
                $markup,
                $afterWiderList[$identifier],
                'A stored teaser is reused, not rendered again.'
            );
        }
    }

    /**
     * The key names no plugin, so a curated and a filtered list showing the
     * same record with the same detail page share the entry.
     */
    #[Test]
    public function aCuratedListSharesTeasersWithAFilteredOne(): void
    {
        $this->request(10);
        $fromFilteredList = $this->storedTeasers();
        self::assertCount(2, $fromFilteredList, 'Attractions 10 and 11 are stored.');

        $this->request(15);

        self::assertSame(
            $fromFilteredList,
            $this->storedTeasers(),
            'The curated list must reuse the stored entries, adding none.'
        );
    }

    /**
     * @return array<string, string>
     */
    private function storedTeasers(): array
    {
        return $this->storedContent(Extension::CACHE_TEASER, $this->teaserIdentifiers());
    }

    /**
     * @return list<string>
     */
    private function teaserIdentifiers(): array
    {
        return $this->identifiersForRecords(Extension::CACHE_TEASER, [10, 11, 12]);
    }
}
