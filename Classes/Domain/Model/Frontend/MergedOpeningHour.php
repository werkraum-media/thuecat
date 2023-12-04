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

use DateTimeImmutable;

class MergedOpeningHour
{
    /**
     * @var MergedOpeningHourWeekDay[]
     */
    private array $weekDays = [];

    private ?DateTimeImmutabl $from;

    private ?DateTimeImmutabl $through;

    public function __construct(
        array $weekDays,
        ?DateTimeImmutable $from,
        ?DateTimeImmutable $through
    ) {
        $this->weekDays = array_values($weekDays);
        $this->from = $from;
        $this->through = $through;
    }

    /**
     * @return MergedOpeningHourWeekDay[]
     */
    public function getWeekDays(): array
    {
        return $this->weekDays;
    }

    public function getWeekDaysWithMondayFirstWeekDay(): array
    {
        return $this->sortWeekDays([
            'Monday',
            'Tuesday',
            'Wednesday',
            'Thursday',
            'Friday',
            'Saturday',
            'Sunday',
            'PublicHolidays',
        ]);
    }

    public function getFrom(): ?DateTimeImmutable
    {
        return $this->from;
    }

    public function getThrough(): ?DateTimeImmutable
    {
        return $this->through;
    }

    private function sortWeekDays(array $sorting): array
    {
        $days = [];
        $weekDays = array_map(function (MergedOpeningHourWeekDay $weekDay) {
            return $weekDay->getDayOfWeek();
        }, $this->weekDays);

        foreach ($sorting as $weekDay) {
            $position = array_search($weekDay, $weekDays);
            if ($position === false) {
                continue;
            }

            $days[] = $this->weekDays[$position];
        }

        return $days;
    }
}
