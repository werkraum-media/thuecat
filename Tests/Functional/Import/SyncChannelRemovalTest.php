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
use WerkraumMedia\ThueCat\Domain\Repository\Backend\ImportConfigurationRepository;
use WerkraumMedia\ThueCat\Domain\Repository\Backend\ImportLogRepository;
use WerkraumMedia\ThueCat\Import\Importer;
use WerkraumMedia\ThueCat\Import\Importer\FetchData\InvalidResponseException;
use WerkraumMedia\ThueCat\Import\SyncChannel\SyncChannelReaper;
use WerkraumMedia\ThueCat\Import\Watchdog\RunDeadline;
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
        $this->expectFetchForUrl(self::LIST_CHANGED, 'widgets.thuecat.org/api/sync/test-channel/list-changed-removed.json');
        $this->expectFetchForUrl(self::LIST_EXPIRED, 'widgets.thuecat.org/api/sync/test-channel/list-expired-expired.json');

        $this->importConfiguration(1);

        self::assertSame(0, $this->fetchUidByRemoteId('tx_events_domain_model_event', 'https://thuecat.org/resources/test-event-removed'));
    }

    #[Test]
    public function removedEventIsDeletedWhenTheRunImportsEventsAsWell(): void
    {
        $this->expectFetchForUrl(self::LIST_CHANGED, 'widgets.thuecat.org/api/sync/test-channel/list-changed-removed-with-import.json');
        $this->expectFetchForUrl(self::LIST_EXPIRED, 'widgets.thuecat.org/api/sync/test-channel/list-expired.json');
        $this->expectFetchForUrl(self::RECORD . 'test-event-1', 'widgets.thuecat.org/api/sync/test-channel/jsonld/test-event-1.json');

        $this->importConfiguration(1);

        self::assertSame(0, $this->fetchUidByRemoteId('tx_events_domain_model_event', 'https://thuecat.org/resources/test-event-removed'));
        self::assertGreaterThan(0, $this->fetchUidByRemoteId('tx_events_domain_model_event', 'https://thuecat.org/resources/test-event-1'));
    }

    #[Test]
    public function expiredEventIsDeleted(): void
    {
        $this->expectFetchForUrl(self::LIST_CHANGED, 'widgets.thuecat.org/api/sync/test-channel/list-changed-removed.json');
        $this->expectFetchForUrl(self::LIST_EXPIRED, 'widgets.thuecat.org/api/sync/test-channel/list-expired-expired.json');

        $this->importConfiguration(1);

        self::assertSame(0, $this->fetchUidByRemoteId('tx_events_domain_model_event', 'https://thuecat.org/resources/test-event-expired'));
    }

    #[Test]
    public function removedEventInAnotherFolderOfTheSiteIsDeleted(): void
    {
        $this->expectFetchForUrl(self::LIST_CHANGED, 'widgets.thuecat.org/api/sync/test-channel/list-changed-removed.json');
        $this->expectFetchForUrl(self::LIST_EXPIRED, 'widgets.thuecat.org/api/sync/test-channel/list-expired-expired.json');

        $this->importConfiguration(1);

        self::assertSame(0, $this->fetchUidByRemoteId('tx_events_domain_model_event', 'https://thuecat.org/resources/test-event-removed-elsewhere'));
    }

    #[Test]
    public function removedEventOfAnotherSiteIsKept(): void
    {
        $this->expectFetchForUrl(self::LIST_CHANGED, 'widgets.thuecat.org/api/sync/test-channel/list-changed-removed.json');
        $this->expectFetchForUrl(self::LIST_EXPIRED, 'widgets.thuecat.org/api/sync/test-channel/list-expired-expired.json');

        $this->importConfiguration(1);

        self::assertSame(14, $this->fetchUidByRemoteId('tx_events_domain_model_event', 'https://thuecat.org/resources/test-event-removed-other-site'));
    }

    #[Test]
    public function unknownRemovedIdDeletesNothingAndLogsNothing(): void
    {
        $this->expectFetchForUrl(self::LIST_CHANGED, 'widgets.thuecat.org/api/sync/test-channel/list-changed-removed.json');
        $this->expectFetchForUrl(self::LIST_EXPIRED, 'widgets.thuecat.org/api/sync/test-channel/list-expired-expired.json');

        $this->importConfiguration(1);

        self::assertSame(1, $this->fetchUidByRemoteId('tx_events_domain_model_event', 'https://thuecat.org/resources/test-event-0'));
        self::assertSame([], $this->getLogEntriesOfType('dataHandlerError'));
    }

    #[Test]
    public function deletedEventTakesItsTranslationDatesAndFileReferencesWithIt(): void
    {
        $this->expectFetchForUrl(self::LIST_CHANGED, 'widgets.thuecat.org/api/sync/test-channel/list-changed-removed.json');
        $this->expectFetchForUrl(self::LIST_EXPIRED, 'widgets.thuecat.org/api/sync/test-channel/list-expired-expired.json');

        $this->importConfiguration(1);

        self::assertSame([], $this->fetchTranslationsByParent('tx_events_domain_model_event', 11));
        self::assertSame(0, $this->countRows('tx_events_domain_model_date'));
        self::assertSame(0, $this->countRows('sys_file_reference'));
    }

    #[Test]
    public function abortedRunDeletesNothing(): void
    {
        $this->expectErrors = true;
        $this->expectFetchForUrl(self::LIST_CHANGED, 'widgets.thuecat.org/api/sync/test-channel/list-changed-removed-with-import.json');
        $this->expectFetchForUrl(self::LIST_EXPIRED, 'widgets.thuecat.org/api/sync/test-channel/list-expired.json');
        $this->workaroundExtbaseConfiguration();
        $configuration = $this->get(ImportConfigurationRepository::class)->findOneByUid(1);
        self::assertNotNull($configuration);

        $this->get(Importer::class)->importConfiguration($configuration, null, new RunDeadline(10, microtime(true) - 20.0));

        self::assertCount(1, $this->getLogEntriesOfType('runAborted'));
        self::assertSame(11, $this->fetchUidByRemoteId('tx_events_domain_model_event', 'https://thuecat.org/resources/test-event-removed'));
    }

    #[Test]
    public function unreadableListDeletesNothing(): void
    {
        $this->expectFetchForUrl(self::LIST_CHANGED, 'widgets.thuecat.org/api/sync/test-channel/list-changed-removed.json');
        $this->expectFailureForUrl(self::LIST_EXPIRED, 403);

        try {
            $this->importConfiguration(1);
            self::fail('The run went on without the expiry list.');
        } catch (InvalidResponseException) {
        }

        self::assertSame(11, $this->fetchUidByRemoteId('tx_events_domain_model_event', 'https://thuecat.org/resources/test-event-removed'));
    }

    #[Test]
    public function failedDeletionIsADataHandlerErrorAndKeepsTheRunAsAnchor(): void
    {
        // @phpstan-ignore method.notFound (functional test container is the Symfony Container, which has set())
        $this->getContainer()->set(SyncChannelReaper::class, new SyncChannelReaperFailingStub());
        $this->expectFetchForUrl(self::LIST_CHANGED, 'widgets.thuecat.org/api/sync/test-channel/list-changed-removed.json');
        $this->expectFetchForUrl(self::LIST_EXPIRED, 'widgets.thuecat.org/api/sync/test-channel/list-expired-expired.json');

        $this->importConfiguration(1);

        $errors = $this->getLogEntriesOfType('dataHandlerError');
        self::assertCount(1, $errors);
        self::assertSame(SyncChannelReaperFailingStub::ERROR, $errors[0]['message']);
        $runCreated = $this->getConnectionPool()
            ->getConnectionForTable('tx_thuecat_import_log')
            ->select(['crdate'], 'tx_thuecat_import_log', ['configuration' => 1], [], ['uid' => 'DESC'], 1)
            ->fetchOne()
        ;
        self::assertSame(
            $runCreated,
            $this->get(ImportLogRepository::class)->findLatestSuccessfulCreation(1)?->getTimestamp()
        );
    }
}
