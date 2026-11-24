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

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLog;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLogEntry\StorageFolderEmpty;
use WerkraumMedia\ThueCat\Domain\Repository\Backend\ImportLogRepository;
use WerkraumMedia\ThueCat\Import\SyncChannel\SyncWindow;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractSyncChannelImportTestCase;
use WerkraumMedia\ThueCat\Tests\Functional\FixedClock;

/**
 * Configuration 4 imports into folder 50, which starts empty. The event the
 * channel lists already lives in folder 10 of the same site; the trail and
 * the tourist attraction live nowhere yet.
 */
final class SyncChannelEmptyFolderTest extends AbstractSyncChannelImportTestCase
{
    private const string LIST_CHANGED_FIVE_YEARS = 'https://widgets.thuecat.org/api/sync/test-channel/list-changed?since=2021-10-19T12%3A00%3A00%2B02%3A00';

    private const string LIST_EXPIRED_FIVE_YEARS = 'https://widgets.thuecat.org/api/sync/test-channel/list-expired?since=2021-10-19T12%3A00%3A00%2B02%3A00';

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Import/SyncChannelEmptyFolder.csv');
        // An empty folder starts a window five years before now.
        // @phpstan-ignore method.notFound (functional test container is the Symfony Container, which has set())
        $this->getContainer()->set(SyncWindow::class, new SyncWindow(
            $this->get(ConnectionPool::class),
            $this->get(TcaSchemaFactory::class),
            new FixedClock(new DateTimeImmutable('2026-10-19 12:00', new DateTimeZone('Europe/Berlin'))),
        ));
    }

    #[Test]
    public function runWhoseEventsAllMatchElsewhereWarnsOfTheEmptyFolder(): void
    {
        $this->expectFiveYearRunServing('list-changed.json', 'test-event-1');

        $this->importConfiguration(4);

        $entries = $this->getLogEntriesOfType('storageFolderEmpty');
        $this->assertCount(1, $entries);
        $this->assertSame('warning', $entries[0]['severity']);
    }

    #[Test]
    public function runFillingItsFolderDoesNotWarn(): void
    {
        $this->expectChannelServingOneEvent();

        $this->importConfiguration(1);

        $this->assertSame([], $this->getLogEntriesOfType('storageFolderEmpty'));
    }

    #[Test]
    public function runStoringTrailsInItsFolderDoesNotWarn(): void
    {
        $this->expectFiveYearRunServing('list-changed-trail.json', 'test-trail-1');

        $this->importConfiguration(4);

        $this->assertSame(50, $this->fetchRowByRemoteId('tx_thuecat_trail', 'https://thuecat.org/resources/test-trail-1')['pid']);
        $this->assertSame([], $this->getLogEntriesOfType('storageFolderEmpty'));
    }

    #[Test]
    public function runStoringTouristAttractionsInItsFolderDoesNotWarn(): void
    {
        $this->expectFiveYearRunServing('list-changed-attraction.json', 'test-attraction-1');

        $this->importConfiguration(4);

        $this->assertSame(50, $this->fetchRowByRemoteId('tx_thuecat_tourist_attraction', 'https://thuecat.org/resources/test-attraction-1')['pid']);
        $this->assertSame([], $this->getLogEntriesOfType('storageFolderEmpty'));
    }

    #[Test]
    public function emptyFolderWarningKeepsTheRunAsAnchor(): void
    {
        $this->expectFiveYearRunServing('list-changed.json', 'test-event-1');

        $this->importConfiguration(4);

        $runCreated = $this->getConnectionPool()
            ->getConnectionForTable('tx_thuecat_import_log')
            ->select(['crdate'], 'tx_thuecat_import_log', ['configuration' => 4], [], ['uid' => 'DESC'], 1)
            ->fetchOne()
        ;
        $this->assertSame($runCreated, $this->get(ImportLogRepository::class)->findLatestSuccessfulCreation(4)?->getTimestamp());
    }

    // The module lists a warning only once the entry hydrates to its own class.
    #[Test]
    public function emptyFolderWarningIsListedInTheImportModule(): void
    {
        $this->expectFiveYearRunServing('list-changed.json', 'test-event-1');

        $this->importConfiguration(4);

        $log = null;
        foreach ($this->get(ImportLogRepository::class)->findAll() as $candidate) {
            if ($candidate instanceof ImportLog && $candidate->getConfigurationUid() === 4) {
                $log = $candidate;
            }
        }

        $this->assertInstanceOf(ImportLog::class, $log);
        $found = [];
        foreach ($log->getEntries() as $entry) {
            if ($entry instanceof StorageFolderEmpty) {
                $found[] = $entry;
            }
        }

        $this->assertCount(1, $found);
        $this->assertSame(['Storage folder 50 holds no record after the import.'], $log->getGroupedNotices()['storageFolderEmpty'] ?? []);
    }

    /**
     * @param string $changedList File name of the change list, which must list exactly $ids.
     */
    protected function expectFiveYearRunServing(string $changedList, string ...$ids): void
    {
        $this->expectFetchForUrl(self::LIST_CHANGED_FIVE_YEARS, 'widgets.thuecat.org/api/sync/test-channel/' . $changedList);
        $this->expectFetchForUrl(self::LIST_EXPIRED_FIVE_YEARS, 'widgets.thuecat.org/api/sync/test-channel/list-expired.json');
        $this->expectChannelRecords(...$ids);
    }
}
