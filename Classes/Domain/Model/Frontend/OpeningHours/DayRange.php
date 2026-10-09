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

namespace WerkraumMedia\ThueCat\Domain\Model\Frontend\OpeningHours;

/**
 * A run of consecutive weekdays within a WeekDayGroup, collapsed to its first and
 * last day (e.g. Monday–Friday). A standalone day has firstDay === lastDay and
 * isRange() === false, so the template renders "Monday" instead of a span.
 */
final class DayRange
{
    public function __construct(
        private readonly string $firstDay,
        private readonly string $lastDay,
    ) {
    }

    public function getFirstDay(): string
    {
        return $this->firstDay;
    }

    public function getLastDay(): string
    {
        return $this->lastDay;
    }

    public function isRange(): bool
    {
        return $this->firstDay !== $this->lastDay;
    }
}
