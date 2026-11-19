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

namespace WerkraumMedia\ThueCat\Removal;

/**
 * What removing one root did: the root removed, skipped because it was already gone, or refused with errors;
 * and how many of the relations and files it left unused went with it.
 */
final readonly class RootOutcome
{
    /**
     * @param list<string> $errors
     */
    public function __construct(
        public bool $removed,
        public bool $skipped,
        public array $errors = [],
        public int $removedRelations = 0,
        public int $removedFiles = 0,
    ) {
    }
}
