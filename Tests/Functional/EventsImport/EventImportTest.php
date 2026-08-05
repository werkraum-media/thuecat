<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional\EventsImport;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\DateTimeAspect;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractImportTestCase;

// End-to-end import test: stages a static-URL ImportConfiguration that points
// at the Kreuzchor JSON-LD fixture, runs the Importer, and asserts that the
// event row plus its single child Date row land in the ext:events tables
// with the FK wired correctly. Proves the EventEntity → DateEntity →
// Resolver → DataHandler chain works through the existing pipeline.
class EventImportTest extends AbstractImportTestCase
{
    protected array $testExtensionsToLoad = [
        'werkraummedia/thuecat/',
        'werkraummedia/events/',
    ];

    // Override fixture roots: events fixtures live under the events test tree,
    // not the legacy thuecat one. The Guzzle fixtures sit under
    // EventsImport/Fixtures/Guzzle/ keyed by host + path.
    protected string $fixtureGuzzleBase = __DIR__ . '/Fixtures/Guzzle';
    protected string $fixtureDomain = 'cdb.int.thuecat.org';
    protected string $fixturePath = 'api/resources';

    #[Test]
    public function importsKreuzchorEventWithSingleDate(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventImportKreuzchorPreState.php');
        $this->expectFetch('e_19542-hubev.json');

        $this->importConfiguration(1);

        $this->assertPHPDataSet(__DIR__ . '/Assertions/EventImportKreuzchor.php');
    }

    #[Test]
    public function reimportDeletesOccurrencesTheScheduleNoLongerProduces(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventReimportStaleDatesPreState.php');
        $this->expectFetch('e_19542-hubev.json');

        $this->importConfiguration(1);

        $this->assertPHPDataSet(__DIR__ . '/Assertions/EventReimportStaleDates.php');
    }

    #[Test]
    public function reimportLeavesDatesOfEventsOutsideTheRunUntouched(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventReimportOtherEventPreState.php');
        $this->expectFetch('e_19542-hubev.json');

        $this->importConfiguration(1);

        $this->assertPHPDataSet(__DIR__ . '/Assertions/EventReimportOtherEvent.php');
    }

    #[Test]
    public function reimportDeletesAnOccurrenceThatBecameExcepted(): void
    {
        $this->getContainer()->get(Context::class)->setAspect(
            'date',
            new DateTimeAspect(new DateTimeImmutable('2026-12-01T00:00:00+00:00'))
        );
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventReimportExceptedDatePreState.php');
        $this->expectFetch('e_7cbe5bb1-tdm.json');

        $this->importConfiguration(1);

        $this->assertPHPDataSet(__DIR__ . '/Assertions/EventReimportExceptedDate.php');
    }

    #[Test]
    public function reimportDeletesOccurrencesAfterAShortenedSeriesEnd(): void
    {
        $this->getContainer()->get(Context::class)->setAspect(
            'date',
            new DateTimeAspect(new DateTimeImmutable('2026-12-01T00:00:00+00:00'))
        );
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventReimportShortenedSeriesPreState.php');
        $this->expectFetch('e_7cbe5bb1-shortened-tdm.json');

        $this->importConfiguration(1);

        $this->assertPHPDataSet(__DIR__ . '/Assertions/EventReimportShortenedSeries.php');
    }

    #[Test]
    public function logsSkippedScheduleDayAsWarningWithoutFailingTheRun(): void
    {
        $this->getContainer()->get(Context::class)->setAspect(
            'date',
            new DateTimeAspect(new DateTimeImmutable('2026-12-01T00:00:00+00:00'))
        );
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventReimportExceptedDatePreState.php');
        $this->expectFetch('e_7cbe5bb1-tdm.json');

        $severity = $this->importConfigurationReturningSeverity(1);

        self::assertSame('warning', $severity);
        self::assertSame(
            [
                [
                    'type' => 'scheduleDaySkipped',
                    'severity' => 'warning',
                    'table_name' => 'tx_events_domain_model_event',
                    'remote_id' => 'https://thuecat.org/resources/e_7cbe5bb1-160b-4916-802c-c64dd2f1bf9e-tdm',
                    'message' => 'Skipped schedule day(s) "PublicHolidays": no date series can be built from them.',
                    'context' => '{"days":["PublicHolidays"]}',
                ],
            ],
            $this->getLogEntriesOfType('scheduleDaySkipped')
        );
        self::assertSame([], $this->getLogEntriesOfType('scheduleDayDropped'));
    }
}
