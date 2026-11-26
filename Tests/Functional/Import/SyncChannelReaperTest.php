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
use WerkraumMedia\ThueCat\Import\SyncChannel\SyncChannelReaper;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractFunctionalTestCase;

final class SyncChannelReaperTest extends AbstractFunctionalTestCase
{
    /**
     * The root page and the two storage folders of the site the fixtures store into.
     */
    private const array SITE_PAGES = [2200, 10, 40];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Import/SyncChannel.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Import/SyncChannelRemoval.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Import/SyncChannelRemovalUnstored.csv');
    }

    #[Test]
    public function storedRemoteIdsAreTheListedIdsTheSiteStoresInAnyRecordTableInListedOrder(): void
    {
        $this->assertSame(
            [
                'https://thuecat.org/resources/test-trail-removed',
                'https://thuecat.org/resources/test-event-removed',
                'https://thuecat.org/resources/test-place-removed-twice',
                'https://thuecat.org/resources/test-event-removed-elsewhere',
            ],
            $this->get(SyncChannelReaper::class)->storedRemoteIds([
                'https://thuecat.org/resources/test-trail-removed',
                'https://thuecat.org/resources/test-event-unknown',
                'https://thuecat.org/resources/test-event-removed',
                'https://thuecat.org/resources/test-event-removed-other-site',
                'https://thuecat.org/resources/test-place-removed-twice',
                'https://thuecat.org/resources/test-event-removed-deleted',
                'https://thuecat.org/resources/test-event-removed-translation-only',
                'https://thuecat.org/resources/test-event-removed-elsewhere',
            ], self::SITE_PAGES)
        );
    }

    #[Test]
    public function storedRemoteIdsAreNoneWithoutTheSitesPages(): void
    {
        $this->assertSame(
            [],
            $this->get(SyncChannelReaper::class)->storedRemoteIds(['https://thuecat.org/resources/test-event-removed'], [])
        );
    }
}
