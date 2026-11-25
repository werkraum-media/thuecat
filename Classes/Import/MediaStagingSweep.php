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

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Resource\Folder;
use WerkraumMedia\ThueCat\Import\Job\ImportJobs;

/**
 * Removes the staging folders a killed consumer leaves behind. It only deletes: a leftover file was never verified
 * as complete, so it is never promoted.
 */
#[Autoconfigure(public: true)]
final readonly class MediaStagingSweep
{
    public function __construct(
        private ConnectionPool $connectionPool,
        private FileFolderAccess $fileFolderAccess,
        private MediaFileStaging $mediaFileStaging,
        private ImportJobs $importJobs,
    ) {
    }

    public function sweepJob(string $fileFolder, int $job): void
    {
        foreach ($this->stagingFoldersIn($fileFolder) as $staging) {
            if ($this->mediaFileStaging->jobOf($staging) === $job) {
                $this->mediaFileStaging->discard($staging);
            }
        }
    }

    /**
     * A folder of thuecat 7 names no job, so any open job may still be using it.
     */
    public function sweep(): void
    {
        $openJobs = $this->importJobs->openJobUids();
        foreach ($this->fileFolders() as $fileFolder) {
            foreach ($this->stagingFoldersIn($fileFolder) as $staging) {
                $job = $this->mediaFileStaging->jobOf($staging);
                $inUse = $job === null ? $openJobs !== [] : in_array($job, $openJobs, true);
                if (!$inUse) {
                    $this->mediaFileStaging->discard($staging);
                }
            }
        }
    }

    /**
     * A job of a configuration whose folder cannot be resolved fails at its start, so nothing was staged there.
     *
     * @return list<Folder>
     */
    private function stagingFoldersIn(string $fileFolder): array
    {
        try {
            return $this->mediaFileStaging->stagingFoldersIn($this->fileFolderAccess->resolveFolder($fileFolder));
        } catch (FileFolderAccessException) {
            return [];
        }
    }

    /**
     * Hidden configurations included: a job of one may have been running when it was hidden.
     *
     * @return list<string>
     */
    private function fileFolders(): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_thuecat_import_configuration');
        $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());

        return array_values(array_filter(
            $queryBuilder
                ->selectLiteral('DISTINCT file_folder')
                ->from('tx_thuecat_import_configuration')
                ->executeQuery()
                ->fetchFirstColumn(),
            is_string(...)
        ));
    }
}
