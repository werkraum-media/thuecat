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
use WerkraumMedia\ThueCat\Import\ImportLogger;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractImportTestCase;

final class JobImportLogTest extends AbstractImportTestCase
{
    private const string FIRST_TRAIL = 'https://thuecat.org/resources/e_106954656-oatour';

    private const string SECOND_TRAIL = 'https://thuecat.org/resources/test-trail-2';

    protected function setUp(): void
    {
        parent::setUp();

        $this->importPHPDataSet(__DIR__ . '/../../Fixtures/Import/ImportsTrailWithRelations.php');
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Import/SecondTrailUrl.csv');
    }

    #[Test]
    public function jobsLogHoldsTheEntriesOfEveryItemEachNamingItsRoot(): void
    {
        $this->expectFetchForUrl(self::FIRST_TRAIL, 'thuecat.org/resources/trail-sharing-organisation-and-keyword.json');
        $this->expectFetchForUrl(self::SECOND_TRAIL, 'thuecat.org/resources/second-trail-sharing-organisation-and-keyword.json');
        $this->expectFetch('887654277691-eatw.json', '192875159827-xfqk.json');
        $this->expectNotFound('018132452787-ngbe');
        $this->expectNotFound('018132452787-ngbe');

        $job = $this->importJob(1);

        $log = $this->jobRow($job)['import_log'];
        $this->assertIsInt($log);
        $this->assertSame(
            [self::FIRST_TRAIL, self::SECOND_TRAIL],
            $this->fetchColumnMatching('tx_thuecat_import_log_entry', 'remote_id', ['import_log' => $log, 'type' => 'referenceSkipped'])
        );
    }

    // As a message that died before writing its log leaves them behind on the shared logger.
    #[Test]
    public function entriesLeftByAnotherJobsMessageNeverReachThisJobsLog(): void
    {
        $this->expectFetchForUrl(self::FIRST_TRAIL, 'thuecat.org/resources/trail-with-content-responsible.json');
        $this->expectFetch('018132452787-ngbe.json');
        $job = $this->startJob(1);
        $this->get(ImportLogger::class)->recordSkippedReference('tx_thuecat_trail', 'https://thuecat.org/resources/test-trail-of-another-job', 'managed_by', 'https://thuecat.org/resources/test-gone', 'Not found [test]');

        $this->handleImportRoot($job, $this->jobItems($job)[0]['uid']);

        $this->assertSame(0, $this->countRowsMatching('tx_thuecat_import_log_entry', ['remote_id' => 'https://thuecat.org/resources/test-trail-of-another-job']));
    }
}
