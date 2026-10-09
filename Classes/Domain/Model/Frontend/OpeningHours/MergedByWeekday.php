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
