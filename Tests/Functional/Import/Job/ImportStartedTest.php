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
use TYPO3\CMS\Core\Database\ConnectionPool;
use WerkraumMedia\ThueCat\Import\CategoryConfigurationException;
use WerkraumMedia\ThueCat\Import\Job\Handler\ImportStartedHandler;
use WerkraumMedia\ThueCat\Import\Job\ImportJobItems;
use WerkraumMedia\ThueCat\Import\Job\ImportJobs;
use WerkraumMedia\ThueCat\Import\Job\Message\ImportFinished;
use WerkraumMedia\ThueCat\Import\Job\Message\ImportRoot;
use WerkraumMedia\ThueCat\Import\Job\Message\ImportStarted;
use WerkraumMedia\ThueCat\Import\KeywordConfigurationException;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractImportTestCase;
use WerkraumMedia\ThueCat\Tests\Functional\FixedClock;

final class ImportStartedTest extends AbstractImportTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Import/ImportJob.csv');
    }

    #[Test]
    public function staticUrlsBecomePendingImportItemsInOrderEachOnce(): void
    {
        $job = $this->startJob(1);

        $this->assertSame([
            ['https://thuecat.org/resources/test-trail-1', 'import', 'pending'],
            ['https://thuecat.org/resources/test-trail-2', 'import', 'pending'],
            ['https://thuecat.org/resources/test-trail-3', 'import', 'pending'],
        ], array_map(static fn (array $item): array => [$item['target'], $item['action'], $item['state']], $this->jobItems($job)));
    }

    // The garbage collection task removes items by this date, as it removes their job by its own.
    #[Test]
    public function itemsCarryTheTimeTheyWereStored(): void
    {
        // @phpstan-ignore method.notFound (functional test container is the Symfony Container, which has set())
        $this->getContainer()->set(ImportJobItems::class, new ImportJobItems(
            $this->get(ConnectionPool::class),
            new FixedClock(new DateTimeImmutable('@1795526700')),
        ));

        $this->startJob(1);

        $this->assertSame(3, $this->countRowsMatching('tx_thuecat_import_job_item', ['crdate' => 1795526700]));
    }

    #[Test]
    public function jobRunsAndSendsItsFirstItemsMessage(): void
    {
        $job = $this->startJob(1);

        $this->assertSame('running', $this->jobRow($job)['status']);
        $this->assertEquals([new ImportRoot($job, $this->jobItems($job)[0]['uid'])], $this->queuedMessages());
    }

    #[Test]
    public function jobWithoutTargetsSendsItsFinishingMessage(): void
    {
        $job = $this->startJob(2);

        $this->assertSame([], $this->jobItems($job));
        $this->assertEquals([new ImportFinished($job)], $this->queuedMessages());
    }

    #[Test]
    public function jobsImportLogOpensWithTheEffectiveSettings(): void
    {
        $job = $this->startJob(1);

        $log = $this->jobLog($job);
        $this->assertSame(['effectiveSettings'], $this->fetchColumnMatching('tx_thuecat_import_log_entry', 'type', ['import_log' => $log]));
        $this->assertSame(1, $this->countRowsMatching('tx_thuecat_import_log', ['uid' => $log, 'configuration' => 1]));
    }

    #[Test]
    public function redeliveredStartIsDropped(): void
    {
        $job = $this->startJob(1);
        $this->takeQueuedMessages();

        $this->get(ImportStartedHandler::class)(new ImportStarted($job));

        $this->assertCount(3, $this->jobItems($job));
        $this->assertSame([], $this->queuedMessages());
    }

    #[Test]
    public function startOfAJobThatIsGoneIsDropped(): void
    {
        $this->get(ImportStartedHandler::class)(new ImportStarted(1));

        $this->assertSame([], $this->queuedMessages());
    }

    #[Test]
    public function jobOfADeletedConfigurationFails(): void
    {
        $job = $this->get(ImportJobs::class)->queueManual(1, false, false, 1);
        $this->assertIsInt($job);
        $this->updateImportConfiguration(1, ['deleted' => 1]);
        $this->takeQueuedMessages();
        $this->workaroundExtbaseConfiguration();

        $this->get(ImportStartedHandler::class)(new ImportStarted($job));

        $this->assertSame('failed', $this->jobRow($job)['status']);
        $this->assertSame([], $this->jobItems($job));
        $this->assertSame([], $this->queuedMessages());
    }

    /**
     * @return iterable<string, array{string, class-string}>
     */
    public static function rejectedAnchors(): iterable
    {
        yield 'category anchor outside the site' => ['RejectedCategoryAnchor.csv', CategoryConfigurationException::class];
        yield 'keyword anchor outside the site' => ['RejectedKeywordAnchor.csv', KeywordConfigurationException::class];
    }

    /**
     * @param class-string $rejection
     */
    #[DataProvider('rejectedAnchors')]
    #[Test]
    public function jobOfAConfigurationWithARejectedAnchorFails(string $fixture, string $rejection): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Import/' . $fixture);

        $job = $this->importJob(3);

        $this->assertSame('failed', $this->jobOutcome($job)['status']);
        $this->assertSame([], $this->jobItems($job));
        $this->assertSame($rejection, $this->logEntryContext('runFailed')['class'] ?? null);
    }
}
