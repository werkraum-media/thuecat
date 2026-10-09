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

namespace WerkraumMedia\ThueCat\Tests\Functional;

use RuntimeException;
use WerkraumMedia\ThueCat\Import\Watchdog\RunDeadline;

// Stands in for any run-ending failure at a known point in the run.
final class DeadlineThrowingOnCheck extends RunDeadline
{
    private int $checks = 0;

    public function __construct(private readonly int $throwsOnCheck)
    {
        parent::__construct(3600);
    }

    public function isExpired(): bool
    {
        $this->checks++;
        if ($this->checks === $this->throwsOnCheck) {
            throw new RuntimeException('deliberate mid-run failure', 1786531208);
        }

        return false;
    }
}
