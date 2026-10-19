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

namespace WerkraumMedia\ThueCat\Tests\Unit\Import\UrlProvider;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfiguration;
use WerkraumMedia\ThueCat\Import\SyncChannel\SyncChannel;
use WerkraumMedia\ThueCat\Import\SyncChannel\SyncChannelLists;
use WerkraumMedia\ThueCat\Import\SyncChannel\SyncWindow;
use WerkraumMedia\ThueCat\Import\UrlProvider\SyncChannelUrlProvider;

final class SyncChannelUrlProviderTest extends TestCase
{
    #[Test]
    public function canProvideForSyncChannelOnly(): void
    {
        $subject = new SyncChannelUrlProvider(
            self::createStub(SyncChannel::class),
            self::createStub(SyncWindow::class),
        );

        $syncChannel = new ImportConfiguration();
        $syncChannel->_setProperty('type', 'syncChannel');
        $syncScope = new ImportConfiguration();
        $syncScope->_setProperty('type', 'syncScope');

        self::assertTrue($subject->canProvideForConfiguration($syncChannel));
        self::assertFalse($subject->canProvideForConfiguration($syncScope));
    }

    #[Test]
    public function emitsRecordUrlsOnTheSyncHostForTheWindowOfTheConfiguration(): void
    {
        $since = new DateTimeImmutable('2026-10-14T03:00:00+02:00');
        $syncWindow = $this->createMock(SyncWindow::class);
        $syncWindow->expects(self::once())->method('since')->with(34, 10)->willReturn($since);
        $syncChannel = $this->createMock(SyncChannel::class);
        $syncChannel->expects(self::once())
            ->method('read')
            ->with('https://widgets.thuecat.org', 'test-channel', $since)
            ->willReturn(new SyncChannelLists(['test-event-1', 'test-event-2'], []))
        ;

        $configuration = self::createConfiguredStub(ImportConfiguration::class, [
            'getUid' => 34,
            'getType' => 'syncChannel',
            'getStoragePid' => 10,
            'getSyncChannelId' => 'test-channel',
            'getSyncApiDomain' => 'https://widgets.thuecat.org',
        ]);

        $subject = (new SyncChannelUrlProvider($syncChannel, $syncWindow))->createWithConfiguration($configuration);

        self::assertSame(
            [
                'https://widgets.thuecat.org/api/sync/test-channel/jsonld/test-event-1',
                'https://widgets.thuecat.org/api/sync/test-channel/jsonld/test-event-2',
            ],
            // The importer passes the stored apiDomain, which a type switch may have left behind.
            $subject->getUrls('https://cdb.thuecat.org')
        );
    }
}
