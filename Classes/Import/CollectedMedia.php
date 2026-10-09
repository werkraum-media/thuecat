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

namespace WerkraumMedia\ThueCat\Import;

/** One image claimed by one owner field, held until the flush relates it. */
final class CollectedMedia
{
    public function __construct(
        public readonly string $ownerTable,
        public readonly string $ownerKey,
        public readonly string $targetField,
        public readonly int $fileUid,
        public readonly string $title,
        public readonly string $description,
    ) {
    }
}
