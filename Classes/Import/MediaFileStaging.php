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
     * Move every staged file into $target. A name already present in the
     * target wins (same dms id = same file): the staged duplicate is dropped
     * rather than moved.
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

    /**
     * A folder a killed attempt left behind was never verified as complete: it is replaced, never reused.
     */
    public function createForItem(Folder $target, int $job, int $item): Folder
    {
        $name = self::STAGING_PREFIX . 'job' . $job . '_item' . $item;
        if ($target->hasFolder($name)) {
            $this->discard($target->getSubfolder($name));
        }

        return $target->createFolder($name);
    }

    /**
     * @return list<Folder>
     */
    public function stagingFoldersIn(Folder $target): array
    {
        return array_values(array_filter(
            $target->getSubfolders(),
            static fn (Folder $folder): bool => str_starts_with($folder->getName(), self::STAGING_PREFIX)
        ));
    }

    /**
     * Null for a folder thuecat 7 staged a whole run in: its name carries no job.
     */
    public function jobOf(Folder $staging): ?int
    {
        return preg_match('/^' . self::STAGING_PREFIX . 'job(\d+)_item\d+$/', $staging->getName(), $matches) === 1
            ? (int)$matches[1]
            : null;
    }

    public function discard(Folder $staging): void
    {
        $staging->getStorage()->deleteFolder($staging, true);
    }
}
