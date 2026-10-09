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
 * Per-day opening-hours format: the periods in display order (current first,
 * then upcoming), each holding every weekday Monday-first, plus the computed
 * open-now status. One of the format DTOs the OpeningHoursFormatter produces
 * (paired with the PerDayTable partial); raw tx_thuecat_opening_hours rows are
 * never exposed directly.
 */
final class PerDayTable
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
