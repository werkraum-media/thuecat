<?php

declare(strict_types=1);

/*
 * Copyright (C) 2023 Daniel Siepmann <coding@daniel-siepmann.de>
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

use WerkraumMedia\ThueCat\Domain\TimingFormat;

class MergedOpeningHourWeekDay
{
    /**
     * @var string
     */
    private $opens;

    /**
     * @var string
     */
    private $closes;

    /**
     * @var string
     */
    private $dayOfWeek;

    public function __construct(
        string $opens,
        string $closes,
        string $dayOfWeek
    ) {
        $this->opens = $opens;
        $this->closes = $closes;
        $this->dayOfWeek = $dayOfWeek;
    }

    public function getOpens(): string
    {
        return TimingFormat::format($this->opens);
    }

    public function getCloses(): string
    {
        return TimingFormat::format($this->closes);
    }

    public function getDayOfWeek(): string
    {
        return $this->dayOfWeek;
    }
}
