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

use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WerkraumMedia\ThueCat\Import\FileFolderAccess;

/**
 * Test double for FileFolderAccess. Skips the real write probe so import tests
 * don't have to set up writable FAL storage, returning the fallback storage's
 * root folder as the target. Registered into the test container by
 * AbstractImportTestCase.
 */
final class FileFolderAccessStub extends FileFolderAccess
{
    public function __construct()
    {
        // No dependencies: the real probe is skipped entirely.
    }

    public function resolveFolder(string $folderIdentifier): Folder
    {
        $storage = GeneralUtility::makeInstance(StorageRepository::class)->getStorageObject(0);
        return $storage->getRootLevelFolder();
    }

    public function assertWritable(string $folderIdentifier): bool
    {
        return true;
    }
}
