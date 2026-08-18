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
 * The sys_category anchors an import writes against. Each kind carries an
 * independent pair — a storage folder and a parent category — because
 * imported categories can be spread over several folders.
 *
 * Unlike ImportSetting these have no default: an anchor nothing supplies is
 * unset, which switches its kind's mapping off.
 */
enum CategoryAnchorSetting
{
    case CategoryStoragePid;
    case CategoryParent;
    case KeywordStoragePid;
    case KeywordParent;

    /** Dotted path as used in site settings. */
    public function settingsPath(): string
    {
        return match ($this) {
            self::CategoryStoragePid => 'import.category.storagePid',
            self::CategoryParent => 'import.category.parent',
            self::KeywordStoragePid => 'import.keywords.storagePid',
            self::KeywordParent => 'import.keywords.parent',
        };
    }

    /** Flat key as used in ext_conf_template.txt; dots are not available there. */
    public function extensionConfigurationKey(): string
    {
        return match ($this) {
            self::CategoryStoragePid => 'importCategoryStoragePid',
            self::CategoryParent => 'importCategoryParent',
            self::KeywordStoragePid => 'importKeywordsStoragePid',
            self::KeywordParent => 'importKeywordsParent',
        };
    }
}
