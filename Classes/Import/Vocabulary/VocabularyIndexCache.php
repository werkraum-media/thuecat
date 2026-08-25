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

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;

/**
 * Holds the distilled index between runs and decides when it is stale.
 *
 * Staleness is ours, not the backend's: TYPO3 offers no way to read an entry
 * past its lifetime, and an expired entry is exactly what a failed refresh has
 * to fall back on. So the entry is written to outlive this window by far and
 * carries the time it was fetched; we compare against that ourselves.
 */
class VocabularyIndexCache
{
    /**
     * 14 days. Class vocabularies are near-static, so refetching per run would
     * cost bandwidth without changing a single resolved chain.
     */
    public const STALE_AFTER = 1209600;

    /**
     * Bumped whenever distillation changes shape, so an entry written by an
     * older format is rebuilt instead of being read as if it still fit.
     */
    public const FORMAT = 1;

    private const ENTRY = 'index';

    public function __construct(
        #[Autowire(service: 'cache.thuecat_vocabulary')]
        private readonly FrontendInterface $cache
    ) {
    }

    /**
     * The stored entry, whether or not it is stale, or null when nothing usable
     * is stored. A caller that cannot refresh may still use a stale entry.
     */
    public function read(): ?CachedVocabularyIndex
    {
        $entry = $this->cache->get(self::ENTRY);
        if (!is_array($entry)) {
            return null;
        }

        // An entry from another format will be overwritten by the next successful fetch.
        if (($entry['format'] ?? null) !== self::FORMAT) {
            return null;
        }

        $fetchedAt = $entry['fetchedAt'] ?? null;
        $classes = $entry['classes'] ?? null;
        if (!is_int($fetchedAt) || !is_array($classes)) {
            return null;
        }

        return new CachedVocabularyIndex($this->indexFrom($classes), $fetchedAt);
    }

    public function write(VocabularyIndex $index, int $fetchedAt): void
    {
        $this->cache->set(self::ENTRY, [
            'format' => self::FORMAT,
            'fetchedAt' => $fetchedAt,
            'classes' => $this->classesFrom($index),
        ]);
    }

    /**
     * Stored as plain arrays rather than objects: a serialised object graph
     * breaks the moment the class changes shape under a deployment.
     *
     * @return array<string, array{parents: list<string>, labels: array<string, string>}>
     */
    private function classesFrom(VocabularyIndex $index): array
    {
        $classes = [];
        foreach ($index->all() as $id => $class) {
            $classes[$id] = ['parents' => $class->parents, 'labels' => $class->labels];
        }

        return $classes;
    }

    /**
     * @param array<mixed> $classes
     */
    private function indexFrom(array $classes): VocabularyIndex
    {
        $restored = [];
        foreach ($classes as $id => $class) {
            if (!is_string($id) || !is_array($class)) {
                continue;
            }
            $parents = $class['parents'] ?? [];
            $labels = $class['labels'] ?? [];
            $restored[$id] = new VocabularyClass(
                $id,
                is_array($parents) ? array_values(array_filter($parents, 'is_string')) : [],
                is_array($labels) ? array_filter($labels, 'is_string') : []
            );
        }

        return new VocabularyIndex($restored);
    }
}
