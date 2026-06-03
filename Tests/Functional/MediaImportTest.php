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
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WerkraumMedia\ThueCat\Domain\Import\Importer;
use WerkraumMedia\ThueCat\Domain\Repository\Backend\ImportConfigurationRepository;

/**
 * Exercises the real media import — download → sys_file → sys_file_reference.
 * A real local storage is set up (DataHandler refuses references into the fallback legacy
 * storage) and the image bytes are faked per download.
 */
class MediaImportTest extends AbstractImportTestCase
{
    protected bool $stubMediaDownloader = false;

    protected bool $stubFileFolderAccess = false;

    protected function setUp(): void
    {
        parent::setUp();

        // The fixture configures fileFolder "1:/thuecat/"; create that storage
        // and folder so the import resolves it and DataHandler accepts
        // references into it
        $basePath = $this->instancePath . '/fileadmin-thuecat';
        GeneralUtility::mkdir_deep($basePath);
        $storageUid = $this->get(StorageRepository::class)->createLocalStorage(
            'ThueCat test storage',
            $basePath,
            'absolute'
        );
        $this->get(StorageRepository::class)->getStorageObject($storageUid)
            ->createFolder('thuecat')
        ;
    }

    #[Test]
    public function importsTouristAttractionWithMedia(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsTouristAttractionWithMedia.php');
        $this->expectFetch('attraction-with-media.json');
        $this->expectFetch('018132452787-ngbe.json');
        $this->expectFetch('image-with-foreign-author.json');
        $this->expectFetch('author-with-names.json');
        $this->expectFetch('image-with-author-string.json');
        $this->expectFetch('image-with-license-author.json');
        $this->expectFetch('image-with-author-and-license-author.json');
        // The four image nodes all reference the same adaptive-media URL; each
        // produces a distinct sys_file (names differ), so four downloads.
        $downloadUrl = 'https://cms.thuecat.org/o/adaptive-media/image/5099196/Preview-1280x0/image';
        for ($i = 0; $i < 4; $i++) {
            $this->expectFetchForUrl($downloadUrl, 'cms.thuecat.org/image.jpg');
        }

        $this->importConfiguration(1);

        $this->assertPHPDataSet(__DIR__ . '/Assertions/Import/ImportsTouristAttractionWithMedia.php');
    }

    private function importConfiguration(int $uid): void
    {
        $this->workaroundExtbaseConfiguration();
        $configuration = $this->get(ImportConfigurationRepository::class)->findOneByUid($uid);
        self::assertNotNull($configuration, 'Fixture configuration uid=' . $uid . ' not found');
        $this->get(Importer::class)->importConfiguration($configuration);
    }
}
