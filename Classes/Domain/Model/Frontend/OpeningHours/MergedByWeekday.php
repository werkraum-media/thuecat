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
 * Merged-by-weekday opening-hours format: per period the weekdays are collapsed
 * into groups sharing identical hours (e.g. Monday–Friday: 08:00–18:00). One of
 * the format DTOs the OpeningHoursFormatter produces (paired with the
 * MergedByWeekday partial); raw tx_thuecat_opening_hours rows are never exposed
 * directly.
 */
final class MergedByWeekday
{
    /**
     * @param list<MergedPeriod> $periods
     */
    public function __construct(
        private readonly array $periods,
        private readonly bool $openNow,
    ) {
    }

    /**
     * @return list<MergedPeriod>
     */
    public function getPeriods(): array
    {
        return $this->periods;
    }

    public function isOpenNow(): bool
    {
        return $this->openNow;
    }

    public function isEmpty(): bool
    {
        return $this->periods === [];
    }
}
