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
 * Display-ready opening hours for one place: the periods in display order
 * (current first, then upcoming) and the computed open-now status. This is the
 * presentation-agnostic shape the OpeningHoursFormatter produces and the Fluid
 * partials render; raw tx_thuecat_opening_hours rows are never exposed directly.
 */
final class OpeningHours
{
    /**
     * @param list<Period> $periods
     */
    public function __construct(
        private readonly array $periods,
        private readonly bool $openNow,
    ) {
    }

    /**
     * @return list<Period>
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
