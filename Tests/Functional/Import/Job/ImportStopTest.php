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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Messenger\MessageBusInterface;
use WerkraumMedia\ThueCat\Import\Job\ImportJobs;
use WerkraumMedia\ThueCat\Import\Job\Message\DeleteRoot;
use WerkraumMedia\ThueCat\Import\Job\Message\ImportFinished;
use WerkraumMedia\ThueCat\Import\Job\Message\ImportMessage;
use WerkraumMedia\ThueCat\Import\Job\Message\ImportRoot;
use WerkraumMedia\ThueCat\Import\Job\Message\ImportStarted;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractImportTestCase;

final class ImportStopTest extends AbstractImportTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Import/ImportStop.csv');
    }

    #[Test]
    public function runningJobFinishesItsCurrentItemSkipsTheRestAndEndsStopped(): void
    {
        $this->expectFailureForUrl('https://thuecat.org/resources/test-trail-current', 404);
        $this->handleImportRoot(1, 1);

        $this->get(ImportJobs::class)->stop(1);

        $this->assertSame('stopping', $this->jobRow(1)['status']);
        $this->assertEquals([new ImportRoot(1, 2)], $this->queuedMessages());

        // Nothing staged: the next item must not be fetched.
        $this->handleImportRoot(1, 2);
        $this->handleImportFinished(1);

        $this->assertSame(['done', 'skipped', 'skipped'], $this->jobItemStates(1));
        $this->assertSame('stopped', $this->jobOutcome(1)['status']);
        $this->assertSame(2, $this->jobOutcome(1)['skipped_items']);
    }

    #[Test]
    public function withNoWaitingMessageTheStopSkipsEveryPendingItemAtOnce(): void
    {
        $this->get(ImportJobs::class)->stop(1);

        $this->assertSame('stopping', $this->jobRow(1)['status']);
        $this->assertSame(['skipped', 'skipped', 'skipped'], $this->jobItemStates(1));
        $this->assertEquals([new ImportFinished(1)], $this->queuedMessages());
    }

    /**
     * @return iterable<string, array{ImportMessage}>
     */
    public static function waitingMessagesOfTheJob(): iterable
    {
        yield 'start' => [new ImportStarted(1)];
        yield 'import' => [new ImportRoot(1, 2)];
        yield 'delete' => [new DeleteRoot(1, 3)];
        yield 'finish' => [new ImportFinished(1)];
    }

    #[DataProvider('waitingMessagesOfTheJob')]
    #[Test]
    public function stopLeavesTheRestToAWaitingMessageOfTheJob(ImportMessage $waiting): void
    {
        $this->get(MessageBusInterface::class)->dispatch($waiting);

        $this->get(ImportJobs::class)->stop(1);

        $this->assertSame(['pending', 'pending', 'pending'], $this->jobItemStates(1));
        $this->assertEquals([$waiting], $this->queuedMessages());
    }

    #[Test]
    public function waitingMessageOfAnotherJobDoesNotDeferTheStop(): void
    {
        $this->get(MessageBusInterface::class)->dispatch(new ImportRoot(4, 5));

        $this->get(ImportJobs::class)->stop(1);

        $this->assertSame(['skipped', 'skipped', 'skipped'], $this->jobItemStates(1));
    }

    #[Test]
    public function queuedJobIsStoppedBeforeItStartsAndKeepsTheLastRun(): void
    {
        $this->get(MessageBusInterface::class)->dispatch(new ImportStarted(2));

        $this->get(ImportJobs::class)->stop(2);

        $this->assertSame('stopping', $this->jobRow(2)['status']);
        $this->assertEquals([new ImportStarted(2)], $this->queuedMessages());

        $this->handleImportStarted(2);

        $this->assertEquals([new ImportFinished(2)], $this->queuedMessages());

        $this->handleImportFinished(2);

        $this->assertSame('stopped', $this->jobOutcome(2)['status']);
        $this->assertSame([], $this->jobItems(2));
        $this->assertSame(1700000000, $this->importConfigurationValue(2, 'last_run'));
    }

    #[Test]
    public function endedJobIsNotStopped(): void
    {
        $this->get(ImportJobs::class)->stop(3);

        $this->assertSame('done', $this->jobRow(3)['status']);
        $this->assertSame([], $this->queuedMessages());
    }

    /**
     * @return iterable<string, array{string, int}>
     */
    public static function itemMessages(): iterable
    {
        yield 'import' => ['handleImportRoot', 5];
        yield 'delete' => ['handleDeleteRoot', 6];
    }

    #[DataProvider('itemMessages')]
    #[Test]
    public function itemMessageOfAStoppingJobSkipsEveryPendingItemAndSendsTheFinishingMessage(string $handle, int $item): void
    {
        $this->{$handle}(4, $item);

        $this->assertSame(['done', 'skipped', 'skipped'], $this->jobItemStates(4));
        $this->assertEquals([new ImportFinished(4)], $this->queuedMessages());
        $this->assertSame(0, $this->getConnectionPool()->getConnectionForTable('tx_thuecat_import_job_item')->select(['attempts'], 'tx_thuecat_import_job_item', ['uid' => $item])->fetchOne());
    }

    // A retry of an item the stop skipped arrives after the finishing message was sent.
    #[Test]
    public function messageOfAStoppingJobsItemThatIsNoLongerPendingSendsNothing(): void
    {
        $this->handleImportRoot(4, 4);
        $this->handleDeleteRoot(4, 4);

        $this->assertSame([], $this->queuedMessages());
        $this->assertSame(['done', 'pending', 'pending'], $this->jobItemStates(4));
    }

    #[Test]
    public function redeliveredStartOfAJobStoppedAfterItsStartSendsNothing(): void
    {
        $this->handleImportStarted(4);

        $this->assertSame([], $this->queuedMessages());
        $this->assertSame(['done', 'pending', 'pending'], $this->jobItemStates(4));
    }

    // An item awaiting its retry would keep the finishing message waiting for an item the stop gave up.
    #[Test]
    public function finishingMessageOfAStoppingJobSkipsPendingItemsInsteadOfWaiting(): void
    {
        $this->handleImportFinished(4);

        $this->assertSame(
            ['status' => 'stopped', 'done_items' => 1, 'failed_items' => 0, 'skipped_items' => 2],
            array_diff_key($this->jobOutcome(4), ['ended_at' => true])
        );
        $this->assertSame([], $this->queuedMessages());
    }
}
