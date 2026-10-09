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

namespace WerkraumMedia\ThueCat\Domain\Model\Frontend;

use DateTimeImmutable;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * One imported tx_thuecat_opening_hours row: a single weekday's open/close span
 * within an optional validity window. Mapped one-to-one to the DB; the grouping,
 * weekday ordering, multi-span-per-day merge and past-date filtering that produce
 * the display shape live in the OpeningHoursFormatter, not here. Not rendered
 * bare — Place exposes it only through the formatter.
 */
class OpeningHourSpecification extends AbstractEntity
{
    protected string $specificationType = '';

    protected string $dayOfWeek = '';

    /**
     * dbType=time: thawed into a same-day DateTimeImmutable by Extbase. Only the
     * wall-clock time is meaningful.
     */
    protected ?DateTimeImmutable $opens = null;

    protected ?DateTimeImmutable $closes = null;

    protected ?DateTimeImmutable $validFrom = null;

    protected ?DateTimeImmutable $validThrough = null;

    public function getSpecificationType(): string
    {
        return $this->specificationType;
    }

    public function getDayOfWeek(): string
    {
        return $this->dayOfWeek;
    }

    public function getOpens(): ?DateTimeImmutable
    {
        return $this->opens;
    }

    public function getCloses(): ?DateTimeImmutable
    {
        return $this->closes;
    }

    public function getValidFrom(): ?DateTimeImmutable
    {
        return $this->validFrom;
    }

    public function getValidThrough(): ?DateTimeImmutable
    {
        return $this->validThrough;
    }
}
