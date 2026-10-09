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
