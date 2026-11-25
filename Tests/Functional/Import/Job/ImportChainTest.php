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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Messenger\MessageBusInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use WerkraumMedia\ThueCat\Import\Job\ImportConfigurationGoneException;
use WerkraumMedia\ThueCat\Import\Job\ImportJobItems;
use WerkraumMedia\ThueCat\Import\Job\ImportJobs;
use WerkraumMedia\ThueCat\Import\Job\Message\ImportFinished;
use WerkraumMedia\ThueCat\Service\WaitingMessages;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractImportTestCase;
use WerkraumMedia\ThueCat\Tests\Functional\FixedClock;

final class ImportChainTest extends AbstractImportTestCase
{
    private const int NOW = 1795527000;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Import/ImportChain.csv');
        // @phpstan-ignore method.notFound (functional test container is the Symfony Container, which has set())
        $this->getContainer()->set(ImportJobs::class, new ImportJobs(
            $this->get(ConnectionPool::class),
            new FixedClock(new DateTimeImmutable('@' . self::NOW)),
            $this->get(MessageBusInterface::class),
            $this->get(ImportJobItems::class),
            new WaitingMessages($this->get(ConnectionPool::class)),
        ));
    }

    // As the doctrine transport delivers it again after a consumer died.
    #[Test]
    public function redeliveredImportOfADoneItemIsDroppedAndSendsNothing(): void
    {
        $this->handleImportRoot(1, 1);

        $this->assertSame([], $this->queuedMessages());
        $this->assertSame(0, $this->countRows('tx_thuecat_trail'));
        $this->assertSame(4, $this->jobItems(1)[0]['duration']);
    }

    #[Test]
    public function redeliveredDeleteOfADoneItemIsDroppedAndSendsNothing(): void
    {
        $this->handleDeleteRoot(1, 2);

        $this->assertSame([], $this->queuedMessages());
        $this->assertSame(1, $this->jobItems(1)[1]['duration']);
    }

    #[Test]
    public function retriedImportSendsNoSecondSuccessor(): void
    {
        $this->expectFailureForUrl('https://thuecat.org/resources/test-trail-retried', 404);

        $this->handleImportRoot(1, 3);

        $this->assertSame([], $this->queuedMessages());
        $this->assertSame('done', $this->jobItemStates(1)[2]);
    }

    #[Test]
    public function retriedDeleteSendsNoSecondSuccessor(): void
    {
        $this->handleDeleteRoot(1, 3);

        $this->assertSame([], $this->queuedMessages());
        $this->assertSame('done', $this->jobItemStates(1)[2]);
    }

    #[Test]
    public function itemCountsItsAttemptAndRecordsItsSuccessor(): void
    {
        $this->handleDeleteRoot(1, 4);

        $this->assertSame(
            ['attempts' => 1, 'successor_sent' => 1],
            $this->getConnectionPool()->getConnectionForTable('tx_thuecat_import_job_item')
                ->select(['attempts', 'successor_sent'], 'tx_thuecat_import_job_item', ['uid' => 4])
                ->fetchAssociative()
        );
    }

    #[Test]
    public function finishingMessageIsSentAgainAfterAMinuteWhileAnItemIsPending(): void
    {
        $this->handleImportFinished(1);

        $this->assertSame('running', $this->jobOutcome(1)['status']);
        $this->assertEquals([new ImportFinished(1)], $this->queuedMessages());
        $this->assertSame(60000, $this->queuedMessageDelay());
    }

    #[Test]
    public function messageOfAnItemThatIsGoneIsDropped(): void
    {
        $this->handleImportRoot(1, 99);
        $this->handleDeleteRoot(1, 99);

        $this->assertSame([], $this->queuedMessages());
    }

    #[Test]
    public function messageOfAJobThatIsGoneIsDroppedAndLeavesTheItemUntouched(): void
    {
        $this->handleImportRoot(99, 4);
        $this->handleDeleteRoot(99, 4);
        $this->handleImportFinished(99);

        $this->assertSame([], $this->queuedMessages());
        $this->assertSame(['pending', 0], [$this->jobItems(1)[3]['state'], $this->jobItems(1)[3]['duration']]);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function itemHandlers(): iterable
    {
        yield 'import' => ['handleImportRoot'];
        yield 'delete' => ['handleDeleteRoot'];
    }

    // The failure is retried and the item dropped like any other, so the chain must go on first.
    #[DataProvider('itemHandlers')]
    #[Test]
    public function itemOfAJobWhoseConfigurationIsGoneSendsItsSuccessorAndFails(string $handle): void
    {
        try {
            $this->{$handle}(2, 5);
            $this->fail('The item went on without its configuration.');
        } catch (ImportConfigurationGoneException) {
        }

        $this->assertEquals([new ImportFinished(2)], $this->queuedMessages());
    }

    #[Test]
    public function jobWhoseConfigurationIsGoneIsStillFinished(): void
    {
        $this->handleImportFinished(3);

        $this->assertSame('done', $this->jobOutcome(3)['status']);
    }

    // The supervisor resumes a chain only after its items made no progress for a while.
    #[DataProvider('itemHandlers')]
    #[Test]
    public function beginningAnItemIsTheJobsProgress(string $handle): void
    {
        try {
            $this->{$handle}(2, 5);
        } catch (ImportConfigurationGoneException) {
        }

        $this->assertSame(self::NOW, $this->jobRow(2)['progressed_at']);
    }

    #[Test]
    public function redeliveredItemIsNoProgress(): void
    {
        $this->handleImportRoot(1, 1);
        $this->handleDeleteRoot(1, 2);

        $this->assertSame(0, $this->jobRow(1)['progressed_at']);
    }
}
