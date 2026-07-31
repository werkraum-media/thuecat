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
use WerkraumMedia\ThueCat\Domain\Repository\Backend\ImportConfigurationRepository;
use WerkraumMedia\ThueCat\Import\Importer;

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
        $this->get(StorageRepository::class)
            ->getStorageObject($storageUid)
            ->createFolder('thuecat')
        ;
    }

    /**
     * The DB resets between test methods, the fileadmin path does not. A leftover
     * file makes download() short-circuit on hasFile(), so a later test staging a
     * fetch would silently never perform it.
     */
    protected function tearDown(): void
    {
        GeneralUtility::rmdir($this->instancePath . '/fileadmin-thuecat', true);

        parent::tearDown();
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

    #[Test]
    public function relatesPhotoToMainImageAndImageToMediaFiles(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsTouristAttractionWithPhotoAndImage.php');
        $this->expectFetch('attraction-with-photo-and-image.json');
        $this->expectFetch('image-with-author-string.json');
        $this->expectFetch('image-with-license-author.json');
        $downloadUrl = 'https://cms.thuecat.org/o/adaptive-media/image/5099196/Preview-1280x0/image';
        $this->expectFetchForUrl($downloadUrl, 'cms.thuecat.org/image.jpg');
        $this->expectFetchForUrl($downloadUrl, 'cms.thuecat.org/image.jpg');

        $this->importConfiguration(1);

        self::assertSame(
            ['main_image', 'media_files'],
            $this->fetchReferenceFields(),
            'schema:photo belongs in main_image, schema:image in media_files.'
        );
    }

    /**
     * @return list<string>
     */
    private function fetchReferenceFields(): array
    {
        $rows = $this->getConnectionPool()
            ->getConnectionForTable('sys_file_reference')
            ->select(['fieldname'], 'sys_file_reference', [], [], ['uid' => 'ASC'])
            ->fetchAllAssociative()
        ;

        $fields = [];
        foreach ($rows as $row) {
            if (is_string($row['fieldname'])) {
                $fields[] = $row['fieldname'];
            }
        }
        sort($fields);
        return $fields;
    }

    #[Test]
    public function relatesInlineImageOnAPointOfInterest(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsTouristAttractionWithInlineImage.php');
        $this->expectFetch('attraction-with-inline-image.json');
        $this->expectFetchForUrl(
            'https://cdb.thuecat.org/assets/ttg/m-tdm/original/poi-inline/1a2b3c4d-0000-4000-8000-000000000001.jpg',
            'cms.thuecat.org/image.jpg'
        );

        $this->importConfiguration(1);

        // Inline is a capability of the media import, not an event feature: a
        // POI carrying one lands it in the field that POI declares.
        self::assertSame(
            ['media_files'],
            $this->fetchReferenceFields()
        );
    }

    #[Test]
    public function relatesBothShapesMixedInsideOnePoiCollectionField(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsTouristAttractionWithMixedGallery.php');
        $this->expectFetch('attraction-with-mixed-gallery.json');
        $this->expectFetch('image-with-author-string.json');
        $this->expectFetchForUrl(
            'https://cms.thuecat.org/o/adaptive-media/image/5099196/Preview-1280x0/image',
            'cms.thuecat.org/image.jpg'
        );
        $this->expectFetchForUrl(
            'https://cdb.thuecat.org/assets/ttg/m-tdm/original/poi-inline/1a2b3c4d-0000-4000-8000-000000000001.jpg',
            'cms.thuecat.org/image.jpg'
        );

        $this->importConfiguration(1);

        // A collection field makes no promise that its entries share a shape.
        $names = $this->fetchRelatedFileNamesInOrder();
        sort($names);
        self::assertSame(
            [
                '1a2b3c4d-0000-4000-8000-000000000001.jpg',
                'image-with-author-string_Bild-mit-author.jpg',
            ],
            $names
        );
    }

    /**
     * @return list<string>
     */
    private function fetchRelatedFileNamesInOrder(): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('sys_file_reference');
        $queryBuilder->getRestrictions()->removeAll();
        $rows = $queryBuilder
            ->select('f.name')
            ->from('sys_file_reference', 'r')
            ->join('r', 'sys_file', 'f', $queryBuilder->expr()->eq('f.uid', $queryBuilder->quoteIdentifier('r.uid_local')))
            ->orderBy('r.sorting_foreign', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative()
        ;

        $names = [];
        foreach ($rows as $row) {
            if (is_string($row['name'])) {
                $names[] = $row['name'];
            }
        }
        return $names;
    }

    #[Test]
    public function reimportKeepsMediaReferencesIdempotent(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ReimportTouristAttractionWithMedia.php');
        $this->seedExistingMediaFiles();

        // Files are already in the target folder, so only the JSON graph is
        // fetched; no image download.
        $this->expectFetch('attraction-with-media.json');
        $this->expectFetch('018132452787-ngbe.json');
        $this->expectFetch('image-with-foreign-author.json');
        $this->expectFetch('author-with-names.json');
        $this->expectFetch('image-with-author-string.json');
        $this->expectFetch('image-with-license-author.json');
        $this->expectFetch('image-with-author-and-license-author.json');

        $this->importConfiguration(1);

        $this->assertPHPDataSet(__DIR__ . '/Assertions/Import/ReimportTouristAttractionWithMedia.php');
    }

    // 400 carries an HTML stub, not image bytes: the status must decide, not the body.
    // The sibling node shares the root, so it also pins that one bad image doesn't
    // cost the other records in the same response.
    #[Test]
    public function skipsOnlyTheImageThatFailsToDownload(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsTouristAttractionWithFailingImage.php');
        $this->expectFetch('attraction-with-failing-image.json');
        $this->expectFetch('018132452787-ngbe.json');
        $this->expectFetch('image-served-before-failure.json');
        $this->expectFetch('image-failing-download.json');
        $this->expectFetch('image-served-after-failure.json');
        $this->expectFetch('image-of-sibling.json');

        $this->expectFetchForUrl(
            'https://cms.thuecat.org/o/adaptive-media/image/5099196/Preview-1280x0/image',
            'cms.thuecat.org/image.jpg'
        );
        $this->expectFailureForUrl(
            'https://cms.thuecat.org/o/adaptive-media/image/72444626/Preview-1280x0/image',
            400,
            '<html><head><title>Redirect</title></head><body></body></html>'
        );
        $this->expectFetchForUrl(
            'https://cms.thuecat.org/o/adaptive-media/image/5099197/Preview-1280x0/image',
            'cms.thuecat.org/image.jpg'
        );
        $this->expectFetchForUrl(
            'https://cms.thuecat.org/o/adaptive-media/image/5099198/Preview-1280x0/image',
            'cms.thuecat.org/image.jpg'
        );

        $this->importConfiguration(1);

        $this->assertPHPDataSet(__DIR__ . '/Assertions/Import/ImportsTouristAttractionWithFailingImage.php');
    }

    #[Test]
    public function logsSkippedImageAsWarningWithoutFailingTheRun(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsTouristAttractionWithFailingImage.php');
        $this->expectFetch('attraction-with-failing-image.json');
        $this->expectFetch('018132452787-ngbe.json');
        $this->expectFetch('image-served-before-failure.json');
        $this->expectFetch('image-failing-download.json');
        $this->expectFetch('image-served-after-failure.json');
        $this->expectFetch('image-of-sibling.json');

        $this->expectFetchForUrl(
            'https://cms.thuecat.org/o/adaptive-media/image/5099196/Preview-1280x0/image',
            'cms.thuecat.org/image.jpg'
        );
        $this->expectFailureForUrl(
            'https://cms.thuecat.org/o/adaptive-media/image/72444626/Preview-1280x0/image',
            400,
            '<html><head><title>Redirect</title></head><body></body></html>'
        );
        $this->expectFetchForUrl(
            'https://cms.thuecat.org/o/adaptive-media/image/5099197/Preview-1280x0/image',
            'cms.thuecat.org/image.jpg'
        );
        $this->expectFetchForUrl(
            'https://cms.thuecat.org/o/adaptive-media/image/5099198/Preview-1280x0/image',
            'cms.thuecat.org/image.jpg'
        );

        $severity = $this->importConfiguration(1);

        // Unfetchable images are data drift: the command must still exit 0.
        self::assertSame('warning', $severity);

        // Queried rather than asserted as a data set: the surrounding savingEntity
        // rows are noise here, and only the skip entry is this test's subject.
        self::assertSame(
            [
                [
                    'type' => 'referenceSkipped',
                    'severity' => 'warning',
                    'table_name' => 'tx_thuecat_tourist_attraction',
                    'remote_id' => 'https://thuecat.org/resources/attraction-with-failing-image',
                    'message' => 'Skipped reference "https://cms.thuecat.org/o/adaptive-media/image/72444626/Preview-1280x0/image" for field "media_files": Image could not be downloaded.',
                ],
            ],
            $this->getSkippedReferenceLogEntries()
        );
    }

    // Promotion was gated on the run's overall severity while the finally
    // discarded staging regardless, so one failed root deleted every root's media.
    #[Test]
    public function promotesStagedMediaEvenWhenAnotherRootFailed(): void
    {
        $this->expectErrors = true;
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/PromotesMediaDespiteFailedRoot.php');
        $this->expectFetch('attraction-with-unmappable-relation.json');
        $this->expectFetch('attraction-under-healthy-root.json');
        $this->expectFetch('018132452787-ngbe.json');
        $this->expectFetch('image-under-healthy-root.json');
        $this->expectFetchForUrl(
            'https://cms.thuecat.org/o/adaptive-media/image/5099199/Preview-1280x0/image',
            'cms.thuecat.org/image.jpg'
        );

        $severity = $this->importConfiguration(1);

        self::assertSame('error', $severity, 'The failed root must still be reported.');
        self::assertFileExists(
            $this->instancePath . '/fileadmin-thuecat/thuecat/image-under-healthy-root_Bild-unter-heilem-Root.jpg',
            'Media of the healthy root must be promoted despite the other root failing.'
        );
        self::assertSame(
            [],
            glob($this->instancePath . '/fileadmin-thuecat/thuecat/_thuecat_import_*') ?: [],
            'The per-run staging folder must not outlive the run.'
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getSkippedReferenceLogEntries(): array
    {
        return $this->getConnectionPool()
            ->getConnectionForTable('tx_thuecat_import_log_entry')
            ->select(
                ['type', 'severity', 'table_name', 'remote_id', 'message'],
                'tx_thuecat_import_log_entry',
                ['type' => 'referenceSkipped'],
                [],
                ['uid' => 'ASC']
            )
            ->fetchAllAssociative()
        ;
    }

    private function seedExistingMediaFiles(): void
    {
        $folder = $this->instancePath . '/fileadmin-thuecat/thuecat';
        $source = $this->fixtureGuzzleBase . '/cms.thuecat.org/image.jpg';
        foreach ([
            'image-with-foreign-author_Bild-mit-externem-Autor.jpg',
            'image-with-author-string_Bild-mit-author.jpg',
            'image-with-license-author_Bild-mit-license-author.jpg',
            'image-with-author-and-license-author_Bild-mit-author-und-license-author.jpg',
        ] as $name) {
            copy($source, $folder . '/' . $name);
        }
    }

    /**
     * @return string highest severity recorded, in PSR-3 vocabulary
     */
    private function importConfiguration(int $uid): string
    {
        $this->workaroundExtbaseConfiguration();
        $configuration = $this->get(ImportConfigurationRepository::class)->findOneByUid($uid);
        self::assertNotNull($configuration, 'Fixture configuration uid=' . $uid . ' not found');
        return $this->get(Importer::class)->importConfiguration($configuration);
    }
}
