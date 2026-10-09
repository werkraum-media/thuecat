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
 * One weekday within a period, carrying ALL of its open–close time periods in
 * order. A day can hold several periods (08:00–12:00, 13:00–18:00).
 * An empty list means closed that day.
 */
final class WeekDay
{
    /**
     * @param list<TimePeriod> $timePeriods
     */
    public function __construct(
        private readonly string $dayOfWeek,
        private readonly array $timePeriods,
    ) {
    }

    public function getDayOfWeek(): string
    {
        return $this->dayOfWeek;
    }

    /**
     * @return list<TimePeriod>
     */
    public function getTimePeriods(): array
    {
        return $this->timePeriods;
    }

    public function isClosed(): bool
    {
        return $this->timePeriods === [];
    }
}
