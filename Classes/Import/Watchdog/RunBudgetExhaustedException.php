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

namespace WerkraumMedia\ThueCat\Import\Watchdog;

use RuntimeException;
use WerkraumMedia\ThueCat\Import\Progress\ImportPhase;

// Deliberate abort, caught by the importer so the log still gets written.
final class RunBudgetExhaustedException extends RuntimeException
{
    public function __construct(
        string $message,
        int $code,
        public readonly ImportPhase $phase,
        public readonly int $budgetSeconds,
        public readonly float $elapsedSeconds,
    ) {
        parent::__construct($message, $code);
    }
}
