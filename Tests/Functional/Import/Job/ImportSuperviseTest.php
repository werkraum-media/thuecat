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
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WerkraumMedia\ThueCat\Import\Job\ImportJobItems;
use WerkraumMedia\ThueCat\Import\Job\ImportJobs;
use WerkraumMedia\ThueCat\Import\Job\ImportSupervisor;
use WerkraumMedia\ThueCat\Import\Job\Message\DeleteRoot;
use WerkraumMedia\ThueCat\Import\Job\Message\ImportDue;
use WerkraumMedia\ThueCat\Import\Job\Message\ImportFinished;
use WerkraumMedia\ThueCat\Import\Job\Message\ImportRoot;
use WerkraumMedia\ThueCat\Import\Job\Message\ImportStarted;
use WerkraumMedia\ThueCat\Import\Settings\ImportSettings;
use WerkraumMedia\ThueCat\Service\WaitingMessages;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractBackendTestCase;
use WerkraumMedia\ThueCat\Tests\Functional\FixedClock;

/**
 * Twice the default budget of 300 s is 600 s without progress.
 */
final class ImportSuperviseTest extends AbstractBackendTestCase
{
    private const int NOW = 1795527000;

    private const int TWO_MINUTES_AGO = 1795526880;

    /**
     * @var list<object>
     */
    private array $waiting;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Import/ImportSupervise.csv');
        // @phpstan-ignore method.notFound (functional test container is the Symfony Container, which has set())
        $this->getContainer()->set(ImportSupervisor::class, new ImportSupervisor(
            $this->get(ConnectionPool::class),
            new FixedClock(new DateTimeImmutable('@' . self::NOW)),
            $this->get(MessageBusInterface::class),
            new WaitingMessages($this->get(ConnectionPool::class)),
            $this->get(ImportJobs::class),
            $this->get(ImportJobItems::class),
            $this->get(ImportSettings::class),
        ));
        $this->waiting = [new ImportDue(2, self::TWO_MINUTES_AGO), new ImportFinished(4), new ImportStarted(8)];
        foreach ($this->waiting as $message) {
            $this->get(MessageBusInterface::class)->dispatch($message, [new DelayStamp(60000)]);
        }
    }

    #[Test]
    public function dueConfigurationWithoutJobOrDueMessageGetsOne(): void
    {
        $this->superviseImports();

        $this->assertContainsEquals(new ImportDue(1, self::TWO_MINUTES_AGO), $this->queuedMessages());
    }

    #[Test]
    public function configurationDueThisSecondGetsItsDueMessage(): void
    {
        $this->superviseImports();

        $this->assertContainsEquals(new ImportDue(8, self::NOW), $this->queuedMessages());
    }

    #[Test]
    public function scheduleSetForTheFirstTimeGetsItsDueMessage(): void
    {
        $this->superviseImports();

        $this->assertContainsEquals(new ImportDue(6, 1700000000), $this->queuedMessages());
    }

    #[Test]
    public function brokenChainGetsTheMessageOfItsFirstPendingItem(): void
    {
        $this->superviseImports();

        $this->assertContainsEquals(new DeleteRoot(1, 2), $this->queuedMessages());
    }

    // The item's handler finds the job stopping and gives up the rest.
    #[Test]
    public function brokenChainOfAStoppingJobGetsTheMessageOfItsFirstPendingItem(): void
    {
        $this->superviseImports();

        $this->assertContainsEquals(new ImportRoot(10, 9), $this->queuedMessages());
    }

    #[Test]
    public function brokenChainWithoutPendingItemGetsItsFinishingMessage(): void
    {
        $this->superviseImports();

        $this->assertContainsEquals(new ImportFinished(2), $this->queuedMessages());
    }

    #[Test]
    public function budgetOfTheJobsConfigurationDecidesWhenItsChainIsBroken(): void
    {
        $this->superviseImports();

        $this->assertContainsEquals(new ImportRoot(6, 8), $this->queuedMessages());
    }

    #[Test]
    public function queuedJobWithoutItsStartingMessageGetsItAgain(): void
    {
        $this->superviseImports();

        $this->assertContainsEquals(new ImportStarted(7), $this->queuedMessages());
    }

    /**
     * Not for a waiting due message, a manual-only or not yet due configuration, a due one with an open job, a chain
     * with a waiting message or with progress within twice the budget, a queued job whose start waits, a closed job.
     */
    #[Test]
    public function nothingElseIsSent(): void
    {
        $this->superviseImports();

        $this->assertEqualsCanonicalizing(
            [
                ...$this->waiting,
                new ImportDue(1, self::TWO_MINUTES_AGO),
                new ImportDue(6, 1700000000),
                new ImportDue(8, self::NOW),
                new DeleteRoot(1, 2),
                new ImportFinished(2),
                new ImportRoot(6, 8),
                new ImportRoot(10, 9),
                new ImportStarted(7),
            ],
            $this->queuedMessages()
        );
    }

    #[Test]
    public function stagingFoldersOfClosedJobsAreSwept(): void
    {
        $folder = $this->createFileStorage();
        $this->updateImportConfiguration(3, ['file_folder' => '1:/thuecat/']);
        GeneralUtility::mkdir($folder . '/_thuecat_import_job9_item1');

        $this->superviseImports();

        $this->assertSame([], $this->stagingFoldersIn($folder));
    }
}
