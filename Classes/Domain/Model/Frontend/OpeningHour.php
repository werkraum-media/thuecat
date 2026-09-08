<?php

declare(strict_types=1);

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

namespace WerkraumMedia\ThueCat\Domain\Model\Frontend;

use DateTimeImmutable;
use DateTimeZone;
use WerkraumMedia\ThueCat\Domain\TimingFormat;

class OpeningHour
{
    /**
     * @param mixed[] $daysOfWeek
     */
    private function __construct(
        private readonly string $opens,
        private readonly string $closes,
        private array $daysOfWeek,
        private readonly ?DateTimeImmutable $from,
        private readonly ?DateTimeImmutable $through
    ) {
    }

    /**
     * @param array<string, mixed> $rawData
     */
    public static function createFromArray(array $rawData): OpeningHour
    {
        return new self(
            is_string($rawData['opens'] ?? null) ? $rawData['opens'] : '',
            is_string($rawData['closes'] ?? null) ? $rawData['closes'] : '',
            is_array($rawData['daysOfWeek'] ?? null) ? $rawData['daysOfWeek'] : [],
            self::createDate($rawData['from'] ?? null),
            self::createDate($rawData['through'] ?? null)
        );
    }

    /**
     * Serialized DateTime blob: ['date' => …, 'timezone' => …].
     */
    protected static function createDate(mixed $rawDate): ?DateTimeImmutable
    {
        if (is_array($rawDate) === false || is_string($rawDate['date'] ?? null) === false) {
            return null;
        }

        $timeZone = is_string($rawDate['timezone'] ?? null) ? $rawDate['timezone'] : 'Europe/Berlin';

        return new DateTimeImmutable($rawDate['date'], new DateTimeZone($timeZone));
    }

    public function getOpens(): string
    {
        return TimingFormat::format($this->opens);
    }

    public function getCloses(): string
    {
        return TimingFormat::format($this->closes);
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

    public function isSingleDay(): bool
    {
        $from = $this->getFrom();
        $through = $this->getThrough();

        return $from instanceof DateTimeImmutable
            && $through instanceof DateTimeImmutable
            && $from->format('Ymd') === $through->format('Ymd');
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
