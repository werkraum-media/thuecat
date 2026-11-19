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
 * How many relations or files went, the errors of those that could not, and the files the removed relations
 * referenced, still to be judged.
 */
final readonly class RemovalTally
{
    /**
     * @param list<string> $errors
     * @param list<int> $fileCandidates
     */
    public function __construct(
        public int $removed,
        public array $errors,
        public array $fileCandidates = [],
    ) {
    }
}
