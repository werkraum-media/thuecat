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
