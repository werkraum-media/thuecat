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

namespace WerkraumMedia\ThueCat\Service\FilterField;

use WerkraumMedia\ThueCat\Import\Settings\CategoryAnchorSetting;

/**
 * The category filter, offering the tree below the site's category anchor.
 *
 * Shares its MM table with the keyword filter and is told apart from it only by
 * the MM field name.
 */
final class CategoryFilterField extends HierarchicalMmField
{
    public function __construct()
    {
        parent::__construct(
            name: 'categories',
            mmTable: 'sys_category_record_mm',
            mmFieldName: 'categories',
            optionTable: 'sys_category',
            parentColumn: 'parent',
            anchorSetting: CategoryAnchorSetting::CategoryParent,
        );
    }
}
