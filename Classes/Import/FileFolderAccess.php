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

namespace WerkraumMedia\ThueCat\Import;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Throwable;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use WerkraumMedia\ThueCat\Extension;
use WerkraumMedia\ThueCat\Typo3Wrapper\TranslationService;

/**
 * Tests accessibility for the user currently running the import
 * given target file folder will be checked for existence and throw if not available
 * a test file will be written to the target and deleted again. If one of the operations
 * fails, again an exception is thrown. If everything succeeds, the folder is signed off
 * and handed back to the importer.
 * There is no option to get the folder without the access check, that is on purpose.
 */
#[Autoconfigure(public: true)]
class FileFolderAccess
{
    private const PROBE_PREFIX = '.thuecat-write-probe-';

    public function __construct(
        private readonly ResourceFactory $resourceFactory,
        private readonly TranslationService $translation,
    ) {
    }

    public function resolveFolder(string $folderIdentifier): Folder
    {
        if ($folderIdentifier === '') {
            throw new FileFolderAccessException(
                $this->translation->translate('import.fileFolder.missing', Extension::EXTENSION_NAME),
                1748520001
            );
        }

        $folder = $this->resolveExistingFolder($folderIdentifier);
        $this->assertProbeSucceeds($folder, $folderIdentifier);

        return $folder;
    }

    public function assertWritable(string $folderIdentifier): bool
    {
        $this->resolveFolder($folderIdentifier);
        return true;
    }

    private function assertProbeSucceeds(Folder $folder, string $folderIdentifier): void
    {
        $probeName = self::PROBE_PREFIX . bin2hex(random_bytes(8));
        try {
            $probe = $folder->createFile($probeName);
            $probe->setContents('thuecat write probe');
            $folder->getStorage()->deleteFile($probe);
        } catch (Throwable $e) {
            throw new FileFolderAccessException(
                $this->translation->translate(
                    'import.fileFolder.notWritable',
                    Extension::EXTENSION_NAME,
                    [$folderIdentifier, $e->getMessage()]
                ),
                1748520003,
                $e
            );
        }
    }

    private function resolveExistingFolder(string $folderIdentifier): Folder
    {
        try {
            return $this->resourceFactory->getFolderObjectFromCombinedIdentifier($folderIdentifier);
        } catch (Throwable $e) {
            throw new FileFolderAccessException(
                $this->translation->translate(
                    'import.fileFolder.unresolvable',
                    Extension::EXTENSION_NAME,
                    [$folderIdentifier, $e->getMessage()]
                ),
                1748520002,
                $e
            );
        }
    }
}
