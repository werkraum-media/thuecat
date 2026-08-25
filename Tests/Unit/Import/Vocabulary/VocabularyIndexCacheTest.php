<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Unit\Import\Vocabulary;

/*
 * Copyright (C) 2026 werkraum-media
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 */

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use WerkraumMedia\ThueCat\Import\Vocabulary\VocabularyClass;
use WerkraumMedia\ThueCat\Import\Vocabulary\VocabularyIndex;
use WerkraumMedia\ThueCat\Import\Vocabulary\VocabularyIndexCache;

/**
 * Staleness is decided from a stored timestamp rather than by the backend,
 * because a caller that cannot refresh must still be able to read the entry.
 */
class VocabularyIndexCacheTest extends TestCase
{
    /**
     * A cache frontend backed by a plain array, so a test can inspect exactly
     * what was written and hand back exactly what it wants read.
     *
     * @param array<string, mixed> $entries
     */
    private function cache(array &$entries): FrontendInterface
    {
        $cache = self::createStub(FrontendInterface::class);
        // `use (&$entries)`, not an arrow function: an arrow function captures
        // by value, so `get` would keep answering from the empty array this
        // was built with and never see what `set` stored.
        $cache->method('get')->willReturnCallback(
            static function (string $id) use (&$entries): mixed {
                return $entries[$id] ?? false;
            }
        );
        $cache->method('set')->willReturnCallback(
            static function (string $id, mixed $data) use (&$entries): void {
                $entries[$id] = $data;
            }
        );

        return $cache;
    }

    private function index(): VocabularyIndex
    {
        return new VocabularyIndex([
            'schema:Museum' => new VocabularyClass(
                'schema:Museum',
                ['schema:CivicStructure'],
                ['' => 'Museum']
            ),
        ]);
    }

    #[Test]
    public function readsBackAnIndexItWrote(): void
    {
        $entries = [];
        $cache = new VocabularyIndexCache($this->cache($entries));

        $cache->write($this->index(), 1000);
        $cached = $cache->read();

        self::assertNotNull($cached);
        self::assertTrue($cached->index->has('schema:Museum'));
        self::assertSame(['schema:CivicStructure'], $cached->index->parents('schema:Museum'));
    }

    #[Test]
    public function readsBackTheLabelsItWrote(): void
    {
        $entries = [];
        $cache = new VocabularyIndexCache($this->cache($entries));

        $cache->write($this->index(), 1000);
        $cached = $cache->read();

        self::assertNotNull($cached);
        $museum = $cached->index->get('schema:Museum');
        self::assertNotNull($museum);
        self::assertSame('Museum', $museum->label(VocabularyClass::UNTAGGED));
    }

    #[Test]
    public function readsBackTheFetchTimestamp(): void
    {
        $entries = [];
        $cache = new VocabularyIndexCache($this->cache($entries));

        $cache->write($this->index(), 1234567);

        self::assertSame(1234567, $cache->read()?->fetchedAt);
    }

    #[Test]
    public function reportsNothingStoredWhenTheCacheIsEmpty(): void
    {
        $entries = [];

        self::assertNull((new VocabularyIndexCache($this->cache($entries)))->read());
    }

    #[Test]
    public function anEntryWithinTheWindowIsNotStale(): void
    {
        $entries = [];
        $cache = new VocabularyIndexCache($this->cache($entries));
        $cache->write($this->index(), 1000);

        $cached = $cache->read();

        self::assertNotNull($cached);
        self::assertFalse($cached->isStale(1000 + VocabularyIndexCache::STALE_AFTER - 1));
    }

    #[Test]
    public function anEntryPastTheWindowIsStale(): void
    {
        $entries = [];
        $cache = new VocabularyIndexCache($this->cache($entries));
        $cache->write($this->index(), 1000);

        $cached = $cache->read();

        self::assertNotNull($cached);
        self::assertTrue($cached->isStale(1000 + VocabularyIndexCache::STALE_AFTER));
    }

    #[Test]
    public function aStaleEntryIsStillReadable(): void
    {
        $entries = [];
        $cache = new VocabularyIndexCache($this->cache($entries));
        $cache->write($this->index(), 1000);

        // The whole point: a refresh that fails must still find this.
        $cached = $cache->read();

        self::assertNotNull($cached);
        self::assertTrue($cached->isStale(PHP_INT_MAX - 1));
        self::assertTrue($cached->index->has('schema:Museum'));
    }

    #[Test]
    public function rejectsAnEntryWrittenByAnOlderFormat(): void
    {
        $entries = [
            'index' => [
                'format' => VocabularyIndexCache::FORMAT - 1,
                'fetchedAt' => 1000,
                'classes' => ['schema:Museum' => ['parents' => [], 'labels' => []]],
            ],
        ];

        self::assertNull((new VocabularyIndexCache($this->cache($entries)))->read());
    }

    #[Test]
    public function rejectsAnEntryThatIsNotAnArray(): void
    {
        $entries = ['index' => 'nonsense'];

        self::assertNull((new VocabularyIndexCache($this->cache($entries)))->read());
    }

    #[Test]
    public function rejectsAnEntryMissingItsTimestamp(): void
    {
        $entries = [
            'index' => ['format' => VocabularyIndexCache::FORMAT, 'classes' => []],
        ];

        self::assertNull((new VocabularyIndexCache($this->cache($entries)))->read());
    }

    #[Test]
    public function writesTheCurrentFormatMarker(): void
    {
        $entries = [];
        $cache = new VocabularyIndexCache($this->cache($entries));

        $cache->write($this->index(), 1000);

        self::assertSame(VocabularyIndexCache::FORMAT, $this->written($entries)['format'] ?? null);
    }

    #[Test]
    public function storesTheDistilledClassesRatherThanObjects(): void
    {
        $entries = [];
        $cache = new VocabularyIndexCache($this->cache($entries));

        $cache->write($this->index(), 1000);

        // Plain arrays survive a cache round-trip across deployments; a
        // serialised object graph would not.
        $classes = $this->written($entries)['classes'] ?? null;
        self::assertIsArray($classes);

        $museum = $classes['schema:Museum'] ?? null;
        self::assertIsArray($museum);
        self::assertSame(['schema:CivicStructure'], $museum['parents'] ?? null);
    }

    /**
     * The single entry the cache writes, as a typed array.
     *
     * @param array<string, mixed> $entries
     *
     * @return array<string, mixed>
     */
    private function written(array $entries): array
    {
        $entry = $entries['index'] ?? null;
        self::assertIsArray($entry, 'Nothing was written to the cache.');

        return $entry;
    }
}
