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

use DateTimeImmutable;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\DateTimeAspect;

/**
 * Runs the syncChannel configuration of Fixtures/Import/SyncChannel.csv, which
 * imports channel `test-channel` into folder 10 of site root 2200, which
 * anchors keywords at category 200. Its last
 * successful run fixes the window, so the list URLs are known in advance.
 */
abstract class AbstractSyncChannelImportTestCase extends AbstractImportTestCase
{
    public const LIST_CHANGED = 'https://widgets.thuecat.org/api/sync/test-channel/list-changed?since=2026-10-14T03%3A00%3A00%2B02%3A00';

    public const LIST_EXPIRED = 'https://widgets.thuecat.org/api/sync/test-channel/list-expired?since=2026-10-14T03%3A00%3A00%2B02%3A00';

    public const RECORD = 'https://widgets.thuecat.org/api/sync/test-channel/jsonld/';

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/Import/SyncChannel.csv');
        // Dates are generated from today onwards.
        $this->get(Context::class)->setAspect(
            'date',
            new DateTimeAspect(new DateTimeImmutable('2026-10-19T12:00:00+02:00'))
        );
    }

    protected function expectChannelServingOneEvent(): void
    {
        $this->expectChannelServing('list-changed.json', 'test-event-1');
    }

    /**
     * @param string $changedList File name of the change list, which must list exactly $eventIds.
     */
    protected function expectChannelServing(string $changedList, string ...$eventIds): void
    {
        $this->expectFetchForUrl(self::LIST_CHANGED, 'widgets.thuecat.org/api/sync/test-channel/' . $changedList);
        $this->expectFetchForUrl(self::LIST_EXPIRED, 'widgets.thuecat.org/api/sync/test-channel/list-expired.json');
        foreach ($eventIds as $eventId) {
            $this->expectFetchForUrl(self::RECORD . $eventId, 'widgets.thuecat.org/api/sync/test-channel/jsonld/' . $eventId . '.json');
        }
    }
}
