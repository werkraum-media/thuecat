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

namespace WerkraumMedia\ThueCat\Import;

/** One keyword claimed by one owner field, held until the flush relates it. */
final class CollectedKeyword
{
    /**
     * @param array<string, string> $titles language code => title. Upstream
     *                                      resolves one per language, so a
     *                                      single title would keep the default
     *                                      language's and discard the rest.
     */
    public function __construct(
        public readonly string $ownerTable,
        public readonly string $ownerKey,
        public readonly string $targetField,
        public readonly string $remoteId,
        public readonly array $titles,
        // Null for a keyword that sits directly under the configured anchor.
        public readonly ?string $parentRemoteId = null,
        // False for an ancestor: it becomes a category, never a relation.
        public readonly bool $isCited = true,
    ) {
    }

    public function titleFor(string $language): ?string
    {
        $title = $this->titles[$language] ?? null;

        return ($title === null || $title === '') ? null : $title;
    }
}
