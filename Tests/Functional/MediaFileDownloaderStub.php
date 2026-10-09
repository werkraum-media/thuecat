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

use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use WerkraumMedia\ThueCat\Import\MediaFileDownloader;

/**
 * Test double for MediaFileDownloader. Tests that focus elsewhere and don't care about
 * Media Handling use this. Tests that assert real media relations build the
 * genuine downloader instead and fake the image fetch deliberately.
 */
final class MediaFileDownloaderStub extends MediaFileDownloader
{
    public function __construct()
    {
        // No dependencies: the real download is skipped entirely.
    }

    /**
     * @param-out null $failureDetail
     * @param-out null $failureStatus
     */
    public function download(
        Folder $target,
        Folder $staging,
        string $downloadUrl,
        string $apiKey = '',
        string $apiDomain = '',
        ?string &$failureDetail = null,
        ?int &$failureStatus = null,
    ): ?File {
        $failureDetail = null;
        $failureStatus = null;
        return null;
    }
}
