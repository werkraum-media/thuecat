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

use DateTimeImmutable;

/**
 * One validity window of the merged format, holding its weekday groups in
 * display order (groups by earliest contained day, PublicHolidays last). Mirrors
 * Period but carries WeekDayGroups instead of individual weekdays; closed days
 * produce no group.
 */
final class MergedPeriod implements PeriodInterface
{
    /**
     * @param list<WeekDayGroup> $weekDayGroups
     */
    public function __construct(
        private readonly ?DateTimeImmutable $validFrom,
        private readonly ?DateTimeImmutable $validThrough,
        private readonly array $weekDayGroups,
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
     * @return list<WeekDayGroup>
     */
    public function getWeekDayGroups(): array
    {
        return $this->weekDayGroups;
    }

    public function isCurrent(): bool
    {
        return $this->current;
    }
}
