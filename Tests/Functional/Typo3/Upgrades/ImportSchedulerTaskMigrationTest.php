<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS extension "thuecat".
 *
 * Copyright (C) werkraum-media <https://werkraum-media.de/>
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * For the full license text, see the LICENSE file distributed with this
 * extension.
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 */

namespace WerkraumMedia\ThueCat\Tests\Functional\Typo3\Upgrades;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Output\BufferedOutput;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractFunctionalTestCase;
use WerkraumMedia\ThueCat\Typo3\Upgrades\ImportSchedulerTaskMigration;

final class ImportSchedulerTaskMigrationTest extends AbstractFunctionalTestCase
{
    private ImportSchedulerTaskMigration $wizard;

    private BufferedOutput $output;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Upgrades/ImportSchedulerTasks.csv');
        $this->output = new BufferedOutput();
        $this->wizard = $this->get(ImportSchedulerTaskMigration::class);
        $this->wizard->setOutput($this->output);
    }

    /**
     * Interval, next run and skip media come from the task with the shortest interval; a disabled task counts only
     * when no other runs the configuration, and then as manual only. Deleted tasks and those of other commands stay
     * as they are. Last runs are seeded from the import logs where no job has recorded one.
     */
    #[Test]
    public function movesTheTasksOntoTheirConfigurationsAndDeletesThem(): void
    {
        $this->assertTrue($this->wizard->executeUpdate());

        $this->assertCSVDataSet(__DIR__ . '/../../Assertions/Upgrades/ImportSchedulerTasks.csv');
    }

    #[Test]
    public function reportsACronExpressionTurnedIntoAnInterval(): void
    {
        $this->wizard->executeUpdate();

        $this->assertStringContainsString(
            'Scheduler task 2: the cron expression "0 3 * * 1" of import configuration 2 became an interval of 604800 s.',
            $this->output->fetch(),
        );
    }

    #[Test]
    public function reportsTheFreshOptionAsNotCarriedOver(): void
    {
        $this->wizard->executeUpdate();

        $this->assertStringContainsString(
            'Scheduler task 3: the option --fresh of import configuration 3 was not carried over.',
            $this->output->fetch(),
        );
    }

    #[Test]
    public function reportsSeveralTasksOfOneConfiguration(): void
    {
        $this->wizard->executeUpdate();

        $report = $this->output->fetch();
        $this->assertStringContainsString(
            'Import configuration 4: scheduler tasks 4, 5, 6 ran it, the interval of 3600 s of task 5 was kept.',
            $report,
        );
        $this->assertStringNotContainsString('Import configuration 1:', $report, 'A single task is not reported.');
    }

    #[Test]
    public function isNecessaryUntilItHasRun(): void
    {
        $this->assertTrue($this->wizard->updateNecessary());

        $this->wizard->executeUpdate();

        $this->assertFalse($this->wizard->updateNecessary());
    }

    #[Test]
    public function isNecessaryForImportLogsWithoutARecordedRun(): void
    {
        $this->getConnectionPool()->getConnectionForTable('tx_scheduler_task')->truncate('tx_scheduler_task');

        $this->assertTrue($this->wizard->updateNecessary());
    }

    // A task stored the thuecat 7 way has no task type yet, so it would be missed.
    #[Test]
    public function waitsForTheSchedulerStorageMigrationOfCore(): void
    {
        $this->getConnectionPool()->getConnectionForTable('tx_scheduler_task')->insert('tx_scheduler_task', [
            'pid' => 0,
            'tasktype' => '',
            'serialized_task_object' => 'O:8:"stdClass":1:{s:17:"commandIdentifier";s:30:"thuecat:importviaconfiguration";}',
        ]);

        $this->assertFalse($this->wizard->updateNecessary());
    }

    #[Test]
    public function doesNotWaitForUnmigratedTasksOfOtherCommands(): void
    {
        $this->getConnectionPool()->getConnectionForTable('tx_scheduler_task')->insert('tx_scheduler_task', [
            'pid' => 0,
            'tasktype' => '',
            'serialized_task_object' => 'O:8:"stdClass":1:{s:17:"commandIdentifier";s:11:"cache:flush";}',
        ]);

        $this->assertTrue($this->wizard->updateNecessary());
    }

    // Core keeps counting native tasks it has already migrated, so its own answer never turns false.
    #[Test]
    public function doesNotWaitForAlreadyMigratedNativeTasks(): void
    {
        $this->getConnectionPool()->getConnectionForTable('tx_scheduler_task')->insert('tx_scheduler_task', [
            'pid' => 0,
            'tasktype' => 'TYPO3\\CMS\\Scheduler\\Task\\TableGarbageCollectionTask',
            'parameters' => [],
        ]);

        $this->assertTrue($this->wizard->updateNecessary());
    }

    // Tasks created before the command had options store none.
    #[Test]
    public function movesATaskStoredWithoutOptions(): void
    {
        $this->getConnectionPool()->getConnectionForTable('tx_thuecat_import_configuration')->insert(
            'tx_thuecat_import_configuration',
            ['uid' => 99, 'pid' => 0, 'title' => 'Without options', 'type' => 'static']
        );
        $this->getConnectionPool()->getConnectionForTable('tx_scheduler_task')->insert('tx_scheduler_task', [
            'uid' => 99,
            'pid' => 0,
            'tasktype' => 'thuecat:importviaconfiguration',
            'parameters' => [
                'arguments' => ['configuration' => '99'],
                'commandIdentifier' => 'thuecat:importviaconfiguration',
            ],
            'execution_details' => [
                'start' => 1795000000,
                'end' => 0,
                'interval' => 86400,
                'multiple' => false,
                'cronCmd' => '',
                'isNewSingleExecution' => false,
            ],
            'nextexecution' => 1795755600,
        ]);

        $this->assertTrue($this->wizard->executeUpdate());

        $this->assertSame(1, $this->countRowsMatching(
            'tx_thuecat_import_configuration',
            ['uid' => 99, 'schedule_interval' => 86400, 'next_run' => 1795755600, 'skip_media' => 0]
        ));
        $this->assertSame(0, $this->countRowsMatching('tx_scheduler_task', ['uid' => 99]));
    }
}
