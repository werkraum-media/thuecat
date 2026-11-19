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
use TYPO3\CMS\Core\Database\ReferenceIndex;

final class ImportLogReferenceIndexTest extends AbstractFunctionalTestCase
{
    #[Test]
    public function aFullUpdateIndexesNoImportLog(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ReferenceIndex/ImportLogs.csv');

        $this->get(ReferenceIndex::class)->updateIndex(false);

        $connection = $this->getConnectionPool()->getConnectionForTable('sys_refindex');
        $this->assertSame(0, $connection->count('*', 'sys_refindex', ['tablename' => 'tx_thuecat_import_log']));
        $this->assertSame(0, $connection->count('*', 'sys_refindex', ['tablename' => 'tx_thuecat_import_log_entry']));
        $this->assertSame(1, $connection->count('*', 'sys_refindex', ['tablename' => 'tx_thuecat_trail', 'ref_table' => 'tx_thuecat_organisation']));
    }
}
