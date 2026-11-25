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

namespace WerkraumMedia\ThueCat\Tests\Functional\Import\Job;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Messenger\MessageBusInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use WerkraumMedia\ThueCat\Import\Job\ImportJobItems;
use WerkraumMedia\ThueCat\Import\Job\ImportJobs;
use WerkraumMedia\ThueCat\Import\Job\ImportSchedule;
use WerkraumMedia\ThueCat\Import\Job\Message\ImportDue;
use WerkraumMedia\ThueCat\Service\WaitingMessages;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractImportTestCase;
use WerkraumMedia\ThueCat\Tests\Functional\FixedClock;

final class ImportFinishedTest extends AbstractImportTestCase
{
    private const int STARTED = 1795526700;

    private const int ENDED = 1795527000;

    private const int EARLIER_RUN = 1700000000;

    private const string ZONE = 'Europe/Berlin';

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Import/ImportFinished.csv');
        // @phpstan-ignore method.notFound (functional test container is the Symfony Container, which has set())
        $this->getContainer()->set(ImportJobs::class, new ImportJobs(
            $this->get(ConnectionPool::class),
            new FixedClock(new DateTimeImmutable('@' . self::ENDED)),
            $this->get(MessageBusInterface::class),
            $this->get(ImportJobItems::class),
            new WaitingMessages($this->get(ConnectionPool::class)),
        ));
        // @phpstan-ignore method.notFound (functional test container is the Symfony Container, which has set())
        $this->getContainer()->set(ImportSchedule::class, new ImportSchedule(
            $this->get(ConnectionPool::class),
            new FixedClock(new DateTimeImmutable('@' . self::ENDED)),
            $this->get(MessageBusInterface::class),
        ));
    }

    #[Test]
    public function jobWhoseItemsAreAllDoneIsDoneWithCountsAndEndTime(): void
    {
        $this->handleImportFinished(1);

        $this->assertSame(
            ['status' => 'done', 'ended_at' => self::ENDED, 'done_items' => 2, 'failed_items' => 0, 'skipped_items' => 0],
            $this->jobOutcome(1)
        );
    }

    #[Test]
    public function jobWithAFailedItemIsFailedWithCounts(): void
    {
        $this->handleImportFinished(2);

        $this->assertSame(
            ['status' => 'failed', 'ended_at' => self::ENDED, 'done_items' => 1, 'failed_items' => 1, 'skipped_items' => 0],
            $this->jobOutcome(2)
        );
    }

    #[Test]
    public function jobFailedAtItsStartStaysFailed(): void
    {
        $this->handleImportFinished(4);

        $this->assertSame(
            ['status' => 'failed', 'ended_at' => self::ENDED, 'done_items' => 0, 'failed_items' => 0, 'skipped_items' => 0],
            $this->jobOutcome(4)
        );
    }

    #[Test]
    public function stoppingJobIsStopped(): void
    {
        $this->handleImportFinished(5);

        $this->assertSame(
            ['status' => 'stopped', 'ended_at' => self::ENDED, 'done_items' => 1, 'failed_items' => 0, 'skipped_items' => 1],
            $this->jobOutcome(5)
        );
    }

    #[Test]
    public function lastRunIsTheJobsStartWhateverItsOutcome(): void
    {
        foreach ([1, 2, 4, 5] as $job) {
            $this->handleImportFinished($job);
        }

        $this->assertSame(
            [self::STARTED, self::STARTED, self::STARTED, self::STARTED],
            array_map(fn (int $uid): mixed => $this->importConfigurationValue($uid, 'last_run'), [1, 2, 4, 5])
        );
    }

    #[Test]
    public function doneJobIsTheLastSuccessfulRun(): void
    {
        $this->handleImportFinished(1);

        $this->assertSame(self::STARTED, $this->importConfigurationValue(1, 'last_successful_run'));
    }

    #[Test]
    public function jobsThatAreNotDoneLeaveTheLastSuccessfulRun(): void
    {
        foreach ([2, 4, 5] as $job) {
            $this->handleImportFinished($job);
        }

        $this->assertSame(
            [self::EARLIER_RUN, self::EARLIER_RUN, self::EARLIER_RUN],
            array_map(fn (int $uid): mixed => $this->importConfigurationValue($uid, 'last_successful_run'), [2, 4, 5])
        );
    }

    #[Test]
    public function doneJobWhoseLogHoldsAFailedRunLeavesTheLastSuccessfulRun(): void
    {
        $this->handleImportFinished(3);

        $this->assertSame('done', $this->jobRow(3)['status']);
        $this->assertSame(self::EARLIER_RUN, $this->importConfigurationValue(3, 'last_successful_run'));
    }

    // One bad record must not reset the anchor, or the next job would fetch the whole history again.
    #[Test]
    public function faultsOfSingleRecordsKeepTheJobAsLastSuccessfulRun(): void
    {
        $this->handleImportFinished(6);

        $this->assertSame(self::STARTED, $this->importConfigurationValue(6, 'last_successful_run'));
    }

    // The job ends at 14:30 on 2026-11-24, Berlin time.
    #[Test]
    public function nextRunKeepsTheDailyCadence(): void
    {
        $this->updateImportConfiguration(1, [
            'schedule_interval' => 86400,
            'next_run' => new DateTimeImmutable('2026-11-24 03:00', new DateTimeZone(self::ZONE))->getTimestamp(),
        ]);

        $this->handleImportFinished(1);

        $this->assertSame(
            new DateTimeImmutable('2026-11-25 03:00', new DateTimeZone(self::ZONE))->getTimestamp(),
            $this->importConfigurationValue(1, 'next_run')
        );
    }

    #[Test]
    public function lateJobSkipsTheMissedSlots(): void
    {
        $this->updateImportConfiguration(1, [
            'schedule_interval' => 3600,
            'next_run' => new DateTimeImmutable('2026-11-24 11:15', new DateTimeZone(self::ZONE))->getTimestamp(),
        ]);

        $this->handleImportFinished(1);

        $this->assertSame(
            new DateTimeImmutable('2026-11-24 15:15', new DateTimeZone(self::ZONE))->getTimestamp(),
            $this->importConfigurationValue(1, 'next_run')
        );
    }

    #[Test]
    public function nextRunAtTheJobsEndIsNotInTheFuture(): void
    {
        $this->updateImportConfiguration(1, ['schedule_interval' => 3600, 'next_run' => self::ENDED]);

        $this->handleImportFinished(1);

        $this->assertSame(self::ENDED + 3600, $this->importConfigurationValue(1, 'next_run'));
    }

    // An editor rescheduled while the job ran.
    #[Test]
    public function nextRunInTheFutureIsKept(): void
    {
        $this->updateImportConfiguration(1, ['schedule_interval' => 3600, 'next_run' => self::ENDED + 600]);

        $this->handleImportFinished(1);

        $this->assertSame(self::ENDED + 600, $this->importConfigurationValue(1, 'next_run'));
        $this->assertEquals([new ImportDue(1, self::ENDED + 600)], $this->queuedMessages());
    }

    #[Test]
    public function dueMessageWaitsForTheNextRun(): void
    {
        $nextRun = new DateTimeImmutable('2026-11-25 03:00', new DateTimeZone(self::ZONE))->getTimestamp();
        $this->updateImportConfiguration(1, [
            'schedule_interval' => 86400,
            'next_run' => new DateTimeImmutable('2026-11-24 03:00', new DateTimeZone(self::ZONE))->getTimestamp(),
        ]);

        $this->handleImportFinished(1);

        $this->assertEquals([new ImportDue(1, $nextRun)], $this->queuedMessages());
        $this->assertSame(($nextRun - self::ENDED) * 1000, $this->queuedMessageDelay());
    }

    #[Test]
    public function manualOnlyConfigurationSchedulesNothing(): void
    {
        $this->handleImportFinished(1);

        $this->assertSame([], $this->queuedMessages());
        $this->assertSame(0, $this->importConfigurationValue(1, 'next_run'));
    }

    #[Test]
    public function failedAndStoppedJobsStillSchedule(): void
    {
        foreach ([2, 4, 5] as $uid) {
            $this->updateImportConfiguration($uid, [
                'schedule_interval' => 86400,
                'next_run' => new DateTimeImmutable('2026-11-24 03:00', new DateTimeZone(self::ZONE))->getTimestamp(),
            ]);
            $this->handleImportFinished($uid);
        }

        $nextRun = new DateTimeImmutable('2026-11-25 03:00', new DateTimeZone(self::ZONE))->getTimestamp();
        $this->assertSame(
            [$nextRun, $nextRun, $nextRun],
            array_map(fn (int $uid): mixed => $this->importConfigurationValue($uid, 'next_run'), [2, 4, 5])
        );
    }
}
