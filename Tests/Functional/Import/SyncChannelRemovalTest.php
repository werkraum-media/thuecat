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

namespace WerkraumMedia\ThueCat\Tests\Functional\Import;

use PHPUnit\Framework\Attributes\Test;
use WerkraumMedia\ThueCat\Import\Job\Message\ImportFinished;
use WerkraumMedia\ThueCat\Import\SyncChannel\SyncChannelReaper;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractSyncChannelImportTestCase;
use WerkraumMedia\ThueCat\Tests\Functional\SyncChannelReaperFailingStub;

final class SyncChannelRemovalTest extends AbstractSyncChannelImportTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Import/SyncChannelRemoval.csv');
    }

    #[Test]
    public function removedEventIsDeleted(): void
    {
        $this->expectChannelLists('list-changed-removed.json', 'list-expired-expired.json');

        $this->importJob(1);

        $this->assertSame(0, $this->fetchUidByRemoteId('tx_events_domain_model_event', 'https://thuecat.org/resources/test-event-removed'));
    }

    #[Test]
    public function removedEventIsDeletedWhenTheJobImportsEventsAsWell(): void
    {
        $this->expectChannelLists('list-changed-removed-with-import.json');
        $this->expectChannelRecords('test-event-1');

        $this->importJob(1);

        $this->assertSame(0, $this->fetchUidByRemoteId('tx_events_domain_model_event', 'https://thuecat.org/resources/test-event-removed'));
        $this->assertGreaterThan(0, $this->fetchUidByRemoteId('tx_events_domain_model_event', 'https://thuecat.org/resources/test-event-1'));
    }

    #[Test]
    public function expiredEventIsDeleted(): void
    {
        $this->expectChannelLists('list-changed-removed.json', 'list-expired-expired.json');

        $this->importJob(1);

        $this->assertSame(0, $this->fetchUidByRemoteId('tx_events_domain_model_event', 'https://thuecat.org/resources/test-event-expired'));
    }

    #[Test]
    public function removedEventInAnotherFolderOfTheSiteIsDeleted(): void
    {
        $this->expectChannelLists('list-changed-removed.json', 'list-expired-expired.json');

        $this->importJob(1);

        $this->assertSame(0, $this->fetchUidByRemoteId('tx_events_domain_model_event', 'https://thuecat.org/resources/test-event-removed-elsewhere'));
    }

    #[Test]
    public function removedEventOfAnotherSiteIsKept(): void
    {
        $this->expectChannelLists('list-changed-removed.json', 'list-expired-expired.json');

        $this->importJob(1);

        $this->assertSame(14, $this->fetchUidByRemoteId('tx_events_domain_model_event', 'https://thuecat.org/resources/test-event-removed-other-site'));
    }

    #[Test]
    public function unknownRemovedIdDeletesNothingAndLogsNothing(): void
    {
        $this->expectChannelLists('list-changed-removed.json', 'list-expired-expired.json');

        $this->importJob(1);

        $this->assertSame(1, $this->fetchUidByRemoteId('tx_events_domain_model_event', 'https://thuecat.org/resources/test-event-0'));
        $this->assertSame([], $this->getLogEntriesOfType('dataHandlerError'));
    }

    // One job for every record kind: Infection skips the reaper's mutants once this class outruns the mutant timeout.
    #[Test]
    public function removedRecordsOfEveryTableAreDeletedWithTheirChildrenWithinTheSite(): void
    {
        $this->expectChannelLists('list-changed-removed-records.json');

        $this->importJob(1);

        $this->assertSame(0, $this->fetchUidByRemoteId('tx_thuecat_trail', 'https://thuecat.org/resources/test-trail-removed'));
        $this->assertSame(0, $this->fetchUidByRemoteId('tx_thuecat_parking_facility', 'https://thuecat.org/resources/test-parking-removed'));
        $this->assertSame(0, $this->fetchUidByRemoteId('tx_thuecat_tourist_attraction', 'https://thuecat.org/resources/test-place-removed-twice'));
        $this->assertSame(0, $this->fetchUidByRemoteId('tx_thuecat_place', 'https://thuecat.org/resources/test-place-removed-twice'));
        $this->assertSame(22, $this->fetchUidByRemoteId('tx_thuecat_trail', 'https://thuecat.org/resources/test-trail-removed-other-site'));
        $this->assertSame(0, $this->countRows('tx_thuecat_trail_way_type'));
        $this->assertSame(0, $this->countRows('tx_thuecat_trail_location'));
        $this->assertSame(0, $this->countRows('tx_thuecat_trail_condition'));
        $this->assertSame(0, $this->countRows('tx_thuecat_address'));
        $this->assertSame(0, $this->countRows('tx_thuecat_opening_hours'));
    }

    #[Test]
    public function deleteItemDeletesOnlyItsOwnRecord(): void
    {
        $this->expectChannelLists('list-changed-removed-records.json');
        $job = $this->startJob(1);

        $this->handleDeleteRoot($job, $this->jobItems($job)[0]['uid']);

        $this->assertSame(0, $this->fetchUidByRemoteId('tx_thuecat_trail', 'https://thuecat.org/resources/test-trail-removed'));
        $this->assertSame(31, $this->fetchUidByRemoteId('tx_thuecat_parking_facility', 'https://thuecat.org/resources/test-parking-removed'));
    }

    #[Test]
    public function deleteItemIsDoneAfterSendingItsSuccessor(): void
    {
        $this->expectChannelLists('list-changed-removed-with-import.json');
        $job = $this->startJob(1);

        $this->handleDeleteRoot($job, $this->jobItems($job)[1]['uid']);

        $this->assertSame('done', $this->jobItemStates($job)[1]);
        $this->assertEquals([new ImportFinished($job)], $this->queuedMessages());
    }

    #[Test]
    public function deletedEventTakesItsTranslationDatesAndFileReferencesWithIt(): void
    {
        $this->expectChannelLists('list-changed-removed.json', 'list-expired-expired.json');

        $this->importJob(1);

        $this->assertSame([], $this->fetchTranslationsByParent('tx_events_domain_model_event', 11));
        $this->assertSame(0, $this->countRows('tx_events_domain_model_date'));
        $this->assertSame(0, $this->countRows('sys_file_reference'));
    }

    #[Test]
    public function failedImportItemDoesNotPreventTheDelete(): void
    {
        $this->expectChannelLists('list-changed-removed-with-import.json');
        $job = $this->startJob(1);
        [$importItem, $deleteItem] = $this->jobItems($job);
        $this->getConnectionPool()->getConnectionForTable('tx_thuecat_import_job_item')
            ->update('tx_thuecat_import_job_item', ['state' => 'failed', 'attempts' => 3], ['uid' => $importItem['uid']])
        ;

        $this->handleDeleteRoot($job, $deleteItem['uid']);

        $this->assertSame(0, $this->fetchUidByRemoteId('tx_events_domain_model_event', 'https://thuecat.org/resources/test-event-removed'));
    }

    #[Test]
    public function unreadableListDeletesNothing(): void
    {
        $this->expectFetchForUrl(self::LIST_CHANGED, 'widgets.thuecat.org/api/sync/test-channel/list-changed-removed.json');
        $this->expectFailureForUrl(self::LIST_EXPIRED, 403);

        $this->importJob(1);

        $this->assertSame(0, $this->countRows('tx_thuecat_import_job_item'));
        $this->assertSame(11, $this->fetchUidByRemoteId('tx_events_domain_model_event', 'https://thuecat.org/resources/test-event-removed'));
    }

    #[Test]
    public function failedDeletionIsADataHandlerErrorAndKeepsTheJobAsLastSuccessfulRun(): void
    {
        // @phpstan-ignore method.notFound (functional test container is the Symfony Container, which has set())
        $this->getContainer()->set(SyncChannelReaper::class, new SyncChannelReaperFailingStub());
        $this->expectChannelLists('list-changed-removed-with-import.json');
        $this->expectChannelRecords('test-event-1');

        $job = $this->importJob(1);

        $log = $this->jobLog($job);
        $this->assertSame(1, $this->countRowsMatching('tx_thuecat_import_log_entry', [
            'import_log' => $log,
            'type' => 'dataHandlerError',
            'message' => SyncChannelReaperFailingStub::ERROR,
        ]));
        $this->assertSame($this->jobRow($job)['started_at'], $this->importConfigurationValue(1, 'last_successful_run'));
    }
}
