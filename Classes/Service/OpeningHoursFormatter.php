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
use WerkraumMedia\ThueCat\Domain\Model\Frontend\OpeningHours\DayRange;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\OpeningHours\MergedByWeekday;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\OpeningHours\MergedPeriod;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\OpeningHours\PerDayTable;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\OpeningHours\Period;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\OpeningHours\PeriodInterface;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\OpeningHours\TimePeriod;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\OpeningHours\WeekDay;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\OpeningHours\WeekDayGroup;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\OpeningHourSpecification;

/**
 * Factory turning the flat tx_thuecat_opening_hours rows (one weekday + one
 * open/close span each) into display-ready format DTOs. Each output format has
 * its own build method + DTO + Fluid partial sharing one name (e.g.
 * buildPerDayTable() -> PerDayTable -> PerDayTable partial); adding a format
 * means adding that triad, not reshaping the others.
 *
 * Shared across formats: rows are grouped into validity periods, past periods
 * dropped, the period containing today marked current (drives open-now). All
 * compute lives here so templates request shapes without re-importing.
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
    public function buildPerDayTable(iterable $specifications, ?DateTimeImmutable $now = null): PerDayTable
    {
        $now ??= new DateTimeImmutable();
        $today = $now->setTime(0, 0);

        $windows = $this->groupByWindow($specifications, $today);

        $periods = [];
        foreach ($windows as $window) {
            $periods[] = new Period(
                $window['from'],
                $window['through'],
                $this->buildWeekDays($window['rows']),
                $this->windowContains($window['from'], $window['through'], $today),
            );
        }

        usort($periods, [$this, 'comparePeriods']);

        return new PerDayTable($periods, $this->isOpenNow($windows, $now, $today));
    }

    /**
     * @param iterable<OpeningHourSpecification> $specifications
     */
    public function buildMergedByWeekday(iterable $specifications, ?DateTimeImmutable $now = null): MergedByWeekday
    {
        $now ??= new DateTimeImmutable();
        $today = $now->setTime(0, 0);

        $windows = $this->groupByWindow($specifications, $today);

        $periods = [];
        foreach ($windows as $window) {
            $periods[] = new MergedPeriod(
                $window['from'],
                $window['through'],
                $this->buildWeekDayGroups($this->buildWeekDays($window['rows'])),
                $this->windowContains($window['from'], $window['through'], $today),
            );
        }

        usort($periods, [$this, 'comparePeriods']);

        return new MergedByWeekday($periods, $this->isOpenNow($windows, $now, $today));
    }

    /**
     * Bucket rows by their validity window, shared by every format. The key
     * separates windows; an open-ended window (both null) collapses under the
     * empty key. Windows that ended before today are dropped — they never display.
     *
     * @param iterable<OpeningHourSpecification> $specifications
     *
     * @return list<array{from: ?DateTimeImmutable, through: ?DateTimeImmutable, rows: list<OpeningHourSpecification>}>
     */
    protected function groupByWindow(iterable $specifications, DateTimeImmutable $today): array
    {
        $byWindow = [];
        foreach ($specifications as $specification) {
            $from = $specification->getValidFrom()?->setTime(0, 0);
            $through = $specification->getValidThrough()?->setTime(0, 0);

            if ($through !== null && $through < $today) {
                continue;
            }

            $key = ($from?->format('Y-m-d') ?? '') . '/' . ($through?->format('Y-m-d') ?? '');
            $byWindow[$key] ??= ['from' => $from, 'through' => $through, 'rows' => []];
            $byWindow[$key]['rows'][] = $specification;
        }

        return array_values($byWindow);
    }

    /**
     * Collapse per-day weekdays into groups sharing the identical set of time
     * periods (the merged format). Closed days produce no group. PublicHolidays
     * always forms its own group regardless of matching spans; the input order
     * (Monday first, PublicHolidays last) carries through to the groups.
     *
     * @param list<WeekDay> $weekDays
     *
     * @return list<WeekDayGroup>
     */
    protected function buildWeekDayGroups(array $weekDays): array
    {
        $groups = [];
        foreach ($weekDays as $weekDay) {
            if ($weekDay->isClosed()) {
                continue;
            }

            $key = $this->spanKey($weekDay->getTimePeriods());
            // PublicHolidays must never merge with a weekday group sharing the
            // same spans, so give it a key nothing else can match.
            if ($weekDay->getDayOfWeek() === 'PublicHolidays') {
                $key = 'PublicHolidays/' . $key;
            }

            if (isset($groups[$key])) {
                $groups[$key]['days'][] = $weekDay->getDayOfWeek();
                continue;
            }
            $groups[$key] = ['days' => [$weekDay->getDayOfWeek()], 'spans' => $weekDay->getTimePeriods()];
        }

        return array_values(array_map(
            fn (array $group): WeekDayGroup => new WeekDayGroup(
                $group['days'],
                $this->collapseToRanges($group['days']),
                $group['spans'],
            ),
            $groups
        ));
    }

    /**
     * Collapse consecutive weekdays (by WEEKDAY_ORDER) into ranges; a day with no
     * adjacent neighbour in the list stays a standalone range (first === last).
     * The days arrive already in canonical order. PublicHolidays has no order
     * neighbour, so it always yields a standalone range.
     *
     * @param list<string> $daysOfWeek
     *
     * @return list<DayRange>
     */
    protected function collapseToRanges(array $daysOfWeek): array
    {
        // Split into runs of consecutive days (by WEEKDAY_ORDER), then turn each
        // run into a range from its first to its last day. A lone day yields a
        // run of one (first === last). PublicHolidays has no order neighbour, so
        // it always starts a new run.
        $runs = [];
        foreach ($daysOfWeek as $day) {
            $order = self::WEEKDAY_ORDER[$day] ?? null;
            $previousRun = $runs === [] ? null : $runs[count($runs) - 1];
            $previousOrder = $previousRun === null
                ? null
                : self::WEEKDAY_ORDER[$previousRun[count($previousRun) - 1]] ?? null;

            if ($order !== null && $previousOrder !== null && $order === $previousOrder + 1) {
                $runs[count($runs) - 1][] = $day;
                continue;
            }
            $runs[] = [$day];
        }

        return array_map(
            static fn (array $run): DayRange => new DayRange($run[0], $run[count($run) - 1]),
            $runs
        );
    }

    /**
     * Stable identity for a set of time periods so two weekdays with the same
     * opening hours share a group.
     *
     * @param list<TimePeriod> $timePeriods
     */
    protected function spanKey(array $timePeriods): string
    {
        return implode('|', array_map(
            static fn (TimePeriod $period): string => $period->getOpens()->format('H:i') . '-' . $period->getCloses()->format('H:i'),
            $timePeriods
        ));
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
     * Open-now is a property of the raw current-window spans, independent of the
     * presentation shape, so it is computed from the shared windows and reused by
     * every format. True when today's weekday has a span covering $now in any
     * window containing today.
     *
     * @param list<array{from: ?DateTimeImmutable, through: ?DateTimeImmutable, rows: list<OpeningHourSpecification>}> $windows
     */
    protected function isOpenNow(array $windows, DateTimeImmutable $now, DateTimeImmutable $today): bool
    {
        $weekday = $now->format('l');
        foreach ($windows as $window) {
            if (!$this->windowContains($window['from'], $window['through'], $today)) {
                continue;
            }
            foreach ($window['rows'] as $row) {
                if ($row->getDayOfWeek() !== $weekday) {
                    continue;
                }
                $opens = $row->getOpens();
                $closes = $row->getCloses();
                if ($opens !== null && $closes !== null && $this->timeWithin(new TimePeriod($opens, $closes), $now)) {
                    return true;
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

    protected function comparePeriods(PeriodInterface $a, PeriodInterface $b): int
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
