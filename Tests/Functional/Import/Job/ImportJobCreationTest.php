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
use WerkraumMedia\ThueCat\Import\Job\ImportJobs;
use WerkraumMedia\ThueCat\Import\Job\Message\ImportStarted;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractBackendTestCase;

final class ImportJobCreationTest extends AbstractBackendTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Import/ImportJob.csv');
    }

    #[Test]
    public function queuedJobWaitsForItsStartingMessage(): void
    {
        $job = $this->get(ImportJobs::class)->queueManual(1, true, true, 1);

        $this->assertIsInt($job);
        $row = $this->getConnectionPool()->getConnectionForTable('tx_thuecat_import_job')
            ->select(['configuration', 'job_trigger', 'backend_user', 'skip_media', 'bypass_cache', 'status'], 'tx_thuecat_import_job', ['uid' => $job])
            ->fetchAssociative()
        ;
        $this->assertSame([
            'configuration' => 1,
            'job_trigger' => 'manual',
            'backend_user' => 1,
            'skip_media' => 1,
            'bypass_cache' => 1,
            'status' => 'queued',
        ], $row);
        $this->assertEquals([new ImportStarted($job)], $this->queuedMessages());
        $this->assertSame(0, $this->getConnectionPool()->getConnectionForTable('tx_thuecat_import_job_item')->count('*', 'tx_thuecat_import_job_item', []));
        $this->assertSame(0, $this->getConnectionPool()->getConnectionForTable('tx_thuecat_import_log')->count('*', 'tx_thuecat_import_log', []));
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function skipMediaSettings(): iterable
    {
        yield 'media imported' => [0];
        yield 'media skipped' => [1];
    }

    #[Test]
    #[DataProvider('skipMediaSettings')]
    public function scheduledJobTakesSkipMediaFromItsConfigurationAndNeverBypassesTheCache(int $skipMedia): void
    {
        $this->updateImportConfiguration(1, ['skip_media' => $skipMedia]);

        $job = $this->get(ImportJobs::class)->queueScheduled(1);

        $this->assertIsInt($job);
        $row = $this->getConnectionPool()->getConnectionForTable('tx_thuecat_import_job')
            ->select(['job_trigger', 'backend_user', 'skip_media', 'bypass_cache'], 'tx_thuecat_import_job', ['uid' => $job])
            ->fetchAssociative()
        ;
        $this->assertSame([
            'job_trigger' => 'schedule',
            'backend_user' => 0,
            'skip_media' => $skipMedia,
            'bypass_cache' => 0,
        ], $row);
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function earlierJobs(): iterable
    {
        yield 'queued' => ['queued', false];
        yield 'running' => ['running', false];
        yield 'stopping' => ['stopping', false];
        yield 'done' => ['done', true];
        yield 'failed' => ['failed', true];
        yield 'stopped' => ['stopped', true];
    }

    #[Test]
    #[DataProvider('earlierJobs')]
    public function onlyAnOpenJobRefusesASecondOne(string $status, bool $queued): void
    {
        $this->insertImportJob(['configuration' => 1, 'status' => $status]);

        $job = $this->get(ImportJobs::class)->queueScheduled(1);

        $this->assertSame($queued, is_int($job));
        $this->assertSame($queued ? 2 : 1, $this->countRows('tx_thuecat_import_job'));
        $this->assertCount($queued ? 1 : 0, $this->queuedMessages());
    }
}
