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

namespace WerkraumMedia\ThueCat\Tests\Unit\Service;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\OpeningHourSpecification;
use WerkraumMedia\ThueCat\Service\OpeningHoursFormatter;

class OpeningHoursFormatterTest extends TestCase
{
    private OpeningHoursFormatter $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new OpeningHoursFormatter();
    }

    #[Test]
    public function emptyInputProducesEmptyResult(): void
    {
        $result = $this->subject->buildPerDayTable([]);

        self::assertTrue($result->isEmpty());
        self::assertSame([], $result->getPeriods());
        self::assertFalse($result->isOpenNow());
    }

    #[Test]
    public function keepsBothTimePeriodsOfALunchBreakUnderOneWeekday(): void
    {
        // The #10902 case: Monday 08:00–12:00 AND 13:00–18:00 must both land
        // under Monday, ordered by start, not collapsed or mis-paired.
        $result = $this->subject->buildPerDayTable([
            $this->specification('regular', 'Monday', '13:00', '18:00'),
            $this->specification('regular', 'Monday', '08:00', '12:00'),
        ]);

        // Monday sorts first; the other six scaffolded days follow as closed.
        $weekDays = $result->getPeriods()[0]->getWeekDays();
        self::assertSame('Monday', $weekDays[0]->getDayOfWeek());

        $timePeriods = $weekDays[0]->getTimePeriods();
        self::assertCount(2, $timePeriods);
        self::assertSame('08:00', $timePeriods[0]->getOpens()->format('H:i'));
        self::assertSame('12:00', $timePeriods[0]->getCloses()->format('H:i'));
        self::assertSame('13:00', $timePeriods[1]->getOpens()->format('H:i'));
        self::assertSame('18:00', $timePeriods[1]->getCloses()->format('H:i'));
    }

    #[Test]
    public function emitsAllSevenWeekdaysMarkingDaysWithoutRowsClosed(): void
    {
        // A place open only Monday must still list Tue–Sun as closed, so the
        // table shows every weekday (the production gap: missing days vanished).
        $result = $this->subject->buildPerDayTable([
            $this->specification('regular', 'Monday', '08:00', '18:00'),
        ]);

        $weekDays = $result->getPeriods()[0]->getWeekDays();
        $byDay = [];
        foreach ($weekDays as $weekDay) {
            $byDay[$weekDay->getDayOfWeek()] = $weekDay;
        }

        self::assertSame(
            ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
            array_keys($byDay)
        );
        self::assertFalse($byDay['Monday']->isClosed());
        self::assertTrue($byDay['Tuesday']->isClosed());
        self::assertTrue($byDay['Sunday']->isClosed());
    }

    #[Test]
    public function doesNotInjectPublicHolidaysWhenNoSuchRowExists(): void
    {
        // PublicHolidays is a pseudo-weekday; scaffold only the 7 real days.
        $result = $this->subject->buildPerDayTable([
            $this->specification('regular', 'Monday', '08:00', '18:00'),
        ]);

        $days = array_map(
            static fn ($weekDay): string => $weekDay->getDayOfWeek(),
            $result->getPeriods()[0]->getWeekDays()
        );

        self::assertNotContains('PublicHolidays', $days);
    }

    #[Test]
    public function ordersWeekdaysMondayFirstWithPublicHolidaysLast(): void
    {
        $result = $this->subject->buildPerDayTable([
            $this->specification('regular', 'PublicHolidays', '10:00', '14:00'),
            $this->specification('regular', 'Sunday', '10:00', '14:00'),
            $this->specification('regular', 'Monday', '08:00', '18:00'),
            $this->specification('regular', 'Wednesday', '08:00', '18:00'),
        ]);

        $days = array_map(
            static fn ($weekDay): string => $weekDay->getDayOfWeek(),
            $result->getPeriods()[0]->getWeekDays()
        );

        // All 7 weekdays are scaffolded Monday-first; PublicHolidays sorts last.
        self::assertSame(
            ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday', 'PublicHolidays'],
            $days
        );
    }

    #[Test]
    public function dropsPeriodsThatEndedBeforeToday(): void
    {
        $now = new DateTimeImmutable('2026-06-16');
        $result = $this->subject->buildPerDayTable([
            $this->specification('regular', 'Monday', '08:00', '18:00', '2020-01-01', '2020-12-31'),
            $this->specification('regular', 'Monday', '09:00', '17:00', '2026-01-01', '2026-12-31'),
        ], $now);

        self::assertCount(1, $result->getPeriods());
        self::assertSame('2026-01-01', $result->getPeriods()[0]->getValidFrom()?->format('Y-m-d'));
    }

    #[Test]
    public function ordersCurrentPeriodBeforeFuturePeriod(): void
    {
        $now = new DateTimeImmutable('2026-06-16');
        $result = $this->subject->buildPerDayTable([
            $this->specification('regular', 'Monday', '10:00', '16:00', '2026-11-02', '2027-03-25'),
            $this->specification('regular', 'Monday', '08:00', '20:00', '2026-01-01', '2026-11-01'),
        ], $now);

        $periods = $result->getPeriods();
        self::assertCount(2, $periods);
        self::assertTrue($periods[0]->isCurrent());
        self::assertSame('2026-01-01', $periods[0]->getValidFrom()?->format('Y-m-d'));
        self::assertFalse($periods[1]->isCurrent());
        self::assertSame('2026-11-02', $periods[1]->getValidFrom()?->format('Y-m-d'));
    }

    #[Test]
    public function reportsOpenNowWhenCurrentTimeFallsInACurrentPeriodSpan(): void
    {
        // Monday 2026-06-15 11:00 → within Monday 08:00–18:00.
        $now = new DateTimeImmutable('2026-06-15 11:00');
        $result = $this->subject->buildPerDayTable([
            $this->specification('regular', 'Monday', '08:00', '18:00'),
        ], $now);

        self::assertTrue($result->isOpenNow());
    }

    #[Test]
    public function reportsClosedWhenCurrentTimeIsOutsideEverySpan(): void
    {
        // Monday 2026-06-15 12:30 → in the lunch gap between the two spans.
        $now = new DateTimeImmutable('2026-06-15 12:30');
        $result = $this->subject->buildPerDayTable([
            $this->specification('regular', 'Monday', '08:00', '12:00'),
            $this->specification('regular', 'Monday', '13:00', '18:00'),
        ], $now);

        self::assertFalse($result->isOpenNow());
    }

    #[Test]
    public function mergedByWeekdayGroupsConsecutiveDaysSharingTheSameSpans(): void
    {
        // Shape 1: Mon–Fri 08:00–12:00 & 13:00–19:00, Sat & Sun 08:00–14:00.
        // Mon–Fri share an identical two-span set → one group; Sat+Sun share a
        // single-span set → one group. Two groups, ordered by earliest day.
        $result = $this->subject->buildMergedByWeekday([
            $this->specification('regular', 'Monday', '08:00', '12:00'),
            $this->specification('regular', 'Monday', '13:00', '19:00'),
            $this->specification('regular', 'Tuesday', '08:00', '12:00'),
            $this->specification('regular', 'Tuesday', '13:00', '19:00'),
            $this->specification('regular', 'Wednesday', '08:00', '12:00'),
            $this->specification('regular', 'Wednesday', '13:00', '19:00'),
            $this->specification('regular', 'Thursday', '08:00', '12:00'),
            $this->specification('regular', 'Thursday', '13:00', '19:00'),
            $this->specification('regular', 'Friday', '08:00', '12:00'),
            $this->specification('regular', 'Friday', '13:00', '19:00'),
            $this->specification('regular', 'Saturday', '08:00', '14:00'),
            $this->specification('regular', 'Sunday', '08:00', '14:00'),
        ]);

        $groups = $result->getPeriods()[0]->getWeekDayGroups();
        self::assertCount(2, $groups);

        self::assertSame(
            ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
            $groups[0]->getDaysOfWeek()
        );
        $spans = $groups[0]->getTimePeriods();
        self::assertCount(2, $spans);
        self::assertSame('08:00', $spans[0]->getOpens()->format('H:i'));
        self::assertSame('12:00', $spans[0]->getCloses()->format('H:i'));
        self::assertSame('13:00', $spans[1]->getOpens()->format('H:i'));
        self::assertSame('19:00', $spans[1]->getCloses()->format('H:i'));

        self::assertSame(['Saturday', 'Sunday'], $groups[1]->getDaysOfWeek());
        self::assertSame('08:00', $groups[1]->getTimePeriods()[0]->getOpens()->format('H:i'));
        self::assertSame('14:00', $groups[1]->getTimePeriods()[0]->getCloses()->format('H:i'));
    }

    #[Test]
    public function mergedByWeekdayGroupsNonAdjacentDaysAndOmitsClosedDays(): void
    {
        // Shape 2: Mon, Wed, Fri 08:00–12:00; Sat 08:00–18:00. Tue/Thu/Sun are
        // closed and must not appear at all. The three same-span days group even
        // though they are not adjacent; Sat differs → own group.
        $result = $this->subject->buildMergedByWeekday([
            $this->specification('regular', 'Monday', '08:00', '12:00'),
            $this->specification('regular', 'Wednesday', '08:00', '12:00'),
            $this->specification('regular', 'Friday', '08:00', '12:00'),
            $this->specification('regular', 'Saturday', '08:00', '18:00'),
        ]);

        $groups = $result->getPeriods()[0]->getWeekDayGroups();
        self::assertCount(2, $groups);

        self::assertSame(['Monday', 'Wednesday', 'Friday'], $groups[0]->getDaysOfWeek());
        self::assertSame(['Saturday'], $groups[1]->getDaysOfWeek());

        $allDays = array_merge(...array_map(
            static fn ($group): array => $group->getDaysOfWeek(),
            $groups
        ));
        self::assertNotContains('Tuesday', $allDays);
        self::assertNotContains('Thursday', $allDays);
        self::assertNotContains('Sunday', $allDays);
    }

    #[Test]
    public function mergedByWeekdayKeepsPublicHolidaysInTheirOwnGroupSortedLast(): void
    {
        // Shape 3: Mon–Fri 09:00–18:00 and PublicHolidays 09:00–18:00 — identical
        // spans, yet PublicHolidays must NOT merge into the weekday group and must
        // sort last.
        $result = $this->subject->buildMergedByWeekday([
            $this->specification('regular', 'Monday', '09:00', '18:00'),
            $this->specification('regular', 'Tuesday', '09:00', '18:00'),
            $this->specification('regular', 'Wednesday', '09:00', '18:00'),
            $this->specification('regular', 'Thursday', '09:00', '18:00'),
            $this->specification('regular', 'Friday', '09:00', '18:00'),
            $this->specification('regular', 'PublicHolidays', '09:00', '18:00'),
        ]);

        $groups = $result->getPeriods()[0]->getWeekDayGroups();
        self::assertCount(2, $groups);

        self::assertSame(
            ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
            $groups[0]->getDaysOfWeek()
        );
        self::assertSame(['PublicHolidays'], $groups[1]->getDaysOfWeek());
    }

    #[Test]
    public function weekDayGroupCollapsesConsecutiveRunsIntoRanges(): void
    {
        // Same data as shape 1: Mon–Fri share spans (one consecutive run →
        // Monday–Friday) and Sat+Sun share spans (a second run → Saturday–Sunday).
        $result = $this->subject->buildMergedByWeekday([
            $this->specification('regular', 'Monday', '08:00', '12:00'),
            $this->specification('regular', 'Monday', '13:00', '19:00'),
            $this->specification('regular', 'Tuesday', '08:00', '12:00'),
            $this->specification('regular', 'Tuesday', '13:00', '19:00'),
            $this->specification('regular', 'Wednesday', '08:00', '12:00'),
            $this->specification('regular', 'Wednesday', '13:00', '19:00'),
            $this->specification('regular', 'Thursday', '08:00', '12:00'),
            $this->specification('regular', 'Thursday', '13:00', '19:00'),
            $this->specification('regular', 'Friday', '08:00', '12:00'),
            $this->specification('regular', 'Friday', '13:00', '19:00'),
            $this->specification('regular', 'Saturday', '08:00', '14:00'),
            $this->specification('regular', 'Sunday', '08:00', '14:00'),
        ]);

        $groups = $result->getPeriods()[0]->getWeekDayGroups();

        $weekRanges = $groups[0]->getDayRanges();
        self::assertCount(1, $weekRanges);
        self::assertTrue($weekRanges[0]->isRange());
        self::assertSame('Monday', $weekRanges[0]->getFirstDay());
        self::assertSame('Friday', $weekRanges[0]->getLastDay());

        $weekendRanges = $groups[1]->getDayRanges();
        self::assertCount(1, $weekendRanges);
        self::assertTrue($weekendRanges[0]->isRange());
        self::assertSame('Saturday', $weekendRanges[0]->getFirstDay());
        self::assertSame('Sunday', $weekendRanges[0]->getLastDay());
    }

    #[Test]
    public function weekDayGroupKeepsNonConsecutiveDaysAsStandaloneRanges(): void
    {
        // Same data as shape 2: Mon, Wed, Fri share spans but are not
        // consecutive → three standalone ranges (first == last), not one range.
        $result = $this->subject->buildMergedByWeekday([
            $this->specification('regular', 'Monday', '08:00', '12:00'),
            $this->specification('regular', 'Wednesday', '08:00', '12:00'),
            $this->specification('regular', 'Friday', '08:00', '12:00'),
            $this->specification('regular', 'Saturday', '08:00', '18:00'),
        ]);

        $ranges = $result->getPeriods()[0]->getWeekDayGroups()[0]->getDayRanges();
        self::assertCount(3, $ranges);
        foreach ($ranges as $range) {
            self::assertFalse($range->isRange());
        }
        self::assertSame(
            ['Monday', 'Wednesday', 'Friday'],
            array_map(static fn ($range): string => $range->getFirstDay(), $ranges)
        );
    }

    #[Test]
    public function weekDayGroupGivesPublicHolidaysASingleStandaloneRange(): void
    {
        // Same data as shape 3: Mon–Fri collapse to one range; PublicHolidays
        // never joins a run, so its own group is a single standalone range.
        $result = $this->subject->buildMergedByWeekday([
            $this->specification('regular', 'Monday', '09:00', '18:00'),
            $this->specification('regular', 'Tuesday', '09:00', '18:00'),
            $this->specification('regular', 'Wednesday', '09:00', '18:00'),
            $this->specification('regular', 'Thursday', '09:00', '18:00'),
            $this->specification('regular', 'Friday', '09:00', '18:00'),
            $this->specification('regular', 'PublicHolidays', '09:00', '18:00'),
        ]);

        $groups = $result->getPeriods()[0]->getWeekDayGroups();

        self::assertCount(1, $groups[0]->getDayRanges());
        self::assertTrue($groups[0]->getDayRanges()[0]->isRange());

        $holidayRanges = $groups[1]->getDayRanges();
        self::assertCount(1, $holidayRanges);
        self::assertFalse($holidayRanges[0]->isRange());
        self::assertSame('PublicHolidays', $holidayRanges[0]->getFirstDay());
    }

    private function specification(
        string $type,
        string $dayOfWeek,
        string $opens,
        string $closes,
        ?string $validFrom = null,
        ?string $validThrough = null,
    ): OpeningHourSpecification {
        $specification = new OpeningHourSpecification();
        $specification->_setProperty('specificationType', $type);
        $specification->_setProperty('dayOfWeek', $dayOfWeek);
        $specification->_setProperty('opens', new DateTimeImmutable('1970-01-01 ' . $opens));
        $specification->_setProperty('closes', new DateTimeImmutable('1970-01-01 ' . $closes));
        $specification->_setProperty('validFrom', $validFrom === null ? null : new DateTimeImmutable($validFrom));
        $specification->_setProperty('validThrough', $validThrough === null ? null : new DateTimeImmutable($validThrough));

        return $specification;
    }
}
