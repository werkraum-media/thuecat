<?php

declare(strict_types=1);

/*
 * Copyright (C) 2026 werkraum-media
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301, USA.
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
