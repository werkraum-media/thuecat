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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WerkraumMedia\ThueCat\Import\FileFolderAccess;
use WerkraumMedia\ThueCat\Import\FileFolderAccessException;
use WerkraumMedia\ThueCat\Typo3Wrapper\TranslationService;

class FileFolderAccessTest extends AbstractImportTestCase
{
    #[Test]
    public function throwsWhenNoFolderConfigured(): void
    {
        $this->expectException(FileFolderAccessException::class);
        $this->expectExceptionCode(1748520001);

        $this->createSubject()->assertWritable('');
    }

    #[Test]
    public function throwsWhenFolderCannotBeResolved(): void
    {
        $this->expectException(FileFolderAccessException::class);
        $this->expectExceptionCode(1748520002);

        $this->createSubject()->assertWritable('999:/does-not-exist/');
    }

    #[Test]
    public function returnsTrueForWritableFolder(): void
    {
        $basePath = $this->instancePath . '/fileadmin-thuecat';
        GeneralUtility::mkdir_deep($basePath);
        $storageUid = $this->get(StorageRepository::class)->createLocalStorage(
            'ThueCat test storage',
            $basePath,
            'absolute'
        );

        self::assertTrue(
            $this->createSubject()->assertWritable($storageUid . ':/')
        );
    }

    /**
     * The real service, not the stub AbstractImportTestCase swaps into the
     * container — this test exercises the actual write probe.
     */
    private function createSubject(): FileFolderAccess
    {
        return new FileFolderAccess(
            $this->get(ResourceFactory::class),
            $this->get(TranslationService::class),
        );
    }
}
