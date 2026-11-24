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

use PHPUnit\Framework\Attributes\Test;
use WerkraumMedia\ThueCat\Import\Job\Handler\ImportStartedHandler;
use WerkraumMedia\ThueCat\Import\Job\ImportJobs;
use WerkraumMedia\ThueCat\Import\Job\ImportTrigger;
use WerkraumMedia\ThueCat\Import\Job\Message\ImportFinished;
use WerkraumMedia\ThueCat\Import\Job\Message\ImportRoot;
use WerkraumMedia\ThueCat\Import\Job\Message\ImportStarted;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractImportTestCase;

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

        $log = $this->jobRow($job)['import_log'];
        $this->assertIsInt($log);
        $this->assertSame(['effectiveSettings'], $this->fetchColumnMatching('tx_thuecat_import_log_entry', 'type', ['import_log' => $log]));
        $this->assertSame(1, $this->countRowsMatching('tx_thuecat_import_log', ['uid' => $log, 'configuration' => 1]));
    }

    #[Test]
    public function redeliveredStartIsDropped(): void
    {
        $job = $this->startJob(1);
        $this->getConnectionPool()->getConnectionForTable('sys_messenger_messages')->truncate('sys_messenger_messages');

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
        $job = $this->get(ImportJobs::class)->queue(1, ImportTrigger::Manual, false, false, 1);
        $this->assertIsInt($job);
        $this->getConnectionPool()->getConnectionForTable('tx_thuecat_import_configuration')
            ->update('tx_thuecat_import_configuration', ['deleted' => 1], ['uid' => 1])
        ;
        $this->getConnectionPool()->getConnectionForTable('sys_messenger_messages')->truncate('sys_messenger_messages');
        $this->workaroundExtbaseConfiguration();

        $this->get(ImportStartedHandler::class)(new ImportStarted($job));

        $this->assertSame('failed', $this->jobRow($job)['status']);
        $this->assertSame([], $this->jobItems($job));
        $this->assertSame([], $this->queuedMessages());
    }
}
