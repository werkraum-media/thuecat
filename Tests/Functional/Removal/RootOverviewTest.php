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

use PHPUnit\Framework\Attributes\Test;
use WerkraumMedia\ThueCat\Removal\OriginRoots;
use WerkraumMedia\ThueCat\Removal\PageRoots;
use WerkraumMedia\ThueCat\Removal\RootOverview;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractBackendTestCase;

final class RootOverviewTest extends AbstractBackendTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/Roots.csv');
    }

    #[Test]
    public function listsThePagesHoldingRootsPerSiteWithCountsPerKind(): void
    {
        $this->assertEquals([
            'example' => [
                new PageRoots(10, 'Folder A', [
                    'tx_thuecat_tourist_attraction' => 2,
                    'tx_thuecat_place' => 1,
                    'tx_thuecat_trail' => 1,
                    'tx_events_domain_model_event' => 1,
                ]),
                new PageRoots(11, 'Folder B', [
                    'tx_thuecat_tourist_attraction' => 1,
                    'tx_thuecat_place' => 1,
                    'tx_thuecat_trail' => 0,
                    'tx_events_domain_model_event' => 1,
                ]),
            ],
            'first_site' => [
                new PageRoots(4010, 'Folder C', [
                    'tx_thuecat_tourist_attraction' => 1,
                    'tx_thuecat_place' => 0,
                    'tx_thuecat_trail' => 1,
                    'tx_events_domain_model_event' => 0,
                ]),
            ],
            '' => [
                new PageRoots(9010, 'Folder D', [
                    'tx_thuecat_tourist_attraction' => 0,
                    'tx_thuecat_place' => 0,
                    'tx_thuecat_trail' => 0,
                    'tx_events_domain_model_event' => 1,
                ]),
            ],
        ], $this->get(RootOverview::class)->pagesBySite($this->setUpBackendUser(1)));
    }

    #[Test]
    public function listsOnlyPagesTheUserMayRead(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Editor.csv');

        $this->assertEquals([
            'example' => [
                new PageRoots(10, 'Folder A', [
                    'tx_thuecat_tourist_attraction' => 2,
                    'tx_thuecat_place' => 1,
                    'tx_thuecat_trail' => 1,
                    'tx_events_domain_model_event' => 1,
                ]),
            ],
        ], $this->get(RootOverview::class)->pagesBySite($this->setUpBackendUser(2)));
    }

    #[Test]
    public function listsTheImportConfigurationsTheRootsOfASiteOriginateFromByTheirStoredTitles(): void
    {
        $this->assertEquals([new OriginRoots(7, 'Weimar', 2), new OriginRoots(32, 'Erfurt', 3)], $this->get(RootOverview::class)->originsOfSite(1));
    }

    #[Test]
    public function listsOnlyTheOriginsOfRootsWithinTheSite(): void
    {
        $this->assertEquals([new OriginRoots(32, 'Erfurt', 2)], $this->get(RootOverview::class)->originsOfSite(4000));
    }

    #[Test]
    public function listsNoOriginForRootsWithoutOne(): void
    {
        $this->assertSame([], $this->get(RootOverview::class)->originsOfSite(9000));
    }
}
