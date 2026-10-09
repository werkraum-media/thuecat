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

namespace WerkraumMedia\ThueCat\Import\Progress;

final class ImportProgress
{
    /**
     * @param int|null $total null where the size is not knowable in advance;
     *        renderers must show liveness rather than invent a percentage
     */
    public function __construct(
        public readonly ImportPhase $phase,
        public readonly string $label = '',
        public readonly ?int $current = null,
        public readonly ?int $total = null,
    ) {
    }

    public function hasPosition(): bool
    {
        return $this->current !== null && $this->total !== null && $this->total > 0;
    }
}
