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
 * The sys_category anchors an import writes against. Each kind carries an
 * independent pair — a storage folder and a parent category — because
 * imported categories can be spread over several folders.
 *
 * The cases name the kinds only; every spelling is scoped by the AnchorScope
 * it is asked for, so anchors are per site *and* per record kind. One site can
 * hold several kinds, and each keeps its own category tree — without the scope
 * segment they would all anchor to one parent.

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
    public function settingsPath(AnchorScope $scope): string
    {
        return 'import.' . $scope->value . '.' . match ($this) {
            self::CategoryStoragePid => 'category.storagePid',
            self::CategoryParent => 'category.parent',
            self::KeywordStoragePid => 'keywords.storagePid',
            self::KeywordParent => 'keywords.parent',
        };
    }

    /** Flat key as used in ext_conf_template.txt; dots are not available there. */
    public function extensionConfigurationKey(AnchorScope $scope): string
    {
        return 'import' . ucfirst($scope->value) . match ($this) {
            self::CategoryStoragePid => 'CategoryStoragePid',
            self::CategoryParent => 'CategoryParent',
            self::KeywordStoragePid => 'KeywordsStoragePid',
            self::KeywordParent => 'KeywordsParent',
        };
    }
}
