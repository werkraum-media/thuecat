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

namespace WerkraumMedia\ThueCat\Import\Parser\Entity;

/** One `schema:keywords` entry, tagged with the shape it arrived in. */
final class KeywordEntry
{
    public const SHAPE_REFERENCE = 'reference';

    public const SHAPE_ONTOLOGY = 'ontology';

    public const SHAPE_FREE_TEXT = 'freeText';

    public function __construct(
        public readonly string $shape,
        public readonly string $value,
        // Set for ontology entries only: the fetched term may belong to several
        // enums, so only the usage site says which one applies here.
        public readonly ?string $usageType = null,
    ) {
    }
}
