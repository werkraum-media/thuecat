<?php

declare(strict_types=1);

/*
 * Copyright (C) 2026 werkraum-media
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301, USA.
 */

namespace WerkraumMedia\ThueCat\Tests\Functional;

use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use WerkraumMedia\ThueCat\Domain\Import\MediaFileDownloader;

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

    public function download(
        Folder $target,
        Folder $staging,
        string $downloadUrl,
        string $dmsId,
        string $originalName,
    ): ?File {
        return null;
    }
}
