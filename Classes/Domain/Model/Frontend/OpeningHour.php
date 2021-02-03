<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Domain\Model\Frontend;

/*
 * Copyright (C) 2021 Daniel Siepmann <coding@daniel-siepmann.de>
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

class OpeningHour
{
    private string $opens;
    private string $closes;
    private array $daysOfWeek;
    private ?\DateTimeImmutable $from;
    private ?\DateTimeImmutable $through;

    private function __construct(
        string $opens,
        string $closes,
        array $daysOfWeek,
        ?\DateTimeImmutable $from,
        ?\DateTimeImmutable $through
    ) {
        $this->opens = $opens;
        $this->closes = $closes;
        $this->daysOfWeek = $daysOfWeek;
        $this->from = $from;
        $this->through = $through;
    }

    public static function createFromArray(array $rawData): self
    {
        $from = null;
        if (isset($rawData['from'])) {
            $timeZone = new \DateTimeZone($rawData['from']['timezone']);
            $from = new \DateTimeImmutable($rawData['from']['date'], $timeZone);
        }
        $through = null;
        if (isset($rawData['through'])) {
            $timeZone = new \DateTimeZone($rawData['through']['timezone']);
            $through = new \DateTimeImmutable($rawData['through']['date'], $timeZone);
        }

        return new self(
            $rawData['opens'] ?? '',
            $rawData['closes'] ?? '',
            $rawData['daysOfWeek'] ?? '',
            $from,
            $through
        );
    }

    public function getOpens(): string
    {
        return $this->opens;
    }

    public function getCloses(): string
    {
        return $this->closes;
    }

    public function getDaysOfWeek(): array
    {
        return $this->daysOfWeek;
    }

    public function getDaysOfWeekWithMondayFirstWeekDay(): array
    {
        return $this->sortedDaysOfWeek([
            'Monday',
            'Tuesday',
            'Wednesday',
            'Thursday',
            'Friday',
            'Saturday',
            'Sunday',
        ]);
    }

    public function getFrom(): ?\DateTimeImmutable
    {
        return $this->from;
    }

    public function getThrough(): ?\DateTimeImmutable
    {
        return $this->through;
    }

    private function sortedDaysOfWeek(array $sorting): array
    {
        if ($this->daysOfWeek === []) {
            return [];
        }

        $days = [];

        foreach ($sorting as $weekDay) {
            $position = array_search($weekDay, $this->daysOfWeek);
            if ($position === false) {
                continue;
            }

            $days[] = $this->daysOfWeek[$position];
        }

        return $days;
    }
}
