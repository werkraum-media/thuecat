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

namespace WerkraumMedia\ThueCat\Import\Parser\Entity;

/** One `schema:keywords` entry, tagged with the shape it arrived in. */
final readonly class KeywordEntry
{
    public const string SHAPE_REFERENCE = 'reference';

    public const string SHAPE_ONTOLOGY = 'ontology';

    public const string SHAPE_FREE_TEXT = 'freeText';

    public function __construct(
        public string $shape,
        public string $value,
        // Set for ontology entries only: the fetched term may belong to several
        // enums, so only the usage site says which one applies here.
        public ?string $usageType = null,
    ) {
    }
}
