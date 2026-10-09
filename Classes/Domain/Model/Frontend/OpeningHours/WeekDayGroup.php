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
 * Weekdays that compute to the identical set of time periods, collapsed into one
 * row of the merged format (e.g. Monday, Wednesday, Friday: 08:00–12:00). The
 * days are in canonical order (Monday first, PublicHolidays last) but need not be
 * adjacent. PublicHolidays never shares a group with regular weekdays.
 */
final class WeekDayGroup
{
    /**
     * @param list<string> $daysOfWeek
     * @param list<DayRange> $dayRanges
     * @param list<TimePeriod> $timePeriods
     */
    public function __construct(
        private readonly array $daysOfWeek,
        private readonly array $dayRanges,
        private readonly array $timePeriods,
    ) {
    }

    /**
     * @return list<string>
     */
    public function getDaysOfWeek(): array
    {
        return $this->daysOfWeek;
    }

    /**
     * The same weekdays as getDaysOfWeek(), but consecutive runs collapsed into
     * ranges (Monday–Friday); non-consecutive days stay standalone.
     *
     * @return list<DayRange>
     */
    public function getDayRanges(): array
    {
        return $this->dayRanges;
    }

    /**
     * @return list<TimePeriod>
     */
    public function getTimePeriods(): array
    {
        return $this->timePeriods;
    }
}
