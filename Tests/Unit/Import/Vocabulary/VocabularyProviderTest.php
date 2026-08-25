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
use WerkraumMedia\ThueCat\Import\Importer\FetchData;
use WerkraumMedia\ThueCat\Import\Importer\FetchData\InvalidResponseException;
use WerkraumMedia\ThueCat\Import\ImportLogger;
use WerkraumMedia\ThueCat\Import\Vocabulary\VocabularyIndexCache;
use WerkraumMedia\ThueCat\Import\Vocabulary\VocabularyIndexFactory;
use WerkraumMedia\ThueCat\Import\Vocabulary\VocabularyProvider;

class VocabularyProviderTest extends TestCase
{
    private const SCHEMA_ORG = ['@graph' => [
        ['@id' => 'schema:Event', '@type' => 'rdfs:Class', 'rdfs:label' => 'Event'],
        [
            '@id' => 'schema:MusicEvent',
            '@type' => 'rdfs:Class',
            'rdfs:label' => 'MusicEvent',
            'rdfs:subClassOf' => ['@id' => 'schema:Event'],
        ],
    ]];

    private const THUECAT = ['@graph' => [
        [
            '@id' => 'thuecat:BrassMusic',
            '@type' => ['rdfs:Class'],
            'rdfs:label' => [['@language' => 'de', '@value' => 'Blasmusik']],
            'rdfs:subClassOf' => ['@id' => 'schema:MusicEvent'],
        ],
    ]];

    /** @var list<array{url: string, apiKey: string|null}> */
    private array $requests = [];

    /** @var array<string, mixed> */
    private array $entries = [];

    /** @var list<array{vocabulary: string, age: int, reason: string}> */
    private array $staleWarnings = [];

    /** @var list<array{vocabulary: string, reason: string}> */
    private array $unavailableWarnings = [];

    private ImportLogger $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $logger = self::createStub(ImportLogger::class);
        $logger->method('recordStaleVocabulary')->willReturnCallback(
            function (string $vocabulary, int $age, string $reason): void {
                $this->staleWarnings[] = compact('vocabulary', 'age', 'reason');
            }
        );
        $logger->method('recordUnavailableVocabulary')->willReturnCallback(
            function (string $vocabulary, string $reason): void {
                $this->unavailableWarnings[] = compact('vocabulary', 'reason');
            }
        );
        $this->logger = $logger;
    }

    /**
     * Answers each vocabulary URL with its document, recording every call so a
     * test can assert what was requested and with which key.
     *
     * @param list<string> $failingUrls urls answered with an exception instead
     */
    private function fetchData(array $failingUrls = []): FetchData
    {
        $fetchData = self::createStub(FetchData::class);
        $fetchData->method('jsonLDFromUrl')->willReturnCallback(
            function (string $url, ?string $apiKey = null) use ($failingUrls): array {
                $this->requests[] = ['url' => $url, 'apiKey' => $apiKey];
                if (in_array($url, $failingUrls, true)) {
                    throw new InvalidResponseException('upstream is down', 1756130000);
                }

                return $url === VocabularyProvider::VOCABULARY_URLS['thuecat']
                    ? self::THUECAT
                    : self::SCHEMA_ORG;
            }
        );

        return $fetchData;
    }

    private function cache(): VocabularyIndexCache
    {
        $cache = self::createStub(FrontendInterface::class);
        $cache->method('get')->willReturnCallback(
            function (string $id): mixed {
                return $this->entries[$id] ?? false;
            }
        );
        $cache->method('set')->willReturnCallback(
            function (string $id, mixed $data): void {
                $this->entries[$id] = $data;
            }
        );

        return new VocabularyIndexCache($cache);
    }

    /**
     * @param list<string> $failingUrls
     */
    private function provider(array $failingUrls = []): VocabularyProvider
    {
        return new VocabularyProvider(
            $this->fetchData($failingUrls),
            $this->cache(),
            new VocabularyIndexFactory(),
            $this->logger
        );
    }

    #[Test]
    public function fetchesEveryVocabularyWhenNothingIsStored(): void
    {
        $this->provider()->index(null, 1000);

        self::assertSame(
            array_values(VocabularyProvider::VOCABULARY_URLS),
            array_column($this->requests, 'url')
        );
    }

    #[Test]
    public function buildsAnIndexSpanningBothVocabularies(): void
    {
        $index = $this->provider()->index(null, 1000);

        self::assertSame(
            ['schema:MusicEvent', 'schema:Event'],
            $index->ancestors('thuecat:BrassMusic')
        );
    }

    #[Test]
    public function passesTheConfiguredApiKeyToEveryFetch(): void
    {
        $this->provider()->index('secret-key', 1000);

        self::assertSame(
            ['secret-key', 'secret-key'],
            array_column($this->requests, 'apiKey')
        );
    }

    #[Test]
    public function storesWhatItFetched(): void
    {
        $this->provider()->index(null, 4242);

        $cached = $this->cache()->read();
        self::assertNotNull($cached);
        self::assertSame(4242, $cached->fetchedAt);
        self::assertTrue($cached->index->has('thuecat:BrassMusic'));
    }

    #[Test]
    public function servesAStoredIndexWithoutFetching(): void
    {
        $this->provider()->index(null, 1000);
        $this->requests = [];

        $index = $this->provider()->index(null, 1000 + VocabularyIndexCache::STALE_AFTER - 1);

        self::assertSame([], $this->requests, 'A fresh index must not be refetched.');
        self::assertTrue($index->has('thuecat:BrassMusic'));
    }

    #[Test]
    public function refetchesOnceTheStoredIndexIsStale(): void
    {
        $this->provider()->index(null, 1000);
        $this->requests = [];

        $this->provider()->index(null, 1000 + VocabularyIndexCache::STALE_AFTER);

        self::assertCount(2, $this->requests, 'A stale index must be refetched.');
    }

    #[Test]
    public function refreshingAStaleIndexUpdatesItsTimestamp(): void
    {
        $this->provider()->index(null, 1000);
        $refreshedAt = 1000 + VocabularyIndexCache::STALE_AFTER;

        $this->provider()->index(null, $refreshedAt);

        self::assertSame($refreshedAt, $this->cache()->read()?->fetchedAt);
    }

    #[Test]
    public function refetchesWhenTheStoredEntryUsesAnOlderFormat(): void
    {
        $this->entries['index'] = [
            'format' => VocabularyIndexCache::FORMAT - 1,
            'fetchedAt' => 1000,
            'classes' => ['schema:Museum' => ['parents' => [], 'labels' => []]],
        ];

        $index = $this->provider()->index(null, 1000);

        self::assertCount(2, $this->requests);
        self::assertTrue($index->has('thuecat:BrassMusic'));
    }

    #[Test]
    public function usesTheExpiredIndexWhenRefreshingFails(): void
    {
        $this->provider()->index(null, 1000);
        $staleAt = 1000 + VocabularyIndexCache::STALE_AFTER;

        $index = $this->provider(array_values(VocabularyProvider::VOCABULARY_URLS))
            ->index(null, $staleAt)
        ;

        self::assertSame(
            ['schema:MusicEvent', 'schema:Event'],
            $index->ancestors('thuecat:BrassMusic')
        );
    }

    #[Test]
    public function warnsWhenItFallsBackOnAnExpiredIndex(): void
    {
        $this->provider()->index(null, 1000);
        $staleAt = 1000 + VocabularyIndexCache::STALE_AFTER;

        $this->provider(array_values(VocabularyProvider::VOCABULARY_URLS))
            ->index(null, $staleAt)
        ;

        self::assertCount(1, $this->staleWarnings);
        self::assertSame(VocabularyIndexCache::STALE_AFTER, $this->staleWarnings[0]['age']);
        self::assertStringContainsString('upstream is down', $this->staleWarnings[0]['reason']);
    }

    #[Test]
    public function keepsTheExpiredEntryAfterAFailedRefresh(): void
    {
        $this->provider()->index(null, 1000);
        $staleAt = 1000 + VocabularyIndexCache::STALE_AFTER;

        $this->provider(array_values(VocabularyProvider::VOCABULARY_URLS))
            ->index(null, $staleAt)
        ;

        $stored = $this->cache()->read();
        self::assertNotNull($stored);
        self::assertSame(1000, $stored->fetchedAt, 'The stored entry must not be overwritten.');
        self::assertTrue($stored->index->has('thuecat:BrassMusic'));
    }

    #[Test]
    public function oneFailingVocabularyIsEnoughToKeepTheStoredIndex(): void
    {
        $this->provider()->index(null, 1000);
        $staleAt = 1000 + VocabularyIndexCache::STALE_AFTER;

        $index = $this->provider([VocabularyProvider::VOCABULARY_URLS['thuecat']])
            ->index(null, $staleAt)
        ;

        self::assertTrue($index->has('thuecat:BrassMusic'));
        self::assertSame(1000, $this->cache()->read()?->fetchedAt);
    }

    #[Test]
    public function yieldsAnEmptyIndexWhenNothingIsStoredAndFetchingFails(): void
    {
        $index = $this->provider(array_values(VocabularyProvider::VOCABULARY_URLS))
            ->index(null, 1000)
        ;

        // No ancestors rather than no import: types fall back to flat categories.
        self::assertSame([], $index->all());
        self::assertSame([], $index->ancestors('thuecat:BrassMusic'));
    }

    #[Test]
    public function warnsThatNoHierarchyIsAvailable(): void
    {
        $this->provider(array_values(VocabularyProvider::VOCABULARY_URLS))->index(null, 1000);

        self::assertCount(1, $this->unavailableWarnings);
        self::assertStringContainsString('upstream is down', $this->unavailableWarnings[0]['reason']);
        self::assertSame([], $this->staleWarnings, 'Nothing was stale; nothing was stored.');
    }

    #[Test]
    public function storesNothingWhenAFirstFetchFails(): void
    {
        $this->provider(array_values(VocabularyProvider::VOCABULARY_URLS))->index(null, 1000);

        // An empty index must never be written; the next run has to retry.
        self::assertNull($this->cache()->read());
    }

    #[Test]
    public function fetchesAgainOnTheNextRunAfterAFailedFirstFetch(): void
    {
        $this->provider(array_values(VocabularyProvider::VOCABULARY_URLS))->index(null, 1000);
        $this->requests = [];

        $index = $this->provider()->index(null, 1001);

        self::assertCount(2, $this->requests);
        self::assertTrue($index->has('thuecat:BrassMusic'));
    }

    #[Test]
    public function aLaterSuccessfulRefreshReplacesTheExpiredIndexSilently(): void
    {
        $this->provider()->index(null, 1000);
        $staleAt = 1000 + VocabularyIndexCache::STALE_AFTER;
        $this->provider(array_values(VocabularyProvider::VOCABULARY_URLS))->index(null, $staleAt);
        $this->staleWarnings = [];

        $recoveredAt = $staleAt + 1;
        $this->provider()->index(null, $recoveredAt);

        self::assertSame([], $this->staleWarnings);
        self::assertSame($recoveredAt, $this->cache()->read()?->fetchedAt);
    }
}
