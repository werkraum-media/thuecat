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

namespace WerkraumMedia\ThueCat\Service\FilterField;

use WerkraumMedia\ThueCat\Import\Settings\CategoryAnchorSetting;

/**
 * The keyword filter, offering the tree below the site's keyword anchor.
 */
final class KeywordFilterField extends HierarchicalMmField
{
    public function __construct()
    {
        parent::__construct(
            name: 'keywords',
            mmTable: 'sys_category_record_mm',
            mmFieldName: 'keywords',
            optionTable: 'sys_category',
            parentColumn: 'parent',
            anchorSetting: CategoryAnchorSetting::KeywordParent,
        );
    }
}
