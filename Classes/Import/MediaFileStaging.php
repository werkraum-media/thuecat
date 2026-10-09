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

namespace WerkraumMedia\ThueCat\Import;

use TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Staging Handling for FAL imports. Download puts them into a fresh folder,
 * makes sure data are properly imported, then moves files as needed into final
 * position. Staging folder will be discarded after import runs, so no orphaned files
 * from failing import records remain.
 */
class MediaFileStaging
{
    protected const STAGING_PREFIX = '_thuecat_import_';

    /**
     * Create a fresh, uniquely named staging subfolder under $target.
     */
    public function createForRun(Folder $target): Folder
    {
        return $target->createFolder(StringUtility::getUniqueId(self::STAGING_PREFIX));
    }

    /**
     * Move every staged file into $target. A name already present in the
     * target wins (same dms id = same file): the staged duplicate is dropped
     * rather than moved. Called only after a clean run.
     */
    public function promote(Folder $staging, Folder $target): void
    {
        $storage = $staging->getStorage();
        foreach ($staging->getFiles() as $file) {
            if ($target->hasFile($file->getName())) {
                $storage->deleteFile($file);
                continue;
            }
            $storage->moveFile($file, $target, $file->getName(), DuplicationBehavior::CANCEL);
        }
    }

    public function discard(Folder $staging): void
    {
        $staging->getStorage()->deleteFolder($staging, true);
    }
}
