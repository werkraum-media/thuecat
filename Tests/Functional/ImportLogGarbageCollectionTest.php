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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Scheduler\Task\TableGarbageCollectionTask;

class ImportLogGarbageCollectionTest extends AbstractImportConfigurationTestCase
{
    protected array $coreExtensionsToLoad = [
        'core',
        'backend',
        'extbase',
        'frontend',
        'install',
        'filelist',
        'filemetadata',
        'scheduler',
    ];

    /**
     * @return iterable<string, array{string}>
     */
    public static function importLogTables(): iterable
    {
        yield 'log' => ['tx_thuecat_import_log'];
        yield 'log entry' => ['tx_thuecat_import_log_entry'];
    }

    #[Test]
    #[DataProvider('importLogTables')]
    public function garbageCollectionTaskOffersTable(string $tableName): void
    {
        $taskType = $this->get(TcaSchemaFactory::class)->get('tx_scheduler_task.' . TableGarbageCollectionTask::class);

        self::assertSame(
            ['dateField' => 'crdate', 'expirePeriod' => 180],
            // @phpstan-ignore offsetAccess.nonOffsetAccessible, offsetAccess.nonOffsetAccessible
            $taskType->getRawConfiguration()['taskOptions']['tables'][$tableName] ?? null
        );
    }
}
