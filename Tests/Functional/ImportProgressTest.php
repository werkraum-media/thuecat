<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional;

/*
 * Copyright (C) 2026 werkraum-media
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 */

use PHPUnit\Framework\Attributes\Test;
use WerkraumMedia\ThueCat\Domain\Repository\Backend\ImportConfigurationRepository;
use WerkraumMedia\ThueCat\Import\Importer;
use WerkraumMedia\ThueCat\Import\Progress\ImportPhase;
use WerkraumMedia\ThueCat\Import\Progress\ImportProgress;

class ImportProgressTest extends AbstractImportTestCase
{
    #[Test]
    public function runWithoutAListenerBehavesAsBefore(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsFreshOrganization.php');
        $this->expectFetch('018132452787-ngbe.json');

        $this->importConfiguration(1);

        $this->assertPHPDataSet(__DIR__ . '/Assertions/Import/ImportsFreshOrganization.php');
    }

    #[Test]
    public function fetchPhaseCountsTheConfiguredUrls(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsTown.php');
        $this->expectFetch('043064193523-jcyt.json');
        $this->expectFetch('018132452787-ngbe.json');

        $listener = $this->importWithListener(1);

        $fetches = $listener->ofPhase(ImportPhase::Fetch);
        self::assertCount(1, $fetches, 'One event per configured root URL.');
        self::assertSame(1, $fetches[0]->current);
        self::assertSame(1, $fetches[0]->total, 'Total is the configured URL count.');
    }

    #[Test]
    public function persistPhaseReportsOneEventPerPass(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsFreshOrganization.php');
        $this->expectFetch('018132452787-ngbe.json');

        $listener = $this->importWithListener(1);

        $passes = $listener->ofPhase(ImportPhase::Persist);
        self::assertNotSame([], $passes, 'Persisting emits at least one event.');
        foreach ($passes as $index => $pass) {
            self::assertSame($index + 1, $pass->current, 'Passes are numbered from one.');
            self::assertNotNull($pass->total, 'The pass budget is knowable up front.');
        }
    }

    // The recursion is the run's longest silent stretch if it never emits.
    #[Test]
    public function resolvingEmitsWithoutATotal(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsTown.php');
        $this->expectFetch('043064193523-jcyt.json');
        $this->expectFetch('018132452787-ngbe.json');

        $listener = $this->importWithListener(1);

        $resolves = $listener->ofPhase(ImportPhase::Resolve);
        self::assertNotSame([], $resolves, 'Nested fetching reports progress.');
        foreach ($resolves as $resolve) {
            self::assertNull($resolve->total, 'No total is knowable while resolving.');
            self::assertNotSame('', $resolve->label, 'The resource being fetched is named.');
        }
    }

    #[Test]
    public function phasesAreReportedInRunOrder(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsFreshOrganization.php');
        $this->expectFetch('018132452787-ngbe.json');

        $listener = $this->importWithListener(1);

        $order = $listener->phaseOrder();

        self::assertSame('fetch', $order[0] ?? null, 'A run starts by fetching.');
        self::assertSame('finish', end($order) ?: null, 'A run ends by finishing.');
        self::assertContains('persist', $order);
        self::assertContains('log', $order);
    }

    #[Test]
    public function everyEventNamesItsPhase(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsFreshOrganization.php');
        $this->expectFetch('018132452787-ngbe.json');

        $listener = $this->importWithListener(1);

        self::assertNotSame([], $listener->events);
        foreach ($listener->events as $event) {
            self::assertInstanceOf(ImportProgress::class, $event);
        }
    }

    private function importWithListener(int $uid): RecordingProgressListener
    {
        $this->workaroundExtbaseConfiguration();
        $configuration = $this->get(ImportConfigurationRepository::class)->findOneByUid($uid);
        self::assertNotNull($configuration, 'Fixture configuration uid=' . $uid . ' not found');

        $listener = new RecordingProgressListener();
        $this->get(Importer::class)->importConfiguration($configuration, $listener);

        return $listener;
    }
}
