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

namespace WerkraumMedia\ThueCat\Import\Settings;

/**
 * The sys_category anchors one import run writes against, already resolved.
 * 0 means unset; a kind whose pair is both unset has its mapping off.
 */
class CategoryAnchors
{
    public function __construct(
        public readonly int $categoryParent = 0,
        public readonly int $categoryStoragePid = 0,
        public readonly int $keywordParent = 0,
        public readonly int $keywordStoragePid = 0,
    ) {
    }

    /** Lets a caller walk CategoryAnchorSetting::cases() instead of naming each field. */
    public function for(CategoryAnchorSetting $setting): int
    {
        return match ($setting) {
            CategoryAnchorSetting::CategoryParent => $this->categoryParent,
            CategoryAnchorSetting::CategoryStoragePid => $this->categoryStoragePid,
            CategoryAnchorSetting::KeywordParent => $this->keywordParent,
            CategoryAnchorSetting::KeywordStoragePid => $this->keywordStoragePid,
        };
    }
}
