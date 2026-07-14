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

namespace WerkraumMedia\ThueCat\Domain\Model\Frontend;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

// Minimal sys_category view for filtering + the search form's option list.
class Category extends AbstractEntity
{
    protected string $title = '';

    protected ?Category $parent = null;

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getParent(): ?Category
    {
        return $this->parent;
    }
}
