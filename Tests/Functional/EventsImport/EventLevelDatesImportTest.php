<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional\EventsImport;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\DateTimeAspect;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractImportTestCase;

// Events publishing their dates on the event node instead of in a schedule.
// Every fixture here is a real upstream document, trimmed.
class EventLevelDatesImportTest extends AbstractImportTestCase
{
    protected array $testExtensionsToLoad = [
        'werkraummedia/thuecat/',
        'werkraummedia/events/',
    ];

    protected string $fixtureGuzzleBase = __DIR__ . '/Fixtures/Guzzle';
    protected string $fixtureDomain = 'cdb.int.thuecat.org';
    protected string $fixturePath = 'api/resources';

    private const TDM = 'https://thuecat.org/resources/e_19c21523-343f-4b72-b13c-756ac4bde8c5-';
    private const MIXED = 'https://thuecat.org/resources/e_349b798696b77c81995d71a99b4460d1-kcev';

    protected function setUp(): void
    {
        parent::setUp();

        $this->getContainer()->get(Context::class)->setAspect(
            'date',
            new DateTimeAspect(new DateTimeImmutable('2026-01-01T00:00:00+00:00'))
        );
    }

    #[Test]
    public function anEventPublishingItsDatesOnTheNodeIsImportedWithThem(): void
    {
        $this->importEventLevelEvents();

        self::assertSame(
            [['start' => '2026-12-01T08:00:00+01:00', 'end' => '2026-12-01T10:00:00+01:00']],
            $this->datesOf(self::TDM . 'tdm')
        );
    }

    // Would previously have imported with zero dates.
    #[Test]
    public function itIsNoLongerReportedAsHavingNoDates(): void
    {
        $this->importEventLevelEvents();

        $reported = array_column($this->getLogEntriesOfType('eventWithoutDates'), 'remote_id');

        self::assertNotContains(self::TDM . 'tdm', $reported);
    }

    #[Test]
    public function theOccurrenceKeepsItsRealDuration(): void
    {
        $this->importEventLevelEvents();

        $dates = $this->datesOf(self::TDM . 'tdm');

        self::assertNotSame($dates[0]['start'], $dates[0]['end']);
    }

    #[Test]
    public function timeKeysAreIgnoredWhenAbsent(): void
    {
        $this->importEventLevelEvents();

        self::assertSame(
            [['start' => '2026-12-01T08:00:00+01:00', 'end' => '2026-12-01T10:00:00+01:00']],
            $this->datesOf(self::TDM . 'notimes')
        );
    }

    // The time keys say 19:30/21:30, the datetimes say 08:00/10:00.
    #[Test]
    public function timeKeysAreIgnoredWhenTheyDisagreeWithTheDatetimes(): void
    {
        $this->importEventLevelEvents();

        self::assertSame(
            [['start' => '2026-12-01T08:00:00+01:00', 'end' => '2026-12-01T10:00:00+01:00']],
            $this->datesOf(self::TDM . 'timedisagree')
        );
    }

    #[Test]
    public function datesWithoutATimeOfDayCoverWholeDays(): void
    {
        $this->importEventLevelEvents();

        self::assertSame(
            [['start' => '2026-12-01T00:00:00+01:00', 'end' => '2026-12-03T23:59:00+01:00']],
            $this->datesOf(self::TDM . 'dateonly')
        );
    }

    /**
     * The mixed shape, 39 of 156 probed events. Its event-level pair spans
     * 2026-12-08..2026-12-26 — exactly the schedule's envelope — so reading it
     * would replace three occurrences with one 18-day event.
     */
    #[Test]
    public function aScheduleWinsOverEventLevelDates(): void
    {
        $this->importEventLevelEvents();

        // Upstream states these in UTC; rendered here as the same instants in
        // Berlin local time.
        self::assertSame(
            [
                ['start' => '2026-12-08T17:00:00+01:00', 'end' => '2026-12-08T19:00:00+01:00'],
                ['start' => '2026-12-19T17:00:00+01:00', 'end' => '2026-12-19T19:00:00+01:00'],
                ['start' => '2026-12-26T17:00:00+01:00', 'end' => '2026-12-26T19:00:00+01:00'],
            ],
            $this->datesOf(self::MIXED)
        );
    }

    #[Test]
    public function theEnvelopeItselfIsNeverStoredAsAnOccurrence(): void
    {
        $this->importEventLevelEvents();

        foreach ($this->datesOf(self::MIXED) as $date) {
            self::assertSame(
                substr($date['start'], 0, 10),
                substr($date['end'], 0, 10),
                'An occurrence spanning several days would be the envelope, not a date.'
            );
        }
    }

    #[Test]
    public function everyEventLevelDateRowIsAnOrdinaryDateRow(): void
    {
        $this->importEventLevelEvents();

        $rows = $this->getConnectionPool()
            ->getConnectionForTable('tx_events_domain_model_date')
            ->select(['remote_id', 'canceled', 'event'], 'tx_events_domain_model_date')
            ->fetchAllAssociative()
        ;

        self::assertNotSame([], $rows);
        foreach ($rows as $row) {
            self::assertIsString($row['remote_id']);
            self::assertSame('no', $row['canceled']);
            self::assertGreaterThan(0, $row['event'], 'Every date must be wired to its event.');
            self::assertStringContainsString('::date::', $row['remote_id']);
        }
    }

    /**
     * Parity: the same occurrence, once as event-level dates and once as a
     * single schedule. Not a recurring schedule — those filter past dates by a
     * different rule than a single one.
     */
    #[Test]
    public function eventLevelAndSingleScheduleProduceEquivalentRows(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventLevelParityPreState.php');
        $this->expectFetch('e_19c21523-343f-4b72-b13c-756ac4bde8c5-tdm.json');
        $this->expectFetch('e_101176534-hubev.json');

        $this->importConfiguration(1);

        $eventLevel = $this->datesOf(self::TDM . 'tdm');
        $scheduled = $this->datesOf('https://thuecat.org/resources/e_101176534-hubev');

        self::assertCount(1, $eventLevel);
        self::assertCount(1, $scheduled);
        self::assertSame(array_keys($eventLevel[0]), array_keys($scheduled[0]));
    }

    #[Test]
    public function aPastEventLevelOccurrenceIsFilteredOut(): void
    {
        $this->getContainer()->get(Context::class)->setAspect(
            'date',
            new DateTimeAspect(new DateTimeImmutable('2027-01-01T00:00:00+00:00'))
        );

        $this->importEventLevelEvents();

        self::assertSame([], $this->datesOf(self::TDM . 'tdm'));
        self::assertContains(
            self::TDM . 'tdm',
            array_column($this->getLogEntriesOfType('eventWithoutDates'), 'remote_id')
        );
    }

    /**
     * The inversion this change exists to avoid. Inside a Schedule,
     * schema:endDate is the SERIES end (repeatUntil) — 2026-12-31 here. Read
     * the event-level way it would bound one occurrence, turning a weekly
     * series into a single 4-month date.
     */
    #[Test]
    public function aScheduleEndDateStillBoundsTheSeriesNotAnOccurrence(): void
    {
        $this->importSchedulePrecedenceEvents();

        $dates = $this->datesOf('https://thuecat.org/resources/e_7cbe5bb1-160b-4916-802c-c64dd2f1bf9e-tdm');

        self::assertGreaterThan(1, count($dates), 'The series must expand, not collapse into one date.');
        foreach ($dates as $date) {
            self::assertSame(
                substr($date['start'], 0, 10),
                substr($date['end'], 0, 10),
                'Each occurrence runs within one day; only repeatUntil reaches 2026-12-31.'
            );
        }

        $last = end($dates);
        self::assertIsArray($last);
        self::assertSame('2026-12-31', substr($last['start'], 0, 10));
    }

    // D12: presence, not yield. The event-level envelope beside it spans
    // 2026-12-08..26 and must not be used to rescue the event.
    #[Test]
    public function aPresentButEmptyScheduleDoesNotFallBackToEventLevelDates(): void
    {
        $this->importSchedulePrecedenceEvents();

        $remoteId = 'https://thuecat.org/resources/e_349b798696b77c81995d71a99b4460d1-emptysched';

        self::assertSame([], $this->datesOf($remoteId));
        self::assertContains(
            $remoteId,
            array_column($this->getLogEntriesOfType('eventWithoutDates'), 'remote_id')
        );
    }

    /**
     * The columns are `type => datetime`, so the DB holds unix timestamps.
     * Rendered back to ISO in Europe/Berlin, which is what the fixtures and the
     * other assertion files state — a timestamp tells a reader nothing.
     *
     * @return list<array{start: string, end: string}>
     */
    private function datesOf(string $eventRemoteId): array
    {
        $connection = $this->getConnectionPool()->getConnectionForTable('tx_events_domain_model_date');
        $rows = $connection
            ->select(['start', 'end', 'remote_id'], 'tx_events_domain_model_date', [], [], ['start' => 'ASC'])
            ->fetchAllAssociative()
        ;

        $dates = [];
        foreach ($rows as $row) {
            self::assertIsString($row['remote_id']);
            if (!str_starts_with($row['remote_id'], $eventRemoteId . '::date::')) {
                continue;
            }
            $dates[] = [
                'start' => $this->toIso($row['start']),
                'end' => $this->toIso($row['end']),
            ];
        }

        return $dates;
    }

    private function toIso(mixed $timestamp): string
    {
        self::assertIsInt($timestamp);

        return (new DateTimeImmutable('@' . $timestamp))
            ->setTimezone(new DateTimeZone('Europe/Berlin'))
            ->format('c')
        ;
    }

    private function importSchedulePrecedenceEvents(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/SchedulePrecedencePreState.php');
        $this->expectFetch('e_7cbe5bb1-tdm.json');
        $this->expectFetch('e_349b798696b77c81995d71a99b4460d1-emptysched.json');

        $this->importConfiguration(1);
    }

    private function importEventLevelEvents(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventLevelDatesPreState.php');
        $this->expectFetch('e_19c21523-343f-4b72-b13c-756ac4bde8c5-tdm.json');
        $this->expectFetch('e_19c21523-343f-4b72-b13c-756ac4bde8c5-notimes.json');
        $this->expectFetch('e_19c21523-343f-4b72-b13c-756ac4bde8c5-timedisagree.json');
        $this->expectFetch('e_19c21523-343f-4b72-b13c-756ac4bde8c5-dateonly.json');
        $this->expectFetch('e_349b798696b77c81995d71a99b4460d1-kcev.json');

        $this->importConfiguration(1);
    }
}
