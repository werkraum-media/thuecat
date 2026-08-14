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

namespace WerkraumMedia\ThueCat\Import;

/** One keyword claimed by one owner field, held until the flush relates it. */
final class CollectedKeyword
{
    public function __construct(
        public readonly string $ownerTable,
        public readonly string $ownerKey,
        public readonly string $targetField,
        public readonly string $remoteId,
        public readonly string $title,
        // Null for a keyword that sits directly under the configured anchor.
        public readonly ?string $parentRemoteId = null,
        // False for an ancestor: it becomes a category, never a relation.
        public readonly bool $isCited = true,
    ) {
    }
}
