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

/**
 * A stored index together with the age that decides whether it still counts as
 * current. Keeping the two together is what lets a caller use a stale index
 * deliberately when a refresh fails.
 */
final class CachedVocabularyIndex
{
    public function __construct(
        public readonly VocabularyIndex $index,
        public readonly int $fetchedAt
    ) {
    }

    public function isStale(int $now): bool
    {
        return ($now - $this->fetchedAt) >= VocabularyIndexCache::STALE_AFTER;
    }
}
