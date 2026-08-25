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

namespace WerkraumMedia\ThueCat\Import\SysCategory;

/**
 * Where one consumer's terms live: the `sys_category` they hang beneath, the
 * page holding them, and the prefix marking their identifiers as its own.
 *
 * Consumers pass their own anchor, which is what keeps their trees apart while
 * sharing the provisioning that builds them.
 */
final class SysCategoryAnchor
{
    public function __construct(
        public readonly int $parentUid,
        public readonly int $storagePid,
        public readonly string $identifierPrefix
    ) {
    }

    public function isConfigured(): bool
    {
        return $this->parentUid !== 0 || $this->storagePid !== 0;
    }

    public function prefixed(string $value): string
    {
        return $this->identifierPrefix . $value;
    }
}
