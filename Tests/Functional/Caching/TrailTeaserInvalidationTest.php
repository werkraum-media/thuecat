<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional\Caching;

use PHPUnit\Framework\Attributes\Test;
use WerkraumMedia\ThueCat\Extension;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractCachingTestCase;

/**
 * Saving a trail discards that trail's teaser entries and nothing else.
 *
 * The per-uid tag is what makes this narrow: tagging with the bare table name
 * would discard every teaser of the type on any save.
 */
class TrailTeaserInvalidationTest extends AbstractCachingTestCase
{
    protected function getDataSetFileName(): string
    {
        return 'TrailsForCaching.php';
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
        $before = $this->teaserIdentifiersByTrail();
        self::assertCount(2, $before, 'Both teasers must be stored before saving.');

        $this->saveRecord(10, ['title' => 'Goethe-Erlebnisweg, neu benannt'], 'tx_thuecat_trail');

        $after = $this->teaserIdentifiersByTrail();
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
        $this->saveRecord(10, ['title' => 'Goethe-Erlebnisweg, neu benannt'], 'tx_thuecat_trail');

        $body = (string)$this->request(10)->getBody();

        self::assertStringContainsString('Goethe-Erlebnisweg, neu benannt', $body);
    }

    /** Saving one table must leave another table's entries alone. */
    #[Test]
    public function savingATrailKeepsAnAttractionSharingItsUid(): void
    {
        $this->request(10);
        $this->request(13, [], 'tx_thuecat_touristattractionlistselected');

        $before = $this->cacheIdentifiersTaggedWith(
            Extension::CACHE_TEASER,
            'tx_thuecat_tourist_attraction_10'
        );
        self::assertCount(1, $before, 'The attraction teaser must be stored before saving.');

        $this->saveRecord(10, ['title' => 'Goethe-Erlebnisweg, neu benannt'], 'tx_thuecat_trail');

        self::assertSame(
            $before,
            $this->cacheIdentifiersTaggedWith(
                Extension::CACHE_TEASER,
                'tx_thuecat_tourist_attraction_10'
            ),
            'Saving a trail must not touch an attraction of the same uid.'
        );
    }

    /**
     * @return array<int, list<string>>
     */
    private function teaserIdentifiersByTrail(): array
    {
        $byUid = [];
        foreach ([10, 11] as $uid) {
            $identifiers = $this->cacheIdentifiersTaggedWith(
                Extension::CACHE_TEASER,
                'tx_thuecat_trail_' . $uid
            );
            if ($identifiers !== []) {
                $byUid[$uid] = $identifiers;
            }
        }

        return $byUid;
    }
}
