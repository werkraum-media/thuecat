<?php

declare(strict_types=1);

/*
 * Copyright (C) 2026 werkraum-media
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 */

namespace WerkraumMedia\ThueCat\Import\Vocabulary;

use Throwable;
use WerkraumMedia\ThueCat\Import\Importer\FetchData;
use WerkraumMedia\ThueCat\Import\ImportLogger;

/**
 * Hands out the merged class index, fetching it only when what is stored has
 * gone stale.
 *
 * Whole vocabularies are fetched, never single types: the per-type endpoints
 * are rate limited, and a climb would need one request per ancestor.
 */
class VocabularyProvider
{
    /** Merged in order; a later entry overrides a class an earlier one declares. */
    public const VOCABULARY_URLS = [
        'schema.org' => 'https://schema.org/version/latest/schemaorg-current-https.jsonld',
        'thuecat' => 'https://thuecat.org/ontology/thuecat/1.0/?format=jsonld',
    ];

    public function __construct(
        private readonly FetchData $fetchData,
        private readonly VocabularyIndexCache $cache,
        private readonly VocabularyIndexFactory $indexFactory,
        private readonly ImportLogger $logger
    ) {
    }

    public function index(?string $apiKey = null, ?int $now = null): VocabularyIndex
    {
        $now ??= time();
        $stored = $this->cache->read();

        if ($stored !== null && !$stored->isStale($now)) {
            return $stored->index;
        }

        try {
            $index = $this->fetch($apiKey);
        } catch (Throwable $failure) {
            return $this->indexBehindFailure($stored, $failure, $now);
        }

        $this->cache->write($index, $now);

        return $index;
    }

    /**
     * A refresh is all or nothing: the vocabularies merge into one index, so
     * keeping a freshly fetched half beside a stale half would drop the failed
     * vocabulary's classes and break the chains crossing between them.
     */
    private function fetch(?string $apiKey): VocabularyIndex
    {
        $documents = [];
        foreach (self::VOCABULARY_URLS as $url) {
            $documents[] = $this->fetchData->jsonLDFromUrl($url, $apiKey);
        }

        return $this->indexFactory->fromDocuments($documents);
    }

    /**
     * The stored entry is deliberately left in place: discarding it would turn
     * one upstream outage into every later run resolving no hierarchy at all.
     */
    private function indexBehindFailure(
        ?CachedVocabularyIndex $stored,
        Throwable $failure,
        int $now
    ): VocabularyIndex {
        if ($stored === null) {
            $this->logger->recordUnavailableVocabulary(
                implode(', ', array_keys(self::VOCABULARY_URLS)),
                $failure->getMessage()
            );

            return new VocabularyIndex([]);
        }

        $this->logger->recordStaleVocabulary(
            implode(', ', array_keys(self::VOCABULARY_URLS)),
            $now - $stored->fetchedAt,
            $failure->getMessage()
        );

        return $stored->index;
    }
}
