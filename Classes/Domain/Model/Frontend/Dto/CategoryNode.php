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

namespace WerkraumMedia\ThueCat\Domain\Model\Frontend\Dto;

use WerkraumMedia\ThueCat\Domain\Model\Frontend\Category;

// One selectable node of the search form's category tree.
final class CategoryNode
{
    /**
     * @param CategoryNode[] $children
     */
    public function __construct(
        private readonly Category $category,
        private readonly array $children,
    ) {
    }

    public function getCategory(): Category
    {
        return $this->category;
    }

    /**
     * @return CategoryNode[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }
}
