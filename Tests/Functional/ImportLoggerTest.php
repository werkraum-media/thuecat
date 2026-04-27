<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\CMS\Core\Database\ConnectionPool;
use WerkraumMedia\ThueCat\Command\ImportConfigurationCommand;
use WerkraumMedia\ThueCat\Domain\Import\ImportLogger;

/**
 * Covers the DataHandler error-capture pipeline that hangs off ImportLogger:
 * - recordDataHandlerErrors stages one tx_thuecat_import_log_entry row of
 *   type=dataHandlerError severity=error per captured errorLog line.
 * - getMaxSeverity reflects the highest staged severity for the run.
 * - ImportConfigurationCommand maps severity to exit code.
 */
final class ImportLoggerTest extends AbstractImportTestCase
{
    #[Test]
    public function recordsDataHandlerErrorsAsLogEntries(): void
    {
        $logger = $this->get(ImportLogger::class);
        $logger->reset();

        $logger->recordDataHandlerErrors([
            '[1.2]: Attempt to localize record on non-translatable table',
            '[1.1]: Some other complaint',
        ], 1);

        self::assertSame(ImportLogger::SEVERITY_ERROR, $logger->getMaxSeverity());

        $logger->writeLog(null, [], []);

        $rows = $this->fetchEntries();
        self::assertCount(2, $rows);
        foreach ($rows as $row) {
            self::assertSame('dataHandlerError', $row['type']);
            self::assertSame('error', $row['severity']);
            self::assertSame('{"iteration":1}', $row['context']);
            self::assertNotSame('', $row['message']);
        }
    }

    #[Test]
    public function recordsExceptionsAsLogEntries(): void
    {
        $logger = $this->get(ImportLogger::class);
        $logger->reset();

        $logger->recordException(
            'fetchingError',
            new RuntimeException('upstream returned 500', 1700000001)
        );

        self::assertSame(ImportLogger::SEVERITY_ERROR, $logger->getMaxSeverity());

        $logger->writeLog(null, [], []);

        $rows = $this->fetchEntries();
        self::assertCount(1, $rows);
        self::assertSame('fetchingError', $rows[0]['type']);
        self::assertSame('error', $rows[0]['severity']);
        self::assertSame('upstream returned 500', $rows[0]['message']);
    }

    #[Test]
    public function getMaxSeverityDefaultsToDebugWhenEmpty(): void
    {
        $logger = $this->get(ImportLogger::class);
        $logger->reset();

        self::assertSame(ImportLogger::SEVERITY_DEBUG, $logger->getMaxSeverity());
    }

    #[Test]
    public function commandReturnsSuccessOnCleanRun(): void
    {
        $this->workaroundExtbaseConfiguration();
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsFreshOrganization.php');
        $this->expectFetch('018132452787-ngbe.json');

        $subject = $this->get(ImportConfigurationCommand::class);
        $tester = new CommandTester($subject);
        $exit = $tester->execute(['configuration' => 1], ['capture_stderr_separately' => true]);

        self::assertSame(Command::SUCCESS, $exit);
    }

    #[Test]
    public function commandReturnsFailureWhenLoggerHoldsErrorSeverity(): void
    {
        $this->workaroundExtbaseConfiguration();
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsFreshOrganization.php');
        $this->expectFetch('018132452787-ngbe.json');

        // Inject a recorded DataHandler-style error into the same logger
        // instance the importer/command will use. Mirrors what would happen
        // if an import pass produced a non-empty $dataHandler->errorLog.
        $logger = $this->get(ImportLogger::class);
        $logger->recordDataHandlerErrors(['[1.2]: Simulated DataHandler refusal'], 0);

        $subject = $this->get(ImportConfigurationCommand::class);
        $tester = new CommandTester($subject);
        $exit = $tester->execute(['configuration' => 1], ['capture_stderr_separately' => true]);

        self::assertSame(Command::FAILURE, $exit);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchEntries(): array
    {
        $queryBuilder = $this->get(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_thuecat_import_log_entry')
        ;
        $queryBuilder->getRestrictions()->removeAll();
        $rows = $queryBuilder
            ->select('*')
            ->from('tx_thuecat_import_log_entry')
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative()
        ;
        /** @var list<array<string, mixed>> $rows */
        return $rows;
    }
}
