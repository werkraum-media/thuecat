<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Unit\Frontend\Cache;

/*
 * Copyright (C) 2026 werkraum-media
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301, USA.
 */

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Dto\TouristAttractionDemand;
use WerkraumMedia\ThueCat\Frontend\Cache\CacheIdentifierFactory;

/**
 * Two requests rendering the same HTML must produce the same identifier, and
 * two that would not must not. A non-canonical form fails quietly, costing hit
 * rate rather than serving anything wrong, so it is pinned here.
 */
class CacheIdentifierFactoryTest extends TestCase
{
    private CacheIdentifierFactory $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new CacheIdentifierFactory();
    }

    private function demand(): TouristAttractionDemand
    {
        return new TouristAttractionDemand();
    }

    #[Test]
    public function equalListRequestsShareAnIdentifier(): void
    {
        $one = $this->demand();
        $one->setTowns([1, 2]);

        $other = $this->demand();
        $other->setTowns([1, 2]);

        self::assertSame(
            $this->subject->forList(12, $one, 1, 0),
            $this->subject->forList(12, $other, 1, 0)
        );
    }

    /**
     * Each input must reach the identifier; a forgotten one has two different
     * requests sharing an entry.
     */
    #[Test]
    public function everyListInputChangesTheIdentifier(): void
    {
        $demand = $this->demand();
        $demand->setTowns([1]);
        $otherDemand = $this->demand();
        $otherDemand->setTowns([2]);

        $base = $this->subject->forList(12, $demand, 1, 0);

        self::assertNotSame($base, $this->subject->forList(13, $demand, 1, 0), 'Plugin uid.');
        self::assertNotSame($base, $this->subject->forList(12, $otherDemand, 1, 0), 'Demand.');
        self::assertNotSame($base, $this->subject->forList(12, $demand, 2, 0), 'Pagination page.');
        self::assertNotSame($base, $this->subject->forList(12, $demand, 1, 1), 'Language.');
    }

    #[Test]
    public function everySearchMaskInputChangesTheIdentifier(): void
    {
        $demand = $this->demand();
        $demand->setTowns([1]);
        $otherDemand = $this->demand();
        $otherDemand->setTowns([2]);

        $base = $this->subject->forSearchMask(12, 10, $demand, 0);

        self::assertNotSame($base, $this->subject->forSearchMask(13, 10, $demand, 0), 'Plugin uid.');
        self::assertNotSame($base, $this->subject->forSearchMask(12, 11, $demand, 0), 'Page uid.');
        self::assertNotSame($base, $this->subject->forSearchMask(12, 10, $otherDemand, 0), 'Demand.');
        self::assertNotSame($base, $this->subject->forSearchMask(12, 10, $demand, 1), 'Language.');
    }

    /**
     * A teaser depends on the record, its detail page and the language, never
     * on the plugin, demand or page — which is what lets lists share entries.
     */
    #[Test]
    public function everyTeaserInputChangesTheIdentifier(): void
    {
        $base = $this->subject->forTeaser('tx_thuecat_tourist_attraction', 100, 20, 0);

        self::assertNotSame($base, $this->subject->forTeaser('tx_thuecat_trail', 100, 20, 0), 'Table.');
        self::assertNotSame($base, $this->subject->forTeaser('tx_thuecat_tourist_attraction', 101, 20, 0), 'Record uid.');
        self::assertNotSame($base, $this->subject->forTeaser('tx_thuecat_tourist_attraction', 100, 21, 0), 'Detail page uid.');
        self::assertNotSame($base, $this->subject->forTeaser('tx_thuecat_tourist_attraction', 100, 20, 1), 'Language.');
    }

    /** Uids are unique only within a table. */
    #[Test]
    public function recordsOfDifferentTypesSharingAUidDoNotCollide(): void
    {
        self::assertNotSame(
            $this->subject->forTeaser('tx_thuecat_tourist_attraction', 12, 20, 0),
            $this->subject->forTeaser('tx_thuecat_trail', 12, 20, 0)
        );
    }

    /**
     * List and mask keys are built from overlapping inputs, so they must not
     * collide even when every shared value agrees.
     */
    #[Test]
    public function differentCachesDoNotCollide(): void
    {
        $demand = $this->demand();

        self::assertNotSame(
            $this->subject->forList(12, $demand, 1, 0),
            $this->subject->forSearchMask(12, 1, $demand, 0)
        );
    }

    /**
     * Extbase builds the demand from query parameters, whose order is the
     * visitor's URL rather than anything canonical.
     */
    #[Test]
    public function listValueOrderDoesNotMatter(): void
    {
        $ascending = $this->demand();
        $ascending->setTowns([1, 2, 3]);
        $ascending->setCategories([7, 8]);

        $shuffled = $this->demand();
        $shuffled->setTowns([3, 1, 2]);
        $shuffled->setCategories([8, 7]);

        self::assertSame(
            $this->subject->forList(12, $ascending, 1, 0),
            $this->subject->forList(12, $shuffled, 1, 0)
        );
    }

    /**
     * A filter never set and a filter set to nothing select the same records.
     */
    #[Test]
    public function anEmptyFilterMatchesAnAbsentOne(): void
    {
        $absent = $this->demand();
        $absent->setTowns([1]);

        $empty = $this->demand();
        $empty->setTowns([1]);
        $empty->setCategories([]);
        $empty->setSearchword('');
        $empty->setPetsAllowed(false);

        self::assertSame(
            $this->subject->forList(12, $absent, 1, 0),
            $this->subject->forList(12, $empty, 1, 0)
        );
    }

    /**
     * Query parameters arrive as strings; the same uids typed differently
     * still select the same records.
     */
    #[Test]
    public function equalValuesOfDifferentTypesMatch(): void
    {
        $integers = $this->demand();
        $integers->setTowns([1, 2]);

        $strings = $this->demand();
        /** @phpstan-ignore argument.type (Extbase hands over strings from the URL.) */
        $strings->setTowns(['1', '2']);

        self::assertSame(
            $this->subject->forList(12, $integers, 1, 0),
            $this->subject->forList(12, $strings, 1, 0)
        );
    }

    /**
     * A duplicate uid does not change which records are selected.
     */
    #[Test]
    public function repeatedValuesCollapse(): void
    {
        $once = $this->demand();
        $once->setTowns([1, 2]);

        $twice = $this->demand();
        $twice->setTowns([1, 2, 2, 1]);

        self::assertSame(
            $this->subject->forList(12, $once, 1, 0),
            $this->subject->forList(12, $twice, 1, 0)
        );
    }

    /**
     * The filters a demand carries are not fixed — more become editor-selectable
     * as JSON blobs turn into relations, and `towns`/`categories` are only
     * today's two. A factory naming the properties it knows would keep working
     * and silently stop distinguishing the new one, serving one list for two
     * filter states. So the canonical form must be derived from whatever the
     * demand holds.
     */
    #[Test]
    public function aFilterTheFactoryNeverHeardOfStillChangesTheIdentifier(): void
    {
        $unset = new class() extends TouristAttractionDemand {
            protected string $futureFilter = '';

            public function setFutureFilter(string $value): void
            {
                $this->futureFilter = $value;
            }
        };

        $set = clone $unset;
        $set->setFutureFilter('someValue');

        self::assertNotSame(
            $this->subject->forList(12, $unset, 1, 0),
            $this->subject->forList(12, $set, 1, 0),
            'A filter added later must reach the identifier without touching the factory.'
        );
    }

    /**
     * A value must stay attached to the filter it belongs to. Two demands
     * selecting different records collapse onto one entry if the filters are
     * flattened into a single joined string — towns [1,2]+categories [3] and
     * towns [1]+categories [2,3] both read as "1,2,3". The one failure here
     * that serves a wrong list rather than merely missing a hit.
     */
    #[Test]
    public function valuesDoNotBleedBetweenFilters(): void
    {
        $one = $this->demand();
        $one->setTowns([1, 2]);
        $one->setCategories([3]);

        $other = $this->demand();
        $other->setTowns([1]);
        $other->setCategories([2, 3]);

        self::assertNotSame(
            $this->subject->forList(12, $one, 1, 0),
            $this->subject->forList(12, $other, 1, 0)
        );
    }

    /**
     * The same bleed, across filters of different types: a search word must not
     * merge into a neighbouring list filter.
     */
    #[Test]
    public function valuesDoNotBleedBetweenFiltersOfDifferentTypes(): void
    {
        $one = $this->demand();
        $one->setSearchword('1');
        $one->setTowns([2]);

        $other = $this->demand();
        $other->setSearchword('12');

        self::assertNotSame(
            $this->subject->forList(12, $one, 1, 0),
            $this->subject->forList(12, $other, 1, 0)
        );
    }

    /**
     * An identifier over 250 characters does not degrade to a cache miss —
     * `AbstractFrontend::set()` throws `InvalidArgumentException`, i.e. a 500 on
     * the frontend. Length is bounded structurally rather than by luck: every
     * unbounded input is hashed to a fixed width, leaving only integers and one
     * table name. This pins that, so adding an unhashed variable-length part to
     * a key fails here instead of in production.
     */
    #[Test]
    public function identifiersStayWithinTheLengthLimitForExtremeInput(): void
    {
        $demand = $this->demand();
        $demand->setSearchword(str_repeat('Wandern und Radfahren im Thüringer Wald ', 50));
        $demand->setTowns(range(1, 5000));
        $demand->setCategories(range(1, 5000));

        $identifiers = [
            'list' => $this->subject->forList(PHP_INT_MAX, $demand, PHP_INT_MAX, 999),
            'search mask' => $this->subject->forSearchMask(PHP_INT_MAX, PHP_INT_MAX, $demand, 999),
            'teaser' => $this->subject->forTeaser(
                'tx_thuecat_some_very_long_record_table_name',
                PHP_INT_MAX,
                PHP_INT_MAX,
                999
            ),
        ];

        foreach ($identifiers as $name => $identifier) {
            self::assertLessThanOrEqual(
                250,
                strlen($identifier),
                $name . ' identifier must never exceed the cache framework limit.'
            );
        }
    }

    /**
     * The raw values cannot go into a cache key: a search word carries spaces
     * and punctuation, and a filter carries arrays. Only the hash can.
     */
    #[Test]
    public function identifiersAreUsableAsCacheEntryIdentifiers(): void
    {
        $demand = $this->demand();
        $demand->setSearchword('Wandern & Radfahren im Thüringer Wald / Rennsteig, "Sommer" 2026!');
        $demand->setTowns(range(1, 200));
        $demand->setCategories(range(500, 700));
        $demand->setPetsAllowed(true);

        $identifiers = [
            'list' => $this->subject->forList(12, $demand, 3, 0),
            'search mask' => $this->subject->forSearchMask(12, 10, $demand, 0),
            'teaser' => $this->subject->forTeaser('tx_thuecat_tourist_attraction', 100, 20, 0),
        ];

        foreach ($identifiers as $name => $identifier) {
            self::assertMatchesRegularExpression(
                FrontendInterface::PATTERN_ENTRYIDENTIFIER,
                $identifier,
                $name . ' identifier must be storable.'
            );
        }
    }
}
