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

/**
 * One selectable value of a filter field: uid, title, and — for hierarchical
 * fields — the options below it.
 */
final class FilterOption
{
    /**
     * @param FilterOption[] $children
     */
    public function __construct(
        private readonly int $uid,
        private readonly string $title,
        private readonly array $children = [],
    ) {
    }

    public function getUid(): int
    {
        return $this->uid;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return FilterOption[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    public function getCategory(): self
    {
        return $this;
    }
}
