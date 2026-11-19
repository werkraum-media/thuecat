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
 * An import configuration roots of a site originate from, by the uid and title stored on the roots, so a
 * configuration deleted or renamed since is still shown as it was.
 */
final readonly class OriginRoots
{
    public function __construct(
        public int $configurationUid,
        public string $title,
        public int $count,
    ) {
    }
}
