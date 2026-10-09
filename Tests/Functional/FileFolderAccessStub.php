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
