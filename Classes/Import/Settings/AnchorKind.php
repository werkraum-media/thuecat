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
 * The category-based properties an anchor serves. Each falls back between
 * scopes as a pair, so its two settings are named together here.
 */
enum AnchorKind
{
    case Category;
    case Keyword;

    public function parentSetting(): CategoryAnchorSetting
    {
        return match ($this) {
            self::Category => CategoryAnchorSetting::CategoryParent,
            self::Keyword => CategoryAnchorSetting::KeywordParent,
        };
    }

    public function storagePidSetting(): CategoryAnchorSetting
    {
        return match ($this) {
            self::Category => CategoryAnchorSetting::CategoryStoragePid,
            self::Keyword => CategoryAnchorSetting::KeywordStoragePid,
        };
    }
}
