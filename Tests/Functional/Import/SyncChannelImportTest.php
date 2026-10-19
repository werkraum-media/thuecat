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

namespace WerkraumMedia\ThueCat\Tests\Functional\Import;

use PHPUnit\Framework\Attributes\Test;
use WerkraumMedia\ThueCat\Import\Importer\FetchData\InvalidResponseException;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractImportTestCase;

final class SyncChannelImportTest extends AbstractImportTestCase
{
    // One day before the fixture's last successful run.
    private const LIST_CHANGED = 'https://widgets.thuecat.org/api/sync/test-channel/list-changed?since=2026-10-14T03%3A00%3A00%2B02%3A00';

    private const LIST_EXPIRED = 'https://widgets.thuecat.org/api/sync/test-channel/list-expired?since=2026-10-14T03%3A00%3A00%2B02%3A00';

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Import/SyncChannel.csv');
    }

    #[Test]
    public function unreadableExpiryListFailsTheRunBeforeAnyRecordIsFetched(): void
    {
        $this->expectFetchForUrl(self::LIST_CHANGED, 'widgets.thuecat.org/api/sync/test-channel/list-changed.json');
        $this->expectFailureForUrl(self::LIST_EXPIRED, 403);

        try {
            $this->importConfiguration(1);
            self::fail('The run went on without the expiry list.');
        } catch (InvalidResponseException) {
        }

        self::assertCount(1, $this->getLogEntriesOfType('runFailed'));
    }
}
