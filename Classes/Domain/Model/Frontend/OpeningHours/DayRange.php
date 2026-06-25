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
