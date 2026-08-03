<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional\Caching;

use PHPUnit\Framework\Attributes\Test;
use WerkraumMedia\ThueCat\Extension;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractCachingTestCase;

/**
 * Saving a record discards what displayed it, through DataHandler's own tags
 * and the `pages` cache group. No invalidation code of ours is involved.
 */
class TeaserInvalidationTest extends AbstractCachingTestCase
{
    protected function getDataSetFileName(): string
    {
        return 'TouristAttractionsForCaching.php';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpBackendUserForDataHandler();
    }

    #[Test]
    public function savingARecordDiscardsItsOwnTeaserOnly(): void
    {
        $this->request(10);
        $before = $this->teaserIdentifiersByAttraction();
        self::assertCount(2, $before, 'Both teasers must be stored before saving.');

        $this->saveRecord(10, ['title' => 'Stadtmuseum Erfurt, neu benannt']);

        $after = $this->teaserIdentifiersByAttraction();
        self::assertArrayNotHasKey(10, $after, 'The saved record keeps no stale teaser.');
        self::assertArrayHasKey(11, $after, 'An untouched record keeps its teaser.');
        self::assertSame(
            $before[11],
            $after[11],
            'The untouched entry is the same one, not a rebuilt replacement.'
        );
    }

    /**
     * The point of discarding: the next request shows the new title. Without
     * this, a test could pass on an entry that was dropped and never replaced.
     */
    #[Test]
    public function theNextRequestRendersTheSavedValue(): void
    {
        $this->request(10);
        $this->saveRecord(10, ['title' => 'Stadtmuseum Erfurt, neu benannt']);

        $body = (string)$this->request(10)->getBody();

        self::assertStringContainsString('Stadtmuseum Erfurt, neu benannt', $body);
        self::assertStringNotContainsString(
            '>Stadtmuseum Erfurt<',
            $body,
            'The superseded title must not survive in a cached teaser.'
        );
    }

    /**
     * Teasers of one record differing only by detail page are separate entries,
     * and a save must take all of them — otherwise one list updates and another
     * keeps the old markup.
     */
    #[Test]
    public function savingARecordDiscardsEveryTeaserOfThatRecord(): void
    {
        $this->request(10);
        $this->request(13);
        self::assertCount(4, $this->teaserIdentifiers(), 'Two lists, two detail pages.');

        $this->saveRecord(10, ['title' => 'Stadtmuseum Erfurt, neu benannt']);

        $remaining = $this->teaserIdentifiersByAttraction();
        self::assertArrayNotHasKey(10, $remaining, 'No teaser of the saved record survives.');
        self::assertCount(2, $remaining[11], 'Both teasers of the untouched record survive.');
    }

    /**
     * Stored teaser identifiers per attraction. Grouping is what makes "the
     * right one was discarded" assertable; a count alone would not.
     *
     * @return array<int, list<string>>
     */
    private function teaserIdentifiersByAttraction(): array
    {
        $grouped = [];
        foreach ([10, 11, 12] as $uid) {
            $identifiers = $this->teaserIdentifiersFor($uid);
            if ($identifiers !== []) {
                $grouped[$uid] = $identifiers;
            }
        }

        return $grouped;
    }

    /**
     * @return list<string>
     */
    private function teaserIdentifiersFor(int $attractionUid): array
    {
        return $this->identifiersForRecords(Extension::CACHE_TEASER, [$attractionUid]);
    }

    /**
     * @return list<string>
     */
    private function teaserIdentifiers(): array
    {
        $grouped = $this->teaserIdentifiersByAttraction();

        return $grouped === [] ? [] : array_merge(...array_values($grouped));
    }
}
