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
