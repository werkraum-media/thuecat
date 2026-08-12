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
