<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS extension "thuecat".
 *
 * Copyright (C) werkraum-media <https://werkraum-media.de/>
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * For the full license text, see the LICENSE file distributed with this
 * extension.
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
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
