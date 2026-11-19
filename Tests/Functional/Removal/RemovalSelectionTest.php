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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use WerkraumMedia\ThueCat\Removal\RemovalSelection;
use WerkraumMedia\ThueCat\Removal\Scope;
use WerkraumMedia\ThueCat\Removal\Selection;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractFunctionalTestCase;

final class RemovalSelectionTest extends AbstractFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/Roots.csv');
    }

    /**
     * @return iterable<string, array{Selection, list<array{string, int}>}>
     */
    public static function selections(): iterable
    {
        yield 'attractions of a folder, translation and deleted row left out, missing remote ID included' => [
            Selection::ofKind('tx_thuecat_tourist_attraction', Scope::Folder, 10),
            [['tx_thuecat_tourist_attraction', 1], ['tx_thuecat_tourist_attraction', 2]],
        ];
        yield 'attractions of a site, other site left out' => [
            Selection::ofKind('tx_thuecat_tourist_attraction', Scope::Site, 1),
            [
                ['tx_thuecat_tourist_attraction', 1],
                ['tx_thuecat_tourist_attraction', 2],
                ['tx_thuecat_tourist_attraction', 4],
            ],
        ];
        yield 'attractions of the instance' => [
            Selection::ofKind('tx_thuecat_tourist_attraction', Scope::Instance),
            [
                ['tx_thuecat_tourist_attraction', 1],
                ['tx_thuecat_tourist_attraction', 2],
                ['tx_thuecat_tourist_attraction', 4],
                ['tx_thuecat_tourist_attraction', 5],
            ],
        ];
        yield 'trails of a folder' => [
            Selection::ofKind('tx_thuecat_trail', Scope::Folder, 10),
            [['tx_thuecat_trail', 2]],
        ];
        yield 'events of a folder without events' => [
            Selection::ofKind('tx_events_domain_model_event', Scope::Folder, 4010),
            [],
        ];
        yield 'origin 32 in a site, across root tables, other site and translations left out' => [
            Selection::ofOrigin(1, 32),
            [['tx_thuecat_tourist_attraction', 1], ['tx_thuecat_place', 1], ['tx_events_domain_model_event', 1]],
        ];
        yield 'origin 7 in a site' => [
            Selection::ofOrigin(1, 7),
            [['tx_thuecat_place', 2], ['tx_events_domain_model_event', 2]],
        ];
    }

    /**
     * @param list<array{string, int}> $expected
     */
    #[DataProvider('selections')]
    #[Test]
    public function walksTheRootsOfTheSelectionOneAtATime(Selection $selection, array $expected): void
    {
        $subject = $this->get(RemovalSelection::class);

        $walked = [];
        $root = $subject->first($selection);
        while ($root !== null) {
            $walked[] = [$root->table, $root->uid];
            $root = $subject->next($selection, $root);
        }

        $this->assertSame($expected, $walked);
    }

    /**
     * @param list<array{string, int}> $expected
     */
    #[DataProvider('selections')]
    #[Test]
    public function countsTheRootsOfTheSelection(Selection $selection, array $expected): void
    {
        $this->assertSame(count($expected), $this->get(RemovalSelection::class)->count($selection));
    }
}
