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

namespace WerkraumMedia\ThueCat\Service;

use DateTimeImmutable;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\OpeningHours\OpeningHours;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\OpeningHours\Period;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\OpeningHours\TimePeriod;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\OpeningHours\WeekDay;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\OpeningHourSpecification;

/**
 * Turns the flat tx_thuecat_opening_hours rows (one weekday + one open/close span
 * each) into the display-ready OpeningHours shape: rows are grouped into validity
 * periods, then by weekday in Monday-first order, each weekday collecting ALL its
 * time periods so a lunch break shows both periods under one day. Past
 * periods are dropped; the period containing today is marked current and drives
 * the open-now status. All compute lives here so templates request shapes without
 * re-importing.
 */
class OpeningHoursFormatter
{
    /**
     * Canonical display order; PublicHolidays sorts last (after Sunday).
     */
    private const WEEKDAY_ORDER = [
        'Monday' => 1,
        'Tuesday' => 2,
        'Wednesday' => 3,
        'Thursday' => 4,
        'Friday' => 5,
        'Saturday' => 6,
        'Sunday' => 7,
        'PublicHolidays' => 8,
    ];

    /**
     * @param iterable<OpeningHourSpecification> $specifications
     */
    public function build(iterable $specifications, ?DateTimeImmutable $now = null): OpeningHours
    {
        $now ??= new DateTimeImmutable();
        $today = $now->setTime(0, 0);

        // Bucket rows by their validity window. The key separates windows; an
        // open-ended window (both null) collapses under the empty key.
        $byWindow = [];
        foreach ($specifications as $specification) {
            $from = $specification->getValidFrom()?->setTime(0, 0);
            $through = $specification->getValidThrough()?->setTime(0, 0);

            // Drop windows that ended before today; they never display.
            if ($through !== null && $through < $today) {
                continue;
            }

            $key = ($from?->format('Y-m-d') ?? '') . '/' . ($through?->format('Y-m-d') ?? '');
            $byWindow[$key] ??= ['from' => $from, 'through' => $through, 'rows' => []];
            $byWindow[$key]['rows'][] = $specification;
        }

        $periods = [];
        foreach ($byWindow as $window) {
            $periods[] = new Period(
                $window['from'],
                $window['through'],
                $this->buildWeekDays($window['rows']),
                $this->windowContains($window['from'], $window['through'], $today),
            );
        }

        usort($periods, [$this, 'comparePeriods']);

        return new OpeningHours($periods, $this->isOpenNow($periods, $now));
    }

    /**
     * @param list<OpeningHourSpecification> $rows
     *
     * @return list<WeekDay>
     */
    protected function buildWeekDays(array $rows): array
    {
        // Scaffold all seven weekdays so a day without rows still renders as
        // closed instead of vanishing. PublicHolidays is NOT scaffolded — it is a
        // pseudo-weekday that only appears when special hours supply it.
        $periodsByDay = [
            'Monday' => [],
            'Tuesday' => [],
            'Wednesday' => [],
            'Thursday' => [],
            'Friday' => [],
            'Saturday' => [],
            'Sunday' => [],
        ];

        foreach ($rows as $row) {
            $opens = $row->getOpens();
            $closes = $row->getCloses();
            if ($opens === null || $closes === null) {
                continue;
            }
            $periodsByDay[$row->getDayOfWeek()][] = new TimePeriod($opens, $closes);
        }

        uksort($periodsByDay, [$this, 'compareWeekDays']);

        $weekDays = [];
        foreach ($periodsByDay as $dayOfWeek => $timePeriods) {
            usort($timePeriods, static fn (TimePeriod $a, TimePeriod $b): int => $a->getOpens() <=> $b->getOpens());
            $weekDays[] = new WeekDay((string)$dayOfWeek, $timePeriods);
        }

        return $weekDays;
    }

    protected function windowContains(?DateTimeImmutable $from, ?DateTimeImmutable $through, DateTimeImmutable $today): bool
    {
        return ($from === null || $from <= $today) && ($through === null || $through >= $today);
    }

    /**
     * @param list<Period> $periods
     */
    protected function isOpenNow(array $periods, DateTimeImmutable $now): bool
    {
        $weekday = $now->format('l');
        foreach ($periods as $period) {
            if (!$period->isCurrent()) {
                continue;
            }
            foreach ($period->getWeekDays() as $weekDay) {
                if ($weekDay->getDayOfWeek() !== $weekday) {
                    continue;
                }
                foreach ($weekDay->getTimePeriods() as $timePeriod) {
                    if ($this->timeWithin($timePeriod, $now)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    protected function timeWithin(TimePeriod $timePeriod, DateTimeImmutable $now): bool
    {
        $minutes = static fn (DateTimeImmutable $time): int => (int)$time->format('H') * 60 + (int)$time->format('i');
        $nowMinutes = (int)$now->format('H') * 60 + (int)$now->format('i');

        return $nowMinutes >= $minutes($timePeriod->getOpens()) && $nowMinutes < $minutes($timePeriod->getCloses());
    }

    protected function comparePeriods(Period $a, Period $b): int
    {
        // Current period(s) first, then upcoming ordered by start date.
        if ($a->isCurrent() !== $b->isCurrent()) {
            return $a->isCurrent() ? -1 : 1;
        }

        return ($a->getValidFrom()?->getTimestamp() ?? 0) <=> ($b->getValidFrom()?->getTimestamp() ?? 0);
    }

    protected function compareWeekDays(string $a, string $b): int
    {
        return (self::WEEKDAY_ORDER[$a] ?? 99) <=> (self::WEEKDAY_ORDER[$b] ?? 99);
    }
}
