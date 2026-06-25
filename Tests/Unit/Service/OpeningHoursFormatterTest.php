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
        $result = $this->subject->build([]);

        self::assertTrue($result->isEmpty());
        self::assertSame([], $result->getPeriods());
        self::assertFalse($result->isOpenNow());
    }

    #[Test]
    public function keepsBothTimePeriodsOfALunchBreakUnderOneWeekday(): void
    {
        // The #10902 case: Monday 08:00–12:00 AND 13:00–18:00 must both land
        // under Monday, ordered by start, not collapsed or mis-paired.
        $result = $this->subject->build([
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
        $result = $this->subject->build([
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
        $result = $this->subject->build([
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
        $result = $this->subject->build([
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
        $result = $this->subject->build([
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
        $result = $this->subject->build([
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
        $result = $this->subject->build([
            $this->specification('regular', 'Monday', '08:00', '18:00'),
        ], $now);

        self::assertTrue($result->isOpenNow());
    }

    #[Test]
    public function reportsClosedWhenCurrentTimeIsOutsideEverySpan(): void
    {
        // Monday 2026-06-15 12:30 → in the lunch gap between the two spans.
        $now = new DateTimeImmutable('2026-06-15 12:30');
        $result = $this->subject->build([
            $this->specification('regular', 'Monday', '08:00', '12:00'),
            $this->specification('regular', 'Monday', '13:00', '18:00'),
        ], $now);

        self::assertFalse($result->isOpenNow());
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
