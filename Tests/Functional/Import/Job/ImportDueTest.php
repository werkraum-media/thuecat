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
use WerkraumMedia\ThueCat\Import\Job\Handler\ImportDueHandler;
use WerkraumMedia\ThueCat\Import\Job\Message\ImportDue;
use WerkraumMedia\ThueCat\Import\Job\Message\ImportStarted;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractBackendTestCase;

final class ImportDueTest extends AbstractBackendTestCase
{
    private const int DUE_AT = 1795572000;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Import/ImportJob.csv');
        $this->updateImportConfiguration(1, ['schedule_interval' => 86400, 'next_run' => self::DUE_AT]);
    }

    #[Test]
    public function currentDueMessageStartsAScheduledJob(): void
    {
        $this->get(ImportDueHandler::class)(new ImportDue(1, self::DUE_AT));

        $job = $this->getConnectionPool()->getConnectionForTable('tx_thuecat_import_job')
            ->select(['uid', 'configuration', 'job_trigger', 'status'], 'tx_thuecat_import_job')
            ->fetchAllAssociative()
        ;
        $this->assertSame([['uid' => 1, 'configuration' => 1, 'job_trigger' => 'schedule', 'status' => 'queued']], $job);
        $this->assertEquals([new ImportStarted(1)], $this->queuedMessages());
    }

    #[Test]
    public function rescheduledConfigurationDropsTheDueMessage(): void
    {
        $this->updateImportConfiguration(1, ['next_run' => self::DUE_AT + 3600]);

        $this->get(ImportDueHandler::class)(new ImportDue(1, self::DUE_AT));

        $this->assertSame(0, $this->countRows('tx_thuecat_import_job'));
        $this->assertSame([], $this->queuedMessages());
    }

    // The open job's end schedules the next start.
    #[Test]
    public function openJobDropsTheDueMessage(): void
    {
        $this->insertImportJob(['configuration' => 1, 'job_trigger' => 'manual', 'status' => 'running']);

        $this->get(ImportDueHandler::class)(new ImportDue(1, self::DUE_AT));

        $this->assertSame(1, $this->countRows('tx_thuecat_import_job'));
        $this->assertSame([], $this->queuedMessages());
    }
}
