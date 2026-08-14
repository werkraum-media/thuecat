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
        // One asset per node: this test is about metadata variants, not sharing.
        foreach ([5099196, 5099201, 5099202, 5099203] as $id) {
            $this->expectFetchForUrl(
                'https://cms.thuecat.org/o/adaptive-media/image/' . $id . '/Preview-1280x0/image',
                'cms.thuecat.org/image.jpg'
            );
        }

        $this->importConfiguration(1);

        $this->assertPHPDataSet(__DIR__ . '/Assertions/Import/ImportsTouristAttractionWithMedia.php');
    }

    #[Test]
    public function relatesPhotoToMainImageAndImageToMediaFiles(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsTouristAttractionWithPhotoAndImage.php');
        $this->expectFetch('attraction-with-photo-and-image.json');
        $this->expectFetch('image-for-photo-slot.json');
        $this->expectFetch('image-for-image-slot.json');
        foreach ([5099210, 5099211] as $id) {
            $this->expectFetchForUrl(
                'https://cms.thuecat.org/o/adaptive-media/image/' . $id . '/Preview-1280x0/image',
                'cms.thuecat.org/image.jpg'
            );
        }

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
        $this->expectFetch('image-for-mixed-gallery.json');
        $this->expectFetchForUrl(
            'https://cms.thuecat.org/o/adaptive-media/image/5099212/Preview-1280x0/image',
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
                '1a2b3c4d-0000-4000-8000-000000000001_54eec04d6322ae4b.jpg',
                'image_652e88519d22ddba.jpg',
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

    // Two nodes, two titles, one asset URL; only one response is staged.
    #[Test]
    public function downloadsASharedAssetOnceWithinOneRecord(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsSharedAssetTwiceOnOneRecord.php');
        $this->expectFetch('attraction-with-shared-asset-twice.json');
        $this->expectFetch('image-shared-first-title.json');
        $this->expectFetch('image-shared-second-title.json');
        $this->expectFetchForUrl(
            'https://cms.thuecat.org/o/adaptive-media/image/5099200/Preview-1280x0/image',
            'cms.thuecat.org/image.jpg'
        );

        $this->importConfiguration(1);

        self::assertSame([], $this->getFailureLogEntries(), 'The asset must be fetched once.');
        self::assertSame(
            ['media_files'],
            $this->fetchReferenceFields(),
            'Same file, same field: the first node wins and the second is discarded.'
        );
        self::assertSame(
            1,
            $this->countRows('sys_file'),
            'One asset URL is one file, whatever the editorial title says.'
        );
    }

    #[Test]
    public function downloadsASharedAssetOnceAcrossTwoRecords(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsSharedAssetAcrossTwoRecords.php');
        $this->expectFetch('attraction-sharing-asset-first.json');
        $this->expectFetch('attraction-sharing-asset-second.json');
        $this->expectFetch('image-shared-first-title.json');
        $this->expectFetch('image-shared-second-title.json');
        $this->expectFetchForUrl(
            'https://cms.thuecat.org/o/adaptive-media/image/5099200/Preview-1280x0/image',
            'cms.thuecat.org/image.jpg'
        );

        $this->importConfiguration(1);

        self::assertSame([], $this->getFailureLogEntries(), 'The asset must be fetched once.');
        self::assertSame(
            ['media_files', 'media_files'],
            $this->fetchReferenceFields(),
            'Each record keeps its own reference to the shared file.'
        );
        self::assertSame(
            1,
            $this->countRows('sys_file'),
            'Two owners of one asset share one file.'
        );
    }

    // Only the expected URL of each pair is staged, so a wrong pick trips the faker.
    #[Test]
    public function downloadsTheOriginalRatherThanTheRendition(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsTouristAttractionWithUrlShapes.php');
        $this->expectFetch('attraction-with-url-shapes.json');
        foreach ([
            // both present, differing by size → the unsized contentUrl
            'http://img.oastatic.com/img/16864060/.jpg',
            // url only, and it carries no size segment → it is the original
            'http://img.oastatic.com/img/22900410/.jpg',
            // contentUrl only
            'https://cdb.thuecat.org/assets/ttg/m-tdm/original/url-shapes/9f8e7d6c-0000-4000-8000-000000000003.jpg',
            // both present, differing by format → contentUrl, not the .jpg
            'https://www.kulturcarre.de/media/a1b2c3d4e5f6/ansicht.webp',
        ] as $url) {
            $this->expectFetchForUrl($url, 'cms.thuecat.org/image.jpg');
        }

        $this->importConfiguration(1);

        self::assertSame([], $this->getFailureLogEntries(), 'No rendition may be requested.');
        self::assertSame(
            // oastatic paths end in "/.jpg", so those two have no stem to keep.
            [
                '1234967583140fb6.jpg',
                '9f8e7d6c-0000-4000-8000-000000000003_89383b9aeef7c2c7.jpg',
                'ansicht_08ad060dba414b14.webp',
                'f8f8def6c6e900fe.jpg',
            ],
            $this->fetchFileNamesSorted()
        );
    }

    /**
     * @return list<string>
     */
    private function fetchFileNamesSorted(): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('sys_file');
        $queryBuilder->getRestrictions()->removeAll();
        $rows = $queryBuilder
            ->select('name')
            ->from('sys_file')
            ->executeQuery()
            ->fetchAllAssociative()
        ;

        $names = [];
        foreach ($rows as $row) {
            if (is_string($row['name'])) {
                $names[] = $row['name'];
            }
        }
        sort($names);
        return $names;
    }

    // One asset in both slots: one download, one reference per field.
    #[Test]
    public function relatesOneAssetIntoEveryFieldItsKindsMapTo(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsTouristAttractionWithOneAssetInBothSlots.php');
        $this->expectFetch('attraction-with-one-asset-in-both-slots.json');
        $this->expectFetchForUrl(
            'https://cdb.thuecat.org/assets/ttg/m-tdm/original/both-slots/5e4d3c2b-0000-4000-8000-000000000001.jpg',
            'cms.thuecat.org/image.jpg'
        );

        $this->importConfiguration(1);

        self::assertSame([], $this->getFailureLogEntries(), 'The asset must be fetched once.');
        self::assertSame(1, $this->countRows('sys_file'), 'One asset is one file.');
        self::assertSame(
            ['main_image', 'media_files'],
            $this->fetchReferenceFields(),
            'photo and image map to separate fields, so both keep a reference.'
        );
        self::assertSame(
            ['main_image' => 'Als photo', 'media_files' => 'Als image'],
            $this->fetchReferenceTitlesByField(),
            'Each reference carries the title of the slot it came from.'
        );
    }

    /**
     * @return array<string, string>
     */
    private function fetchReferenceTitlesByField(): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('sys_file_reference');
        $queryBuilder->getRestrictions()->removeAll();
        $rows = $queryBuilder
            ->select('fieldname', 'title')
            ->from('sys_file_reference')
            ->orderBy('uid', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative()
        ;

        $titles = [];
        foreach ($rows as $row) {
            if (is_string($row['fieldname']) && is_string($row['title'])) {
                $titles[$row['fieldname']] = $row['title'];
            }
        }
        ksort($titles);
        return $titles;
    }

    // The reap names only the default row; Core is expected to cascade. Pinned
    // because a surviving translation shows an image the default language lost.
    #[Test]
    public function reapingADefaultLanguageReferenceDiscardsItsTranslation(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ReimportDropsTranslatedMediaReference.php');
        $this->seedExistingMediaFiles();
        $this->expectFetch('attraction-with-media.json');
        $this->expectFetch('018132452787-ngbe.json');
        $this->expectFetch('image-with-foreign-author.json');
        $this->expectFetch('author-with-names.json');
        $this->expectFetch('image-with-author-string.json');
        $this->expectFetch('image-with-license-author.json');
        $this->expectFetch('image-with-author-and-license-author.json');

        $this->importConfiguration(1);

        self::assertSame(
            [
                ['uid' => 1, 'lang' => 0, 'deleted' => 0],
                ['uid' => 2, 'lang' => 0, 'deleted' => 0],
                ['uid' => 3, 'lang' => 0, 'deleted' => 0],
                ['uid' => 4, 'lang' => 0, 'deleted' => 0],
                ['uid' => 5, 'lang' => 1, 'deleted' => 0],
                ['uid' => 6, 'lang' => 1, 'deleted' => 0],
                ['uid' => 7, 'lang' => 1, 'deleted' => 0],
                ['uid' => 8, 'lang' => 1, 'deleted' => 0],
                // No longer supplied: the default row and its translation go.
                ['uid' => 9, 'lang' => 0, 'deleted' => 1],
                ['uid' => 10, 'lang' => 1, 'deleted' => 1],
            ],
            $this->fetchReferenceStateWithLanguage()
        );
    }

    /**
     * @return list<array{uid: int, lang: int, deleted: int}>
     */
    private function fetchReferenceStateWithLanguage(): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('sys_file_reference');
        $queryBuilder->getRestrictions()->removeAll();
        $rows = $queryBuilder
            ->select('uid', 'sys_language_uid', 'deleted')
            ->from('sys_file_reference')
            ->orderBy('uid', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative()
        ;

        return array_map(static fn (array $row): array => [
            'uid' => is_numeric($row['uid']) ? (int)$row['uid'] : 0,
            'lang' => is_numeric($row['sys_language_uid']) ? (int)$row['sys_language_uid'] : 0,
            'deleted' => is_numeric($row['deleted']) ? (int)$row['deleted'] : 0,
        ], $rows);
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

        $severity = $this->importConfigurationReturningSeverity(1);

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

    /**
     * An exhausted retry must be distinguishable in the log from a rejected
     * request; both skip one image, but only one means the host was failing.
     */
    #[Test]
    public function skippedImageNamesTheExhaustedRetryAndItsAttemptCount(): void
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
        // One per attempt: maxAttempts defaults to 3.
        $failingImage = 'https://cms.thuecat.org/o/adaptive-media/image/72444626/Preview-1280x0/image';
        $this->expectFailureForUrl($failingImage, 503);
        $this->expectFailureForUrl($failingImage, 503);
        $this->expectFailureForUrl($failingImage, 503);
        $this->expectFetchForUrl(
            'https://cms.thuecat.org/o/adaptive-media/image/5099197/Preview-1280x0/image',
            'cms.thuecat.org/image.jpg'
        );
        $this->expectFetchForUrl(
            'https://cms.thuecat.org/o/adaptive-media/image/5099198/Preview-1280x0/image',
            'cms.thuecat.org/image.jpg'
        );

        $severity = $this->importConfigurationReturningSeverity(1);

        self::assertSame('warning', $severity, 'An unfetchable image stays a warning.');

        $entries = $this->getSkippedReferenceLogEntries();
        self::assertCount(1, $entries);
        self::assertIsString($entries[0]['message']);
        self::assertStringContainsString(
            'after 3 attempts',
            $entries[0]['message'],
            'The message names how many attempts were made.'
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

        $severity = $this->importConfigurationReturningSeverity(1);

        self::assertSame('error', $severity, 'The failed root must still be reported.');
        self::assertFileExists(
            $this->instancePath . '/fileadmin-thuecat/thuecat/image_2173acbddcfbcdb1.jpg',
            'Media of the healthy root must be promoted despite the other root failing.'
        );
        self::assertSame(
            [],
            glob($this->instancePath . '/fileadmin-thuecat/thuecat/_thuecat_import_*') ?: [],
            'The per-run staging folder must not outlive the run.'
        );
    }

    /**
     * The fixture carries both media shapes on one record: a referenced image
     * (its own API fetch, drained via the transient bucket) and an inline node.
     * Only the root is staged as an expected fetch, so any request either shape
     * makes trips the faker — which is the point of the option, the downloads
     * are merely what follows.
     */
    #[Test]
    public function skippingMediaImportsTheRecordWithoutFetchingEitherMediaShape(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsTouristAttractionWithMixedGallery.php');
        $this->expectFetch('attraction-with-mixed-gallery.json');

        $this->importConfigurationSkippingMedia(1);

        self::assertSame([], $this->fetchReferenceFields(), 'No media may be related.');
        self::assertSame(0, $this->countRows('sys_file'), 'No media may be downloaded.');
        self::assertSame(
            ['Attraction with mixed gallery'],
            $this->fetchAttractionTitles(),
            'The record itself must still import.'
        );
    }

    /**
     * Skipping media must not cost the run its file folder: the option also
     * bypasses the write-access probe, so an unresolvable folder is now
     * survivable rather than an abort before the first API call.
     */
    #[Test]
    public function skippingMediaImportsWithoutTouchingTheFileFolder(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsTouristAttractionWithMixedGallery.php');
        $this->expectFetch('attraction-with-mixed-gallery.json');
        GeneralUtility::rmdir($this->instancePath . '/fileadmin-thuecat/thuecat', true);

        $this->importConfigurationSkippingMedia(1);

        self::assertSame(
            ['Attraction with mixed gallery'],
            $this->fetchAttractionTitles(),
            'The record must import even with the configured folder gone.'
        );
    }

    private function countRows(string $table): int
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll();

        $count = $queryBuilder
            ->count('uid')
            ->from($table)
            ->executeQuery()
            ->fetchOne()
        ;

        return is_numeric($count) ? (int)$count : 0;
    }

    /**
     * @return list<string>
     */
    private function fetchAttractionTitles(): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tx_thuecat_tourist_attraction');
        $queryBuilder->getRestrictions()->removeAll();
        $rows = $queryBuilder
            ->select('title')
            ->from('tx_thuecat_tourist_attraction')
            ->orderBy('uid', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative()
        ;

        $titles = [];
        foreach ($rows as $row) {
            if (is_string($row['title'])) {
                $titles[] = $row['title'];
            }
        }
        return $titles;
    }

    /**
     * A repeat fetch becomes a mappingError rather than a failed run.
     *
     * @return list<array<string, mixed>>
     */
    private function getFailureLogEntries(): array
    {
        return $this->getConnectionPool()
            ->getConnectionForTable('tx_thuecat_import_log_entry')
            ->select(
                ['type', 'severity', 'table_name', 'remote_id', 'message'],
                'tx_thuecat_import_log_entry',
                ['severity' => 'error'],
                [],
                ['uid' => 'ASC']
            )
            ->fetchAllAssociative()
        ;
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
            'image_6ab24be70ef3f2e8.jpg',
            'image_89d8f4e95612e13d.jpg',
            'image_718be403bf38b616.jpg',
            'image_1bd2daee00b7ee9c.jpg',
        ] as $name) {
            copy($source, $folder . '/' . $name);
        }
    }
}
