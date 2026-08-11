<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional\EventsImport;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\DateTimeAspect;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLog;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLogEntry\EventDateSkipped;
use WerkraumMedia\ThueCat\Domain\Repository\Backend\ImportLogRepository;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractImportTestCase;

// An event publishing schema:startDate without schema:endDate. The pair cannot
// be resolved, so the occurrence is skipped and the event ends up dateless —
// two findings, deliberately reported separately.
class EventDateSkippedTest extends AbstractImportTestCase
{
    protected array $testExtensionsToLoad = [
        'werkraummedia/thuecat/',
        'werkraummedia/events/',
    ];

    protected string $fixtureGuzzleBase = __DIR__ . '/Fixtures/Guzzle';
    protected string $fixtureDomain = 'cdb.int.thuecat.org';
    protected string $fixturePath = 'api/resources';

    private const REMOTE_ID = 'https://thuecat.org/resources/e_19c21523-343f-4b72-b13c-756ac4bde8c5-noenddate';

    protected function setUp(): void
    {
        parent::setUp();

        $this->getContainer()->get(Context::class)->setAspect(
            'date',
            new DateTimeAspect(new DateTimeImmutable('2026-01-01T00:00:00+00:00'))
        );
    }

    #[Test]
    public function anUnresolvableEventLevelDateIsLoggedAsWarning(): void
    {
        $this->importEventWithUnresolvableDate();

        $entries = $this->getLogEntriesOfType('eventDateSkipped');

        self::assertCount(1, $entries);
        self::assertSame('warning', $entries[0]['severity']);
        self::assertSame('tx_events_domain_model_event', $entries[0]['table_name']);
        self::assertSame(self::REMOTE_ID, $entries[0]['remote_id']);
    }

    #[Test]
    public function noDateRowIsWrittenForIt(): void
    {
        $this->importEventWithUnresolvableDate();

        $count = $this->getConnectionPool()
            ->getConnectionForTable('tx_events_domain_model_date')
            ->count('uid', 'tx_events_domain_model_date', [])
        ;

        self::assertSame(0, $count, 'An unresolvable date must not be completed by guesswork.');
    }

    // The two entries answer different questions: a published value was
    // unusable, and the stored record is undisplayable. Both are wanted.
    #[Test]
    public function theSameEventIsAlsoReportedAsHavingNoDates(): void
    {
        $this->importEventWithUnresolvableDate();

        self::assertCount(1, $this->getLogEntriesOfType('eventDateSkipped'));
        self::assertCount(1, $this->getLogEntriesOfType('eventWithoutDates'));
    }

    #[Test]
    public function theEventRecordIsStillImported(): void
    {
        $this->importEventWithUnresolvableDate();

        $rows = $this->getConnectionPool()
            ->getConnectionForTable('tx_events_domain_model_event')
            ->select(['remote_id'], 'tx_events_domain_model_event')
            ->fetchAllAssociative()
        ;

        self::assertCount(1, $rows);
    }

    #[Test]
    public function aRunFindingOnlySkippedDatesStillSucceeds(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventDateSkippedPreState.php');
        $this->expectFetch('e_19c21523-343f-4b72-b13c-756ac4bde8c5-noenddate.json');

        $severity = $this->importConfigurationReturningSeverity(1);

        self::assertSame('warning', $severity, 'A skipped date must not fail the run.');
    }

    #[Test]
    public function theEntryNamesTheEvent(): void
    {
        $this->importEventWithUnresolvableDate();

        $entries = $this->getLogEntriesOfType('eventDateSkipped');

        self::assertNotSame([], $entries);
        $message = $entries[0]['message'];
        self::assertIsString($message);
        self::assertStringContainsString('Test-Festival ohne Enddatum', $message);
    }

    // The trap eventWithoutDates hit: a type that stages but cannot be mapped
    // back is invisible until the backend module tries to list it.
    #[Test]
    public function theEntryResolvesToItsOwnClass(): void
    {
        $this->importEventWithUnresolvableDate();

        $log = $this->get(ImportLogRepository::class)->findAll()->getFirst();
        self::assertInstanceOf(ImportLog::class, $log);

        $matching = [];
        foreach ($log->getEntries() as $entry) {
            if ($entry instanceof EventDateSkipped) {
                $matching[] = $entry;
            }
        }

        self::assertCount(1, $matching);
        self::assertSame('eventDateSkipped', $matching[0]->getType());
    }

    private function importEventWithUnresolvableDate(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventDateSkippedPreState.php');
        $this->expectFetch('e_19c21523-343f-4b72-b13c-756ac4bde8c5-noenddate.json');

        $this->importConfiguration(1);
    }
}
