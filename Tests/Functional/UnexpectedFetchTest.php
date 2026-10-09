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

namespace WerkraumMedia\ThueCat\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;

/**
 * The resolver catches a failed reference fetch and only logs it, which is right for
 * production. The faker's exception is swallowed the same way, so the test harness
 * has to report it on its own.
 */
class UnexpectedFetchTest extends AbstractImportTestCase
{
    #[Test]
    public function reportsAReferenceFetchNoTestStaged(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsTrailWithRelations.php');
        $this->expectFetchForUrl(
            'https://thuecat.org/resources/e_106954656-oatour',
            'thuecat.org/resources/trail-with-content-responsible.json'
        );

        $this->importConfiguration(1);

        self::assertStringContainsString(
            'https://thuecat.org/resources/018132452787-ngbe',
            implode("\n", GuzzleClientFaker::tearDown())
        );
    }
}
