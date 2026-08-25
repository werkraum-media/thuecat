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

namespace WerkraumMedia\ThueCat\Import\SysCategory;

/**
 * One term to provision as a `sys_category` row: what identifies it, what it is
 * called in each language, and which already-provisioned term it hangs beneath.
 *
 * Identity is the source value, never the title, so a term keeps its record when
 * upstream renames it or when its title starts coming from another source.
 */
final class SysCategoryTerm
{
    /**
     * @param array<string, string> $titles      language code => title; the
     *                                           default language decides
     *                                           whether the term is usable
     * @param string|null           $parentValue source value of the parent,
     *                                           null to hang off the anchor
     */
    public function __construct(
        public readonly string $sourceValue,
        public readonly array $titles,
        public readonly ?string $parentValue = null
    ) {
    }

    public function titleFor(string $language): ?string
    {
        $title = $this->titles[$language] ?? null;

        return ($title === null || $title === '') ? null : $title;
    }
}
