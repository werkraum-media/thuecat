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
 * A filter field whose values live in an MM table over a parent/child option
 * table, offered as the tree below a configured anchor.
 *
 * The anchor is per site and therefore held as the setting to resolve, never as
 * a resolved uid.
 */
abstract class HierarchicalMmField implements FilterFieldDefinition
{
    public function __construct(
        protected readonly string $name,
        protected readonly string $mmTable,
        protected readonly string $mmFieldName,
        protected readonly string $optionTable,
        protected readonly string $parentColumn,
        protected readonly CategoryAnchorSetting $anchorSetting,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getMmTable(): string
    {
        return $this->mmTable;
    }

    public function getMmFieldName(): string
    {
        return $this->mmFieldName;
    }

    public function getOptionTable(): string
    {
        return $this->optionTable;
    }

    public function getParentColumn(): string
    {
        return $this->parentColumn;
    }

    public function getAnchorSetting(): CategoryAnchorSetting
    {
        return $this->anchorSetting;
    }
}
