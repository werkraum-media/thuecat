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

namespace WerkraumMedia\ThueCat\Import\Http;

/**
 * Unresolved HTTP choices handed to the factory; 0 means "fall back a level".
 * ImportSettings turns these into the values the client actually carries.
 */
final class ClientBudget
{
    public function __construct(
        public readonly int $readTimeout = 0,
        public readonly int $connectTimeout = 0,
        public readonly int $maxAttempts = 0,
    ) {
    }

    // Carries no overrides, so every value resolves from extension config.
    public static function fromExtensionConfiguration(): self
    {
        return new self();
    }
}
