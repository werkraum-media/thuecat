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
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractSyncChannelImportTestCase;

final class SyncChannelMediaImportTest extends AbstractSyncChannelImportTestCase
{
    protected bool $stubMediaDownloader = false;

    protected bool $stubFileFolderAccess = false;

    protected function setUp(): void
    {
        parent::setUp();

        $basePath = $this->instancePath . '/fileadmin-thuecat';
        GeneralUtility::mkdir_deep($basePath);
        $storageUid = $this->get(StorageRepository::class)->createLocalStorage(
            'ThueCat test storage',
            $basePath,
            'absolute'
        );
        $this->get(StorageRepository::class)
            ->getStorageObject($storageUid)
            ->createFolder('thuecat')
        ;
    }

    // The fileadmin path survives the database reset; a leftover file would skip the download.
    protected function tearDown(): void
    {
        GeneralUtility::rmdir($this->instancePath . '/fileadmin-thuecat', true);

        parent::tearDown();
    }

    #[Test]
    public function privateImageArrivingInlineIsDownloadedAndRelated(): void
    {
        $this->expectChannelServingOneEvent();
        $this->expectFetchForUrl(
            'https://cdb.thuecat.org/assets/ttg/m-tdm/original/test-event-1/test-image-1.jpg',
            'cms.thuecat.org/image.jpg'
        );

        $this->importConfiguration(1);

        $this->assertCSVDataSet(__DIR__ . '/Assertions/SyncChannelRelatesPrivateImage.csv');
    }

    #[Test]
    public function referencedMediaServedByTheChannelComesFromTheChannel(): void
    {
        $this->expectChannelServing('list-changed-media-in-channel.json', 'test-event-media-1');
        $this->expectFetchForUrl(self::RECORD . 'test-media-1', 'widgets.thuecat.org/api/sync/test-channel/jsonld/test-media-1.json');
        $this->expectFetchForUrl(
            'https://cdb.thuecat.org/assets/ttg/m-tdm/original/test-media-1/test-image-private.jpg',
            'cms.thuecat.org/image.jpg'
        );

        $this->importConfiguration(1);

        $this->assertCSVDataSet(__DIR__ . '/Assertions/SyncChannelRelatesPrivateImage.csv');
    }
}
