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
use WerkraumMedia\ThueCat\Tests\Functional\AbstractImportTestCase;

/**
 * Each root of a job is persisted on its own, so a target two roots share is found in the database by the second.
 */
final class SharedTargetsImportTest extends AbstractImportTestCase
{
    private const string ORGANISATION = 'https://thuecat.org/resources/018132452787-ngbe';

    private const string KEYWORD = 'keyword:https://thuecat.org/resources/887654277691-eatw';

    protected function setUp(): void
    {
        parent::setUp();

        $this->importPHPDataSet(__DIR__ . '/../../Fixtures/Import/ImportsTrailWithRelations.php');
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Import/SecondTrailUrl.csv');
        $this->expectFetchForUrl(
            'https://thuecat.org/resources/e_106954656-oatour',
            'thuecat.org/resources/trail-sharing-organisation-and-keyword.json'
        );
        $this->expectFetchForUrl(
            'https://thuecat.org/resources/test-trail-2',
            'thuecat.org/resources/second-trail-sharing-organisation-and-keyword.json'
        );
        $this->expectFetch('018132452787-ngbe.json', '887654277691-eatw.json', '192875159827-xfqk.json');
    }

    #[Test]
    public function twoTrailsShareOneOrganisation(): void
    {
        $this->importJob(1);

        $this->assertSame(1, $this->countRowsMatching('tx_thuecat_organisation', ['remote_id' => self::ORGANISATION, 'sys_language_uid' => 0]));
        $organisation = $this->fetchUidByRemoteId('tx_thuecat_organisation', self::ORGANISATION);
        $this->assertSame([$organisation, $organisation], $this->fetchColumnMatching('tx_thuecat_trail', 'managed_by', ['sys_language_uid' => 0]));
    }

    #[Test]
    public function twoTrailsShareOneKeywordTerm(): void
    {
        $this->importJob(1);

        $this->assertSame(1, $this->countRowsMatching('sys_category', ['remote_id' => self::KEYWORD, 'sys_language_uid' => 0]));
        $related = $this->getConnectionPool()->getConnectionForTable('sys_category_record_mm')->select(['uid_foreign'], 'sys_category_record_mm', [
            'uid_local' => $this->fetchUidByRemoteId('sys_category', self::KEYWORD),
            'tablenames' => 'tx_thuecat_trail',
            'fieldname' => 'keywords',
        ])->fetchFirstColumn();
        $trails = $this->fetchColumnMatching('tx_thuecat_trail', 'uid', ['sys_language_uid' => 0]);
        $this->assertCount(2, $trails);
        foreach ($trails as $trail) {
            $this->assertContains($trail, $related);
        }
    }
}
