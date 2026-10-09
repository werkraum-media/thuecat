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

    /**
     * Run bookkeeping key for an identifier. One term can exist once per tree
     * with the same remote_id, so staging and translations are told apart by
     * the parent they hang beneath; anchors sharing a parent share the row.
     */
    public function stagingKey(string $identifier): string
    {
        return $this->parentUid . self::STAGING_SEPARATOR . $identifier;
    }

    public const STAGING_SEPARATOR = '|';
}
