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
