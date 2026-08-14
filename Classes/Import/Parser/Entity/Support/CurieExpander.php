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

namespace WerkraumMedia\ThueCat\Import\Parser\Entity\Support;

/**
 * Expands a CURIE to its ontology URI. Hardcoded because the parse path only
 * ever sees `@graph`; the payload's `@context` is discarded before that.
 */
class CurieExpander
{
    private const BASE_BY_PREFIX = [
        'thuecat' => 'https://thuecat.org/ontology/thuecat/1.0/',
    ];

    public function expand(string $curie): ?string
    {
        $separator = strpos($curie, ':');
        if ($separator === false) {
            return null;
        }

        $base = self::BASE_BY_PREFIX[substr($curie, 0, $separator)] ?? null;
        $localPart = substr($curie, $separator + 1);
        if ($base === null || $localPart === '') {
            return null;
        }

        return $base . $localPart;
    }
}
