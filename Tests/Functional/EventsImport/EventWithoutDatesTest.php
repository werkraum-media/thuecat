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

namespace WerkraumMedia\ThueCat\Tests\Functional\EventsImport;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\DateTimeAspect;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLog;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLogEntry\EventWithoutDates;
use WerkraumMedia\ThueCat\Domain\Repository\Backend\ImportLogRepository;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractImportTestCase;

/**
 * Four routes to zero dates: no schedule, only an unusable day, all excepted,
 * all past. One event per route, imported in a single run.
 */
class EventWithoutDatesTest extends AbstractImportTestCase
{
    protected string $fixtureGuzzleBase = __DIR__ . '/Fixtures/Guzzle';
    protected string $fixtureDomain = 'cdb.int.thuecat.org';
    protected string $fixturePath = 'api/resources';

    private const REMOTE_ID_PREFIX = 'https://thuecat.org/resources/e_7cbe5bb1-160b-4916-802c-c64dd2f1bf9e-';

    protected function setUp(): void
    {
        parent::setUp();

        // Fixes what counts as past; the `past` fixture sits in 2020.
        $this->getContainer()->get(Context::class)->setAspect(
            'date',
            new DateTimeAspect(new DateTimeImmutable('2026-12-01T00:00:00+00:00'))
        );
    }

    #[Test]
    public function anEventWithoutDatesIsLoggedAsWarning(): void
    {
        $this->importDatelessEvents();

        $entries = $this->getLogEntriesOfType('eventWithoutDates');

        $this->assertNotSame([], $entries, 'A dateless event must be reported.');
        foreach ($entries as $entry) {
            $this->assertSame('warning', $entry['severity']);
            $this->assertSame('tx_events_domain_model_event', $entry['table_name']);
        }
    }

    #[Test]
    public function everyRouteToZeroDatesIsReportedAlike(): void
    {
        $this->importDatelessEvents();

        $this->assertSame([
            self::REMOTE_ID_PREFIX . 'excepted',
            self::REMOTE_ID_PREFIX . 'nosched',
            self::REMOTE_ID_PREFIX . 'past',
            self::REMOTE_ID_PREFIX . 'unusable',
        ], $this->reportedRemoteIds());
    }

    #[Test]
    public function eachDatelessEventGetsItsOwnEntry(): void
    {
        $this->importDatelessEvents();

        $remoteIds = $this->reportedRemoteIds();

        $this->assertCount(4, $remoteIds);
        $this->assertSame($remoteIds, array_values(array_unique($remoteIds)));
    }

    #[Test]
    public function theEventRecordIsStillImported(): void
    {
        $this->importDatelessEvents();

        $rows = $this->getConnectionPool()
            ->getConnectionForTable('tx_events_domain_model_event')
            ->select(['remote_id'], 'tx_events_domain_model_event')
            ->fetchAllAssociative()
        ;

        $this->assertCount(4, $rows, 'Reporting must not stop the record being written.');
    }

    #[Test]
    public function aRunFindingOnlyDatelessEventsReportsWarning(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventWithoutDatesPreState.php');
        $this->expectFetch('e_7cbe5bb1-nosched.json');
        $this->expectFetch('e_7cbe5bb1-unusable.json');
        $this->expectFetch('e_7cbe5bb1-excepted.json');
        $this->expectFetch('e_7cbe5bb1-past.json');

        $severity = $this->importConfigurationReturningSeverity(1);

        $this->assertSame('warning', $severity);
    }

    #[Test]
    public function theEntryNamesTheEventByItsTitle(): void
    {
        $this->importDatelessEvents();

        $entries = $this->getLogEntriesOfType('eventWithoutDates');

        $this->assertNotSame([], $entries);
        foreach ($entries as $entry) {
            $this->assertIsString($entry['message']);
            $this->assertStringContainsString('Test-Altstadtführung', $entry['message'], 'The message must name the event an editor is looking for.');
        }
    }

    /**
     * Also the control: an always-firing detection site would report the sibling
     * too. Its dates must lie AFTER the pinned clock — a past event is filtered
     * out and is legitimately dateless.
     */
    #[Test]
    public function siblingRecordsInTheSameRunAreUnaffected(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventWithoutDatesSiblingPreState.php');
        $this->expectFetch('e_7cbe5bb1-nosched.json');
        $this->expectFetch('e_7cbe5bb1-tdm.json');

        $this->importConfiguration(1);

        $dateCount = $this->getConnectionPool()
            ->getConnectionForTable('tx_events_domain_model_date')
            ->count('uid', 'tx_events_domain_model_date', [])
        ;

        $this->assertGreaterThan(0, $dateCount, 'The healthy sibling keeps its dates.');
        $this->assertSame([self::REMOTE_ID_PREFIX . 'nosched'], $this->reportedRemoteIds(), 'Only the dateless event is reported.');
    }

    /** Without the fallback the message would read: Event "" was imported… */
    #[Test]
    public function anEventWithoutATitleIsNamedByItsIdentifier(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventWithoutDatesUntitledPreState.php');
        $this->expectFetch('e_7cbe5bb1-untitled.json');

        $this->importConfiguration(1);

        $entries = $this->getLogEntriesOfType('eventWithoutDates');

        $this->assertCount(1, $entries);
        $message = $entries[0]['message'];
        $this->assertIsString($message);
        $this->assertStringNotContainsString('""', $message);
        $this->assertStringContainsString(self::REMOTE_ID_PREFIX . 'untitled', $message);
    }

    /** Type map and class map are separate; the rest passes with only the first. */
    #[Test]
    public function theEntryHydratesToItsOwnType(): void
    {
        $this->importDatelessEvents();

        $log = $this->get(ImportLogRepository::class)->findAll()->getFirst();
        $this->assertInstanceOf(ImportLog::class, $log, 'The run must have written a log.');

        $found = [];
        foreach ($log->getEntries() as $entry) {
            if ($entry instanceof EventWithoutDates) {
                $found[] = $entry;
            }
        }

        $this->assertCount(4, $found, 'Each dateless event hydrates to EventWithoutDates.');
    }

    private function importDatelessEvents(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventWithoutDatesPreState.php');
        $this->expectFetch('e_7cbe5bb1-nosched.json');
        $this->expectFetch('e_7cbe5bb1-unusable.json');
        $this->expectFetch('e_7cbe5bb1-excepted.json');
        $this->expectFetch('e_7cbe5bb1-past.json');

        $this->importConfiguration(1);
    }

    /**
     * @return list<string>
     */
    private function reportedRemoteIds(): array
    {
        $remoteIds = [];
        foreach ($this->getLogEntriesOfType('eventWithoutDates') as $entry) {
            $this->assertIsString($entry['remote_id']);
            $remoteIds[] = $entry['remote_id'];
        }
        sort($remoteIds);

        return $remoteIds;
    }
}
