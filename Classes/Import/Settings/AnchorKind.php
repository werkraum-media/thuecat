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
