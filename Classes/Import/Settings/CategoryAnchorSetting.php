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
 * The cases name the kinds only; every spelling is scoped by the ImportTarget
 * it is asked for, so anchors are per site *and* per target. One site can hold
 * import configurations of several targets, and each keeps its own category
 * tree — without the target segment they would all anchor to one parent.
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
    public function settingsPath(ImportTarget $target): string
    {
        return 'import.' . $target->value . '.' . match ($this) {
            self::CategoryStoragePid => 'category.storagePid',
            self::CategoryParent => 'category.parent',
            self::KeywordStoragePid => 'keywords.storagePid',
            self::KeywordParent => 'keywords.parent',
        };
    }

    /** Flat key as used in ext_conf_template.txt; dots are not available there. */
    public function extensionConfigurationKey(ImportTarget $target): string
    {
        return 'import' . ucfirst($target->value) . match ($this) {
            self::CategoryStoragePid => 'CategoryStoragePid',
            self::CategoryParent => 'CategoryParent',
            self::KeywordStoragePid => 'KeywordsStoragePid',
            self::KeywordParent => 'KeywordsParent',
        };
    }
}
