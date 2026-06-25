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

use DateTimeImmutable;

/**
 * One open–close interval on a single weekday. A weekday may carry several.
 * Wall-clock only — the date part is irrelevant.
 */
final class TimePeriod
{
    public function __construct(
        private readonly DateTimeImmutable $opens,
        private readonly DateTimeImmutable $closes,
    ) {
    }

    public function getOpens(): DateTimeImmutable
    {
        return $this->opens;
    }

    public function getCloses(): DateTimeImmutable
    {
        return $this->closes;
    }
}
