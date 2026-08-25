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
 * What a term is called in each language, and whether the fallback map had to
 * be consulted to get there.
 *
 * The second part is what the import report is measuring: a title that came
 * wholly from upstream needs no one's attention, while one the map supplied is
 * a title somebody maintains by hand.
 */
final class TitleResolution
{
    /**
     * @param array<string, string> $titles language code => title
     */
    public function __construct(
        public readonly array $titles,
        public readonly bool $usedFallback
    ) {
    }

    public function hasTitleFor(string $language): bool
    {
        return ($this->titles[$language] ?? '') !== '';
    }
}
