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
