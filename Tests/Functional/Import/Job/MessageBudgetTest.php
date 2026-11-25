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
use TYPO3\CMS\Core\Cache\CacheManager;
use WerkraumMedia\ThueCat\Import\Progress\ImportPhase;
use WerkraumMedia\ThueCat\Import\Vocabulary\VocabularyIndex;
use WerkraumMedia\ThueCat\Import\Vocabulary\VocabularyIndexCache;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractImportTestCase;

final class MessageBudgetTest extends AbstractImportTestCase
{
    private const string TRAIL = 'https://thuecat.org/resources/e_106954656-oatour';

    private const string EVENT = 'https://cdb.int.thuecat.org/api/resources/e_locref-hubev';

    private const string CATEGORISED_EVENT = 'https://cdb.int.thuecat.org/api/resources/e_19542-hubev';

    #[Test]
    public function anItemOutlastingTheDefaultBudgetIsAbortedAtItsNextFetch(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../../Fixtures/Import/ImportsTrailWithRelations.php');
        $this->expectFetchForUrl(self::TRAIL, 'thuecat.org/resources/trail-with-content-responsible.json');
        $this->clockMessageDeadline(0, 0, 300);
        $job = $this->startJob(1);

        $abort = $this->handleImportRootAbortedByBudget($job, $this->jobItems($job)[0]['uid']);

        $this->assertSame(ImportPhase::Fetch, $abort->phase);
        $this->assertSame(300, $abort->budgetSeconds);
        $this->assertSame('Message budget of 300s exhausted after 300s while fetch.', $abort->getMessage());
        $this->assertSame(1795587176, $abort->getCode());
        $this->assertSame(0, $this->countRows('tx_thuecat_trail'));
        $this->assertSame('pending', $this->jobItems($job)[0]['state']);
    }

    #[Test]
    public function anItemInsideTheDefaultBudgetIsUnaffected(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../../Fixtures/Import/ImportsTrailWithRelations.php');
        $this->expectFetchForUrl(self::TRAIL, 'thuecat.org/resources/trail-with-content-responsible.json');
        $this->expectFetch('018132452787-ngbe.json');
        $this->clockMessageDeadline(0, 299);
        $job = $this->startJob(1);

        $this->handleImportRoot($job, $this->jobItems($job)[0]['uid']);

        $this->assertSame('done', $this->jobItems($job)[0]['state']);
        $this->assertSame([], $this->getLogEntriesOfType('runAborted'));
    }

    #[Test]
    public function theConfigurationsBudgetBoundsItsItems(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../../Fixtures/Import/ImportsTrailWithRelations.php');
        $this->expectFetchForUrl(self::TRAIL, 'thuecat.org/resources/trail-with-content-responsible.json');
        $this->getConnectionPool()->getConnectionForTable('tx_thuecat_import_configuration')
            ->update('tx_thuecat_import_configuration', ['message_budget' => 60], ['uid' => 1])
        ;
        $this->clockMessageDeadline(0, 0, 60);
        $job = $this->startJob(1);

        $abort = $this->handleImportRootAbortedByBudget($job, $this->jobItems($job)[0]['uid']);

        $this->assertSame(60, $abort->budgetSeconds);
    }

    #[Test]
    public function aPassUnderWayFinishesAndTheItemIsAbortedBeforeTheNext(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../../Fixtures/Import/ImportsTrailWithRelations.php');
        $this->expectFetchForUrl(self::TRAIL, 'thuecat.org/resources/trail-with-content-responsible.json');
        $this->expectFetch('018132452787-ngbe.json');
        $this->clockMessageDeadline(0, 0, 0, 0, 300);
        $job = $this->startJob(1);

        $abort = $this->handleImportRootAbortedByBudget($job, $this->jobItems($job)[0]['uid']);

        $this->assertSame(ImportPhase::Persist, $abort->phase);
        $trail = $this->fetchUidByRemoteId('tx_thuecat_trail', self::TRAIL);
        $this->assertSame([], $this->fetchColumnMatching('tx_thuecat_trail', 'uid', ['l18n_parent' => $trail]));
    }

    #[Test]
    public function anAbortAtAKeywordTermIsNoSkippedReference(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../../Fixtures/Import/ImportsTrailWithRelations.php');
        $this->expectFetchForUrl(self::TRAIL, 'thuecat.org/resources/trail-sharing-organisation-and-keyword.json');
        $this->expectFetch('018132452787-ngbe.json');
        $this->clockMessageDeadline(0, 0, 0, 300);
        $job = $this->startJob(1);

        $this->handleImportRootAbortedByBudget($job, $this->jobItems($job)[0]['uid']);

        $this->assertSame([], $this->getSkippedReferenceLogEntries());
    }

    #[Test]
    public function anAbortAtAnEventsLocationReferenceIsNoSkippedReference(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../../EventsImport/Fixtures/EventLocationReferencePreState.php');
        $this->expectFetchForUrl(self::EVENT, '../../../EventsImport/Fixtures/Guzzle/cdb.int.thuecat.org/api/resources/e_locref-hubev.json');
        $this->clockMessageDeadline(0, 0, 300);
        $job = $this->startJob(1);

        $this->handleImportRootAbortedByBudget($job, $this->jobItems($job)[0]['uid']);

        $this->assertSame([], $this->getSkippedReferenceLogEntries());
    }

    #[Test]
    public function anAbortAtAVocabularyRefreshIsNoVocabularyFailure(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../../EventsImport/Fixtures/EventCategoryImportPreState.php');
        $this->expectFetchForUrl(self::CATEGORISED_EVENT, '../../../EventsImport/Fixtures/Guzzle/cdb.int.thuecat.org/api/resources/e_19542-hubev.json');
        $cache = new VocabularyIndexCache($this->get(CacheManager::class)->getCache('thuecat_vocabulary'));
        $cache->write(new VocabularyIndex([]), time() - VocabularyIndexCache::STALE_AFTER);
        $this->clockMessageDeadline(0, 0, 300);
        $job = $this->startJob(1);

        $this->handleImportRootAbortedByBudget($job, $this->jobItems($job)[0]['uid']);

        $this->assertSame([], $this->getLogEntriesOfType('vocabularyStale'));
    }

    #[Test]
    public function theAbortIsLoggedOnTheJobsLogNamingItsItemAndPhase(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../../Fixtures/Import/ImportsTrailWithRelations.php');
        $this->expectFetchForUrl(self::TRAIL, 'thuecat.org/resources/trail-with-content-responsible.json');
        $this->clockMessageDeadline(0, 0, 300);
        $job = $this->startJob(1);

        $this->handleImportRootAbortedByBudget($job, $this->jobItems($job)[0]['uid']);

        $entries = $this->getLogEntriesOfType('runAborted');
        $this->assertSame(['error', self::TRAIL], [$entries[0]['severity'] ?? null, $entries[0]['remote_id'] ?? null]);
        $this->assertSame('fetch', $this->logEntryContext('runAborted')['phase'] ?? null);
        $this->assertSame([$this->jobLog($job)], $this->fetchColumnMatching('tx_thuecat_import_log_entry', 'import_log', ['type' => 'runAborted']));
    }
}
