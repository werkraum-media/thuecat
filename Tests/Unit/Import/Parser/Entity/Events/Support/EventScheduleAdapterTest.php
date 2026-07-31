<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Unit\Import\Parser\Entity\Events\Support;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WerkraumMedia\ThueCat\Import\Parser\Entity\Events\Support\EventScheduleAdapter;

class EventScheduleAdapterTest extends TestCase
{
    private EventScheduleAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adapter = new EventScheduleAdapter();
    }

    #[Test]
    public function keepsWeekdaysAndDropsUnusableValue(): void
    {
        $intervals = $this->adapter->toTimeIntervals(
            $this->weeklySchedule(['schema:Thursday', 'schema:PublicHolidays'])
        );

        self::assertSame(['Thursday'], $intervals[0]['weekdays']);
    }

    #[Test]
    public function dropsUnusableValueBeforeAUsableOne(): void
    {
        $intervals = $this->adapter->toTimeIntervals(
            $this->weeklySchedule(['schema:PublicHolidays', 'schema:Thursday'])
        );

        self::assertSame(['Thursday'], $intervals[0]['weekdays']);
    }

    #[Test]
    public function dropsUnusableValueBetweenUsableOnes(): void
    {
        $intervals = $this->adapter->toTimeIntervals(
            $this->weeklySchedule(['schema:Monday', 'schema:PublicHolidays', 'schema:Friday'])
        );

        self::assertSame(['Monday', 'Friday'], $intervals[0]['weekdays']);
    }

    #[Test]
    public function dropsSeveralUnusableValues(): void
    {
        $intervals = $this->adapter->toTimeIntervals(
            $this->weeklySchedule([
                'schema:PublicHolidays',
                'schema:Tuesday',
                'thuecat:SomethingElse',
                'schema:Saturday',
            ])
        );

        self::assertSame(['Tuesday', 'Saturday'], $intervals[0]['weekdays']);
    }

    #[Test]
    public function yieldsNoWeekdaysWhenEveryValueIsUnusable(): void
    {
        $intervals = $this->adapter->toTimeIntervals(
            $this->weeklySchedule(['schema:PublicHolidays'])
        );

        self::assertSame([], $intervals[0]['weekdays']);
    }

    #[Test]
    public function keepsEveryWeekdayWhenAllAreUsable(): void
    {
        $intervals = $this->adapter->toTimeIntervals(
            $this->weeklySchedule(['schema:Monday', 'schema:Friday'])
        );

        self::assertSame(['Monday', 'Friday'], $intervals[0]['weekdays']);
    }

    #[Test]
    public function monthlySelectsAUsableDayRegardlessOfPosition(): void
    {
        $intervals = $this->adapter->toTimeIntervals(
            $this->monthlySchedule(['schema:PublicHolidays', 'schema:Thursday'])
        );

        self::assertSame('Thursday', $intervals[0]['weekday']);
    }

    #[Test]
    public function monthlyYieldsNoDayWhenNoneIsUsable(): void
    {
        $intervals = $this->adapter->toTimeIntervals(
            $this->monthlySchedule(['schema:PublicHolidays'])
        );

        self::assertSame('', $intervals[0]['weekday']);
    }

    #[Test]
    public function reportsUnusableDays(): void
    {
        $schedule = $this->weeklySchedule(['schema:Thursday', 'schema:PublicHolidays']);

        self::assertSame(['PublicHolidays'], $this->adapter->toUnusableDays($schedule));
    }

    #[Test]
    public function reportsNoUnusableDaysWhenEveryValueIsAWeekday(): void
    {
        $schedule = $this->weeklySchedule(['schema:Monday', 'schema:Friday']);

        self::assertSame([], $this->adapter->toUnusableDays($schedule));
    }

    #[Test]
    public function reportsMonthlyDaysBeyondTheOneCarried(): void
    {
        $schedule = $this->monthlySchedule(['schema:Monday', 'schema:Thursday', 'schema:Friday']);

        self::assertSame(['Thursday', 'Friday'], $this->adapter->toDroppedDays($schedule));
    }

    #[Test]
    public function reportsNoDroppedDaysForASingleMonthlyDay(): void
    {
        $schedule = $this->monthlySchedule(['schema:Monday']);

        self::assertSame([], $this->adapter->toDroppedDays($schedule));
    }

    #[Test]
    public function reportsNoDroppedDaysForAWeeklySchedule(): void
    {
        $schedule = $this->weeklySchedule(['schema:Monday', 'schema:Friday']);

        self::assertSame([], $this->adapter->toDroppedDays($schedule));
    }

    #[Test]
    public function readsASingleExcludedDate(): void
    {
        $schedule = $this->weeklySchedule(['schema:Thursday']) + [
            'schema:exceptDate' => ['@type' => 'schema:Date', '@value' => '2026-12-17'],
        ];

        self::assertSame(['2026-12-17'], $this->adapter->toExcludedDates($schedule));
    }

    #[Test]
    public function readsSeveralExcludedDates(): void
    {
        $schedule = $this->weeklySchedule(['schema:Thursday']) + [
            'schema:exceptDate' => [
                ['@type' => 'schema:Date', '@value' => '2026-12-17'],
                ['@type' => 'schema:Date', '@value' => '2026-12-24'],
            ],
        ];

        self::assertSame(['2026-12-17', '2026-12-24'], $this->adapter->toExcludedDates($schedule));
    }

    #[Test]
    public function yieldsNoExcludedDatesWhenNoneAreDeclared(): void
    {
        self::assertSame([], $this->adapter->toExcludedDates($this->weeklySchedule(['schema:Thursday'])));
    }

    #[Test]
    public function collectsExcludedDatesAcrossSeveralSchedules(): void
    {
        $schedules = [
            $this->weeklySchedule(['schema:Thursday']) + [
                'schema:exceptDate' => ['@type' => 'schema:Date', '@value' => '2026-12-17'],
            ],
            $this->weeklySchedule(['schema:Monday']) + [
                'schema:exceptDate' => ['@type' => 'schema:Date', '@value' => '2026-12-21'],
            ],
        ];

        self::assertSame(['2026-12-17', '2026-12-21'], $this->adapter->toExcludedDates($schedules));
    }

    #[Test]
    public function excludedDatesLeaveTheIntervalMapUntouched(): void
    {
        $schedule = $this->weeklySchedule(['schema:Thursday']) + [
            'schema:exceptDate' => ['@type' => 'schema:Date', '@value' => '2026-12-17'],
        ];

        self::assertArrayNotHasKey('exceptDate', $this->adapter->toTimeIntervals($schedule)[0]);
    }

    /**
     * @param list<string> $days
     *
     * @return array<string, mixed>
     */
    private function weeklySchedule(array $days): array
    {
        return $this->schedule('P1W', $days);
    }

    /**
     * @param list<string> $days
     *
     * @return array<string, mixed>
     */
    private function monthlySchedule(array $days): array
    {
        return $this->schedule('P1M', $days) + [
            'schema:byMonthWeek' => ['@type' => 'schema:Integer', '@value' => '2'],
        ];
    }

    /**
     * @param list<string> $days
     *
     * @return array<string, mixed>
     */
    private function schedule(string $frequency, array $days): array
    {
        return [
            '@type' => ['schema:Schedule'],
            'schema:frequency' => ['@type' => 'schema:Duration', '@value' => $frequency],
            'schema:startDate' => ['@type' => 'schema:Date', '@value' => '2026-12-03'],
            'schema:endDate' => ['@type' => 'schema:Date', '@value' => '2026-12-31'],
            'schema:startTime' => ['@type' => 'schema:Time', '@value' => '14:00:00'],
            'schema:byDay' => array_map(
                static fn (string $day): array => ['@type' => 'schema:DayOfWeek', '@value' => $day],
                $days
            ),
        ];
    }
}
