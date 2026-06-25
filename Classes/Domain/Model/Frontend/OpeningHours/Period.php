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
 * Opening hours valid within one date window (open-ended when from/through are
 * null). Holds the weekdays in display order (Monday first, PublicHolidays last);
 * the formatter only emits days that exist for this period — the template decides
 * whether to pad closed days. A period is "current" when today falls in its
 * window, otherwise "future" (past periods are dropped by the formatter).
 */
final class Period implements PeriodInterface
{
    /**
     * @param list<WeekDay> $weekDays
     */
    public function __construct(
        private readonly ?DateTimeImmutable $validFrom,
        private readonly ?DateTimeImmutable $validThrough,
        private readonly array $weekDays,
        private readonly bool $current,
    ) {
    }

    public function getValidFrom(): ?DateTimeImmutable
    {
        return $this->validFrom;
    }

    public function getValidThrough(): ?DateTimeImmutable
    {
        return $this->validThrough;
    }

    /**
     * @return list<WeekDay>
     */
    public function getWeekDays(): array
    {
        return $this->weekDays;
    }

    public function isCurrent(): bool
    {
        return $this->current;
    }
}
