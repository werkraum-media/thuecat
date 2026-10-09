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

namespace WerkraumMedia\ThueCat\Import\Settings;

/**
 * One kind's anchors as resolved for a record kind, with the scope that
 * supplied them. 0 means unset; the scope is null when no scope in the chain
 * supplied either value, which switches the kind's mapping off.
 */
final class AnchorPair
{
    public function __construct(
        public readonly int $parent = 0,
        public readonly int $storagePid = 0,
        public readonly ?AnchorScope $scope = null,
    ) {
    }

    public function isSet(): bool
    {
        return $this->parent > 0 || $this->storagePid > 0;
    }
}
