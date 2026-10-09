<?php

declare(strict_types=1);

/*
 * Copyright (C) 2026 werkraum-media
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301, USA.
 */

namespace WerkraumMedia\ThueCat\Tests\Functional\Import;

use PHPUnit\Framework\Attributes\Test;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractImportTestCase;

/**
 * The roots are trails that import from their own document alone, so every staged
 * fetch is one the containsPlace type itself must make.
 */
class ContainsPlaceImportTest extends AbstractImportTestCase
{
    protected string $fixtureGuzzleBase = __DIR__ . '/../Fixtures/Import/Guzzle';

    #[Test]
    public function importsEveryPlaceTheConfiguredPlaceContains(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsContainsPlace.php');
        $this->expectFetchForUrl(
            'https://thuecat.org/resources/043064193523-contains',
            'thuecat.org/resources/contains-two-trails.json'
        );
        $this->expectFetch('e_52469786-oatour.json');
        $this->expectFetchForUrl(
            'https://thuecat.org/resources/e_106954656-oatour',
            'thuecat.org/resources/trail-without-relations.json'
        );

        $this->importConfiguration(1);

        self::assertGreaterThan(
            0,
            $this->fetchUidByRemoteId('tx_thuecat_trail', 'https://thuecat.org/resources/e_52469786-oatour')
        );
        self::assertGreaterThan(
            0,
            $this->fetchUidByRemoteId('tx_thuecat_trail', 'https://thuecat.org/resources/e_106954656-oatour')
        );
    }
}
