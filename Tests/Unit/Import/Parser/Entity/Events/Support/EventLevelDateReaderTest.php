<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Unit\Import\Parser\Entity\Events\Support;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WerkraumMedia\ThueCat\Import\Parser\Entity\Events\Support\EventLevelDateReader;

// Values are the real ones from the fetched fixtures, so a change upstream
// surfaces here and not only in a functional test.
class EventLevelDateReaderTest extends TestCase
{
    private EventLevelDateReader $reader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reader = new EventLevelDateReader();
    }

    #[Test]
    public function readsOccurrenceFromFullDatetimes(): void
    {
        $occurrence = $this->reader->toOccurrence($this->node(
            start: '2026-12-01T08:00:00.000+01:00',
            end: '2026-12-01T10:00:00.000+01:00'
        ));

        self::assertNotNull($occurrence);
        self::assertSame('2026-12-01T08:00:00+01:00', $occurrence->start);
        self::assertSame('2026-12-01T10:00:00+01:00', $occurrence->end);
    }

    // Mirroring start into end is the schedule path's fallback for a missing
    // endTime; data stating its own end must not reach it.
    #[Test]
    public function keepsTheRealDurationRatherThanMirroringStart(): void
    {
        $occurrence = $this->reader->toOccurrence($this->node(
            start: '2026-12-01T08:00:00.000+01:00',
            end: '2026-12-01T10:00:00.000+01:00'
        ));

        self::assertNotNull($occurrence);
        self::assertNotSame($occurrence->start, $occurrence->end);
    }

    #[Test]
    public function preservesTheOffsetTheNodePublishes(): void
    {
        $occurrence = $this->reader->toOccurrence($this->node(
            start: '2026-08-30T17:00:00.000+02:00',
            end: '2026-08-30T19:00:00.000+02:00'
        ));

        self::assertNotNull($occurrence);
        self::assertSame('2026-08-30T17:00:00+02:00', $occurrence->start);
    }

    // In a Schedule this key bounds the repetition; here, the occurrence.
    #[Test]
    public function endDateBoundsTheOccurrenceNeverASeries(): void
    {
        $occurrence = $this->reader->toOccurrence($this->node(
            start: '2026-08-06T18:00:00.000+02:00',
            end: '2026-08-29T20:00:00.000+02:00'
        ));

        self::assertNotNull($occurrence);
        self::assertSame('2026-08-29T20:00:00+02:00', $occurrence->end);
    }

    #[Test]
    public function ignoresTimeKeysWhenPresent(): void
    {
        $node = $this->node(
            start: '2026-12-01T08:00:00.000+01:00',
            end: '2026-12-01T10:00:00.000+01:00'
        );
        $node['schema:startTime'] = ['@type' => 'schema:Time', '@value' => '19:30:00'];
        $node['schema:endTime'] = ['@type' => 'schema:Time', '@value' => '21:30:00'];

        $occurrence = $this->reader->toOccurrence($node);

        self::assertNotNull($occurrence);
        self::assertSame('2026-12-01T08:00:00+01:00', $occurrence->start);
        self::assertSame('2026-12-01T10:00:00+01:00', $occurrence->end);
    }

    #[Test]
    public function readsOccurrenceWhenTimeKeysAreAbsent(): void
    {
        $occurrence = $this->reader->toOccurrence($this->node(
            start: '2026-12-01T08:00:00.000+01:00',
            end: '2026-12-01T10:00:00.000+01:00'
        ));

        self::assertNotNull($occurrence);
        self::assertSame('2026-12-01T08:00:00+01:00', $occurrence->start);
    }

    #[Test]
    public function skipsWhenStartDateIsMissing(): void
    {
        $node = $this->node(start: null, end: '2026-12-01T10:00:00.000+01:00');

        self::assertNull($this->reader->toOccurrence($node));
    }

    #[Test]
    public function skipsWhenEndDateIsMissing(): void
    {
        $node = $this->node(start: '2026-12-01T08:00:00.000+01:00', end: null);

        self::assertNull($this->reader->toOccurrence($node));
    }

    // Standing in for the absent bound would invent a zero-duration occurrence.
    #[Test]
    public function doesNotMirrorThePresentBoundIntoTheAbsentOne(): void
    {
        $node = $this->node(start: '2026-12-01T08:00:00.000+01:00', end: null);

        self::assertNull($this->reader->toOccurrence($node));
    }

    // 00:00/23:59 is ext:events' all-day convention, not an invention here.
    #[Test]
    public function readsDateOnlyValuesAsAFullDay(): void
    {
        $occurrence = $this->reader->toOccurrence(
            $this->node(start: '2026-12-01', end: '2026-12-01', type: 'schema:Date')
        );

        self::assertNotNull($occurrence);
        self::assertSame('2026-12-01T00:00:00+01:00', $occurrence->start);
        self::assertSame('2026-12-01T23:59:00+01:00', $occurrence->end);
    }

    #[Test]
    public function readsAFullDayRangeSpanningSeveralDays(): void
    {
        $occurrence = $this->reader->toOccurrence(
            $this->node(start: '2026-12-08', end: '2026-12-10', type: 'schema:Date')
        );

        self::assertNotNull($occurrence);
        self::assertSame('2026-12-08T00:00:00+01:00', $occurrence->start);
        self::assertSame('2026-12-10T23:59:00+01:00', $occurrence->end);
    }

    // Offsetless values are German local time, so summer dates carry +02:00.
    #[Test]
    public function readsAFullDayInSummerWithItsOwnOffset(): void
    {
        $occurrence = $this->reader->toOccurrence(
            $this->node(start: '2026-08-06', end: '2026-08-06', type: 'schema:Date')
        );

        self::assertNotNull($occurrence);
        self::assertSame('2026-08-06T00:00:00+02:00', $occurrence->start);
    }

    // A published midnight is a real time, not a missing one.
    #[Test]
    public function keepsAnExplicitMidnightStart(): void
    {
        $occurrence = $this->reader->toOccurrence($this->node(
            start: '2026-12-01T00:00:00.000+01:00',
            end: '2026-12-01T10:00:00.000+01:00'
        ));

        self::assertNotNull($occurrence);
        self::assertSame('2026-12-01T00:00:00+01:00', $occurrence->start);
    }

    #[Test]
    public function skipsWhenNoDateKeysArePresentAtAll(): void
    {
        self::assertNull($this->reader->toOccurrence([]));
    }

    /**
     * @return array<string, mixed>
     */
    private function node(?string $start, ?string $end, string $type = 'schema:DateTime'): array
    {
        $node = [];
        if ($start !== null) {
            $node['schema:startDate'] = ['@type' => $type, '@value' => $start];
        }
        if ($end !== null) {
            $node['schema:endDate'] = ['@type' => $type, '@value' => $end];
        }
        return $node;
    }
}
