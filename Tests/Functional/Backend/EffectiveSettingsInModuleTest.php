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

namespace WerkraumMedia\ThueCat\Tests\Functional\Backend;

use PHPUnit\Framework\Attributes\Test;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLog;
use WerkraumMedia\ThueCat\Domain\Repository\Backend\ImportLogRepository;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractImportTestCase;

// The summary column of the imports module shows what drove each run.
class EffectiveSettingsInModuleTest extends AbstractImportTestCase
{
    #[Test]
    public function theRunsSettingsAreReadableFromItsLog(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsFreshOrganization.php');
        $this->expectFetch('018132452787-ngbe.json');

        $this->importConfiguration(1);

        $settings = $this->latestLog()->getEffectiveSettings();

        self::assertSame(11, $settings['storagePid'] ?? null);
        self::assertArrayNotHasKey('importTarget', $settings);
        self::assertSame('unset', $settings['import.thuecat.category.parent'] ?? null);
        self::assertSame('unset', $settings['import.trails.keywords.parent'] ?? null);
        self::assertArrayNotHasKey('apiKey', $settings);
    }

    #[Test]
    public function logsWithoutASummaryReportNoSettings(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/LogWithoutSummary.php');

        self::assertSame([], $this->latestLog()->getEffectiveSettings());
    }

    private function latestLog(): ImportLog
    {
        $log = null;
        foreach ($this->get(ImportLogRepository::class)->findAll() as $candidate) {
            $log = $candidate;
        }
        self::assertInstanceOf(ImportLog::class, $log);

        return $log;
    }
}
