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

namespace WerkraumMedia\ThueCat\Tests\Functional\Removal;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Registry;
use WerkraumMedia\ThueCat\Removal\Handler\RemoveRootHandler;
use WerkraumMedia\ThueCat\Removal\Message\RemoveRoot;
use WerkraumMedia\ThueCat\Removal\ReferenceIndexUpdate;
use WerkraumMedia\ThueCat\Removal\Scope;
use WerkraumMedia\ThueCat\Removal\Selection;
use WerkraumMedia\ThueCat\Tests\Functional\FixedClock;

final class ReferenceIndexUpdateTest extends AbstractRemovalTestCase
{
    private const int NOW = 1795086000;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/Roots.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Relations.csv');
        // Stale index: the kept trail's reference is missing.
        $this->getConnectionPool()->getConnectionForTable('sys_refindex')->delete('sys_refindex', [
            'tablename' => 'tx_thuecat_trail',
            'recuid' => 100,
        ]);
        // @phpstan-ignore method.notFound (functional test container is the Symfony Container, which has set())
        $this->getContainer()->set(ReferenceIndexUpdate::class, new ReferenceIndexUpdate(
            $this->get(ReferenceIndex::class),
            $this->get(Registry::class),
            new FixedClock(new DateTimeImmutable('@' . self::NOW)),
        ));
    }

    #[Test]
    public function aJobUpdatingTheIndexKeepsAnOrganisationOnlyTheMissingEntryProtects(): void
    {
        $job = $this->startJob(Selection::ofKind('tx_thuecat_tourist_attraction', Scope::Folder, 10), true);
        $this->get(RemoveRootHandler::class)(new RemoveRoot($job, 'tx_thuecat_tourist_attraction', 100));

        $this->assertFalse($this->isDeleted('tx_thuecat_organisation', 101));
    }

    #[Test]
    public function aJobSkippingTheUpdateLeavesTheIndexAlone(): void
    {
        $this->startJob(Selection::ofKind('tx_thuecat_tourist_attraction', Scope::Folder, 10));

        $this->assertFalse($this->referenceExists('tx_thuecat_trail', 100, 'tx_thuecat_organisation', 101));
    }

    #[Test]
    public function anUpdateLessThan24HoursOldIsNotRepeated(): void
    {
        $this->get(Registry::class)->set('core', 'sys_refindex_lastUpdate', self::NOW - 86399);
        $this->startJob(Selection::ofKind('tx_thuecat_tourist_attraction', Scope::Folder, 10), true);

        $this->assertFalse($this->referenceExists('tx_thuecat_trail', 100, 'tx_thuecat_organisation', 101));
    }

    #[Test]
    public function anUpdate24HoursOldIsRepeated(): void
    {
        $this->get(Registry::class)->set('core', 'sys_refindex_lastUpdate', self::NOW - 86400);
        $this->startJob(Selection::ofKind('tx_thuecat_tourist_attraction', Scope::Folder, 10), true);

        $this->assertTrue($this->referenceExists('tx_thuecat_trail', 100, 'tx_thuecat_organisation', 101));
    }
}
