<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional;

/*
 * Copyright (C) 2026 werkraum-media
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 */

use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfigurationInterface;
use WerkraumMedia\ThueCat\Domain\Repository\Backend\ImportConfigurationRepository;
use WerkraumMedia\ThueCat\Import\Importer;
use WerkraumMedia\ThueCat\Import\Watchdog\RunDeadline;

class ImportWatchdogTest extends AbstractImportTestCase
{
    protected bool $expectErrors = true;

    #[Test]
    public function anAbortedRunStillWritesItsLog(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsFreshOrganization.php');

        $this->importWithExpiredDeadline(1);

        $entries = $this->getLogEntriesOfType('runAborted');

        self::assertCount(1, $entries, 'The abort is recorded.');
        self::assertSame('error', $entries[0]['severity']);
    }

    #[Test]
    public function theAbortEntryNamesTheBudgetAndThePhaseReached(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsFreshOrganization.php');

        $this->importWithExpiredDeadline(1);

        $entries = $this->getLogEntriesOfType('runAborted');
        self::assertIsString($entries[0]['context']);
        $context = json_decode($entries[0]['context'], true);

        self::assertIsArray($context);
        self::assertSame('fetch', $context['phase'] ?? null, 'The phase reached is named.');
        self::assertArrayHasKey('budgetSeconds', $context);
        self::assertArrayHasKey('elapsedSeconds', $context);
    }

    #[Test]
    public function anAbortedRunReportsErrorSeverity(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsFreshOrganization.php');

        $severity = $this->importWithExpiredDeadline(1);

        self::assertSame('error', $severity, 'The command must exit non-zero.');
    }

    /**
     * The abort is staged like any other entry, so reaching the database at all
     * proves the run finished through writeLog() rather than unwinding past it.
     *
     * MEASURED: a single-root run makes exactly 2 deadline checks — before the
     * root, and before pass one. Persisting happens only if check 2 passes, so
     * "aborted AND records written" cannot both hold here; that combination
     * needs a multi-root fixture and is not asserted.
     */
    #[Test]
    public function theAbortReachesTheDatabaseThroughTheNormalLogWrite(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsFreshOrganization.php');

        $this->importWithExpiredDeadline(1);

        $log = $this->getConnectionPool()
            ->getConnectionForTable('tx_thuecat_import_log')
            ->select(['uid'], 'tx_thuecat_import_log')
            ->fetchAllAssociative()
        ;

        self::assertCount(1, $log, 'An import log record exists for the aborted run.');
        self::assertCount(1, $this->getLogEntriesOfType('runAborted'));
    }

    /**
     * A run killed by an unexpected throwable is the same diagnostic problem as
     * a watchdog abort: without a log, nobody can see how far it got.
     */
    #[Test]
    public function aRunEndedByAThrowableStillWritesItsLog(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsFreshOrganization.php');
        $this->expectFetch('018132452787-ngbe.json');

        try {
            $this->get(Importer::class)->importConfiguration(
                $this->configuration(1),
                null,
                new DeadlineThrowingOnCheck(2)
            );
            self::fail('Expected the run-ending throwable to surface.');
        } catch (RuntimeException $expected) {
            self::assertSame('deliberate mid-run failure', $expected->getMessage());
        }

        $entries = $this->getLogEntriesOfType('runFailed');

        self::assertCount(1, $entries, 'The run-ending failure is recorded.');
        self::assertSame('error', $entries[0]['severity']);
        self::assertIsString($entries[0]['context']);
        $context = json_decode($entries[0]['context'], true);
        self::assertIsArray($context);
        self::assertSame('persist', $context['phase'] ?? null, 'The phase reached is named.');
    }

    #[Test]
    public function aRunInsideItsBudgetIsUnaffected(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsFreshOrganization.php');
        $this->expectFetch('018132452787-ngbe.json');

        $this->importConfiguration(1);

        $this->assertPHPDataSet(__DIR__ . '/Assertions/Import/ImportsFreshOrganization.php');
        self::assertSame([], $this->getLogEntriesOfType('runAborted'));
    }

    private function importWithExpiredDeadline(int $uid): string
    {
        return $this->runWithDeadline($uid, new RunDeadline(10, microtime(true) - 20.0));
    }

    private function runWithDeadline(int $uid, RunDeadline $deadline): string
    {
        return $this->get(Importer::class)->importConfiguration(
            $this->configuration($uid),
            null,
            $deadline
        );
    }

    private function configuration(int $uid): ImportConfigurationInterface
    {
        $this->workaroundExtbaseConfiguration();
        $configuration = $this->get(ImportConfigurationRepository::class)->findOneByUid($uid);
        self::assertNotNull($configuration, 'Fixture configuration uid=' . $uid . ' not found');

        return $configuration;
    }
}
