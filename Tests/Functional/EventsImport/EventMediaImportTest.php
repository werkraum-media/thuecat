<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional\EventsImport;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WerkraumMedia\ThueCat\Domain\Repository\Backend\ImportConfigurationRepository;
use WerkraumMedia\ThueCat\Import\Importer;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractImportTestCase;

// Real media import for events: download → sys_file → sys_file_reference on
// tx_events_domain_model_event.images. Events keep every image in one field,
// so these also pin that the target field comes from the owner rather than
// the resolver.
class EventMediaImportTest extends AbstractImportTestCase
{
    protected array $testExtensionsToLoad = [
        'werkraummedia/thuecat/',
        'werkraummedia/events/',
    ];

    protected bool $stubMediaDownloader = false;

    protected bool $stubFileFolderAccess = false;

    protected string $fixtureGuzzleBase = __DIR__ . '/Fixtures/Guzzle';

    protected string $fixtureDomain = 'cdb.int.thuecat.org';

    protected string $fixturePath = 'api/resources';

    protected function setUp(): void
    {
        parent::setUp();

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
     * The DB resets between test methods, the fileadmin path does not. A
     * leftover file makes download() short-circuit on hasFile().
     */
    protected function tearDown(): void
    {
        GeneralUtility::rmdir($this->instancePath . '/fileadmin-thuecat', true);

        parent::tearDown();
    }

    #[Test]
    public function relatesReferencedImageToTheEventImagesField(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventMediaImportPreState.php');
        $this->expectFetch('e_referenced_media-tdm.json');
        $this->expectFetch('dms_167631738.json');
        $this->expectFetch('902877780916-bgnt.json');
        $this->expectFetchForUrl(
            'https://cms.thuecat.org/o/adaptive-media/image/167631738/Preview-1280x0/image',
            'cms.thuecat.org/image.jpg'
        );

        $this->runImport();

        self::assertSame(
            [
                [
                    'tablenames' => 'tx_events_domain_model_event',
                    'fieldname' => 'images',
                ],
            ],
            $this->fetchReferences()
        );
    }

    #[Test]
    public function relatesInlineImageToTheEventImagesField(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventInlineMediaPreState.php');
        $this->expectFetch('e_inline_media-tdm.json');
        // The asset host requires the API key; the faker normalises it away.
        $this->expectFetchForUrl(
            'https://cdb.thuecat.org/assets/ttg/m-tdm/original/7cbe5bb1-160b-4916-802c-c64dd2f1bf9e/c4915c4a-9a68-4a51-8d4e-782158f6887d.jpg',
            'cms.thuecat.org/image.jpg'
        );

        $this->runImport();

        self::assertSame(
            [
                [
                    'tablenames' => 'tx_events_domain_model_event',
                    'fieldname' => 'images',
                ],
            ],
            $this->fetchReferences(),
            'An inline media node carries everything needed; it must import like a referenced one.'
        );
    }

    #[Test]
    public function reimportReusesTheStoredInlineFile(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventInlineMediaReimportPreState.php');
        $this->seedInlineMediaFile();
        // No image fetch staged: reuse means no second download. An attempt
        // would trip the unexpected-fetch guard.
        $this->expectFetch('e_inline_media-tdm.json');

        $this->runImport();

        self::assertSame(
            [['uid' => 1, 'deleted' => 0]],
            $this->fetchReferenceState(),
            'A stable identity means the stored file and its reference are reused.'
        );
        self::assertSame(1, $this->countFiles(), 'No second sys_file for the same asset.');
    }

    private function countFiles(): int
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('sys_file');
        $queryBuilder->getRestrictions()->removeAll();
        $count = $queryBuilder->count('uid')->from('sys_file')->executeQuery()->fetchOne();

        return is_numeric($count) ? (int)$count : 0;
    }

    private function seedInlineMediaFile(): void
    {
        copy(
            $this->fixtureGuzzleBase . '/cms.thuecat.org/image.jpg',
            $this->instancePath . '/fileadmin-thuecat/thuecat/c4915c4a-9a68-4a51-8d4e-782158f6887d_31222b4549bdcfaa.jpg'
        );
    }

    #[Test]
    public function relatesBothInlineAndReferencedMediaOnOneEvent(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventMixedMediaPreState.php');
        $this->expectFetch('e_mixed_media-tdm.json');
        $this->expectFetch('dms_167631738.json');
        $this->expectFetch('902877780916-bgnt.json');
        $this->expectFetchForUrl(
            'https://cdb.thuecat.org/assets/ttg/m-tdm/original/7cbe5bb1-160b-4916-802c-c64dd2f1bf9e/c4915c4a-9a68-4a51-8d4e-782158f6887d.jpg',
            'cms.thuecat.org/image.jpg'
        );
        $this->expectFetchForUrl(
            'https://cms.thuecat.org/o/adaptive-media/image/167631738/Preview-1280x0/image',
            'cms.thuecat.org/image.jpg'
        );

        $this->runImport();

        // The two shapes are consumed at different stages — inline during the
        // rekey pass, referenced during the drain — but land on one owner.
        // Sorted, because relative order between the stages is not a promise:
        // both being present is.
        $names = $this->fetchRelatedFileNamesInOrder();
        sort($names);
        self::assertSame(
            [
                'c4915c4a-9a68-4a51-8d4e-782158f6887d_31222b4549bdcfaa.jpg',
                'image_3e6a3987344f6d38.jpg',
            ],
            $names
        );
    }

    #[Test]
    public function relatesBothShapesMixedInsideOneCollectionField(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventMixedGalleryPreState.php');
        $this->expectFetch('e_mixed_gallery-tdm.json');
        $this->expectFetch('dms_167631738.json');
        $this->expectFetch('902877780916-bgnt.json');
        // The inline asset appears twice (photo + gallery) but is one file, so
        // one download and one reference.
        $this->expectFetchForUrl(
            'https://cdb.thuecat.org/assets/ttg/m-tdm/original/7cbe5bb1-160b-4916-802c-c64dd2f1bf9e/c4915c4a-9a68-4a51-8d4e-782158f6887d.jpg',
            'cms.thuecat.org/image.jpg'
        );
        $this->expectFetchForUrl(
            'https://cms.thuecat.org/o/adaptive-media/image/167631738/Preview-1280x0/image',
            'cms.thuecat.org/image.jpg'
        );

        $this->runImport();

        // One field, entries of both shapes: each is judged on its own.
        $names = $this->fetchRelatedFileNamesInOrder();
        sort($names);
        self::assertSame(
            [
                'c4915c4a-9a68-4a51-8d4e-782158f6887d_31222b4549bdcfaa.jpg',
                'image_3e6a3987344f6d38.jpg',
            ],
            $names
        );
    }

    #[Test]
    public function reapsAnImageTheEventNoLongerSupplies(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventMediaReimportPreState.php');
        $this->seedExistingMediaFiles();
        // Both files are already in the target folder, so nothing downloads.
        $this->expectFetch('e_referenced_media-tdm.json');
        $this->expectFetch('dms_167631738.json');
        $this->expectFetch('902877780916-bgnt.json');

        $this->runImport();

        // uid 1 keeps its uid: still supplied, so reused rather than recreated.
        self::assertSame(
            [
                ['uid' => 1, 'deleted' => 0],
                ['uid' => 2, 'deleted' => 1],
            ],
            $this->fetchReferenceState(),
            'The reap must sweep the field the owner declares, not a hardcoded one.'
        );
    }

    // The old per-path staging could not express this: the inline path ran
    // before the referenced path had claimed anything.
    #[Test]
    public function reapsOneImageOfEachShapeInTheSameRun(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventMixedMediaReimportPreState.php');
        $this->seedMixedMediaFiles();
        $this->expectFetch('e_mixed_media-tdm.json');
        $this->expectFetch('dms_167631738.json');
        $this->expectFetch('902877780916-bgnt.json');

        $this->runImport();

        self::assertSame(
            [
                ['uid' => 1, 'deleted' => 0],
                ['uid' => 2, 'deleted' => 0],
                ['uid' => 3, 'deleted' => 1],
                ['uid' => 4, 'deleted' => 1],
            ],
            $this->fetchReferenceState()
        );
    }

    #[Test]
    public function reimportingUnchangedMediaDoesNotGrowTheRelation(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventMixedMediaReimportPreState.php');
        $this->seedMixedMediaFiles();
        $this->expectFetch('e_mixed_media-tdm.json');
        $this->expectFetch('dms_167631738.json');
        $this->expectFetch('902877780916-bgnt.json');

        $this->runImport();

        // A type:file relation appends, so without the reap this climbs on
        // every run.
        self::assertSame(
            2,
            $this->countLiveReferences(),
            'The two supplied images stay two references.'
        );
    }

    // Reaping compares against what this run produced.
    #[Test]
    public function leavesReferencesOfRecordsTheRunDidNotImport(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventMediaReimportUntouchedOwnerPreState.php');
        $this->seedExistingMediaFiles();
        $this->expectFetch('e_referenced_media-tdm.json');
        $this->expectFetch('dms_167631738.json');
        $this->expectFetch('902877780916-bgnt.json');

        $this->runImport();

        self::assertSame(
            [
                ['uid' => 1, 'deleted' => 0],
                ['uid' => 2, 'deleted' => 1],
                // Belongs to the event this run never fetched.
                ['uid' => 3, 'deleted' => 0],
            ],
            $this->fetchReferenceState()
        );
    }

    // A failing server says nothing about whether the asset still exists.
    #[Test]
    public function keepsTheReferenceWhenTheDownloadFailsWithAServerError(): void
    {
        $this->stageOwnerWithOneGoodImageAnd(503, 503, 503);

        self::assertSame(
            [
                ['uid' => 1, 'deleted' => 0],
                ['uid' => 2, 'deleted' => 0],
            ],
            $this->fetchReferenceState(),
            'A 5xx must never reap.'
        );
    }

    // 404 is the one signal that positively means the asset is gone.
    #[Test]
    public function reapsTheReferenceWhenTheMediaServerReportsItGone(): void
    {
        $this->stageOwnerWithOneGoodImageAnd(404);

        self::assertSame(
            [
                ['uid' => 1, 'deleted' => 1],
                ['uid' => 2, 'deleted' => 0],
            ],
            $this->fetchReferenceState(),
            'A 404 means the asset is gone, so the reference goes with it.'
        );
    }

    // 403 arrives for every asset on the host at once when a credential expires.
    #[Test]
    public function keepsTheReferenceWhenTheMediaServerRefusesAccess(): void
    {
        $this->stageOwnerWithOneGoodImageAnd(403);

        self::assertSame(
            [
                ['uid' => 1, 'deleted' => 0],
                ['uid' => 2, 'deleted' => 0],
            ],
            $this->fetchReferenceState(),
            'A refused request is a credential fault, not a removal.'
        );
    }

    #[Test]
    public function skippingMediaNeverReaps(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventPartialDownloadFailurePreState.php');
        $this->expectFetch('e_mixed_media-tdm.json');

        $this->runImportSkippingMedia();

        self::assertSame(
            [
                ['uid' => 1, 'deleted' => 0],
                ['uid' => 2, 'deleted' => 0],
            ],
            $this->fetchReferenceState(),
            'Media was never retrieved, so nothing may be concluded about it.'
        );
    }

    /** The success is what makes the owner reach the flush at all. */
    private function stageOwnerWithOneGoodImageAnd(int ...$statuses): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventPartialDownloadFailurePreState.php');
        // Seeded, so the good image reuses its stored file and reference
        // instead of creating a second one that orphans the first.
        $this->seedInlineMediaFile();
        $this->expectFetch('e_mixed_media-tdm.json');
        $this->expectFetch('dms_167631738.json');
        $this->expectFetch('902877780916-bgnt.json');
        foreach ($statuses as $status) {
            $this->expectFailureForUrl(
                'https://cms.thuecat.org/o/adaptive-media/image/167631738/Preview-1280x0/image',
                $status
            );
        }

        $this->runImport();
    }

    private function countLiveReferences(): int
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('sys_file_reference');
        $queryBuilder->getRestrictions()->removeAll();
        $count = $queryBuilder
            ->count('uid')
            ->from('sys_file_reference')
            ->where($queryBuilder->expr()->eq('deleted', 0))
            ->executeQuery()
            ->fetchOne()
        ;

        return is_numeric($count) ? (int)$count : 0;
    }

    private function seedMixedMediaFiles(): void
    {
        $folder = $this->instancePath . '/fileadmin-thuecat/thuecat';
        $source = $this->fixtureGuzzleBase . '/cms.thuecat.org/image.jpg';
        foreach ([
            'image_3e6a3987344f6d38.jpg',
            'c4915c4a-9a68-4a51-8d4e-782158f6887d_31222b4549bdcfaa.jpg',
            'dms_888888888_Referenziert-entfallen.jpg',
            'aaaaaaaa-0000-4000-8000-00000000ffff_deadbeefdeadbeef.jpg',
        ] as $name) {
            copy($source, $folder . '/' . $name);
        }
    }

    #[Test]
    public function relatesPhotoBeforeFurtherImagesInTheSingleField(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventOrderedMediaPreState.php');
        $this->expectFetch('e_ordered_media-tdm.json');
        $this->expectFetch('dms_167631738.json');
        $this->expectFetch('902877780916-bgnt.json');
        $this->expectFetch('dms_5099196.json');
        $this->expectFetchForUrl(
            'https://cms.thuecat.org/o/adaptive-media/image/167631738/Preview-1280x0/image',
            'cms.thuecat.org/image.jpg'
        );
        $this->expectFetchForUrl(
            'https://cms.thuecat.org/o/adaptive-media/image/5099196/Preview-1280x0/image',
            'cms.thuecat.org/image.jpg'
        );

        $this->runImport();

        // Both slots share one field, so the source ranking is all that
        // distinguishes them: schema:photo before schema:image.
        self::assertSame(
            [
                'image_3e6a3987344f6d38.jpg',
                'image_6ab24be70ef3f2e8.jpg',
            ],
            $this->fetchRelatedFileNamesInOrder()
        );
    }

    #[Test]
    public function importsAnEventWhoseMediaEntryIsInNeitherShape(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventUnusableMediaPreState.php');
        $this->expectFetch('e_unusable_media-tdm.json');
        $this->expectFetch('dms_167631738.json');
        $this->expectFetch('902877780916-bgnt.json');
        $this->expectFetchForUrl(
            'https://cms.thuecat.org/o/adaptive-media/image/167631738/Preview-1280x0/image',
            'cms.thuecat.org/image.jpg'
        );

        $this->runImport();

        self::assertSame(
            ['e_unusable_media-tdm' => ['image_3e6a3987344f6d38.jpg']],
            $this->fetchFileNamesByEventRemoteId(),
            'An uninterpretable entry costs that entry, not the root URL.'
        );
    }

    #[Test]
    public function importsAnEventWhoseOnlyMediaEntryIsUnusable(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventOnlyUnusableMediaPreState.php');
        $this->expectFetch('e_only_unusable_media-tdm.json');

        $this->runImport();

        // Nothing usable alongside it, so the skipped entry alone has to drain
        // the bucket — an undrained one raises "no progress" and costs the root.
        self::assertSame(
            ['e_only_unusable_media-tdm' => []],
            $this->fetchFileNamesByEventRemoteId()
        );
    }

    #[Test]
    public function runWithOnlyAnUnusableMediaEntryReportsWarning(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventUnusableMediaPreState.php');
        $this->expectFetch('e_unusable_media-tdm.json');
        $this->expectFetch('dms_167631738.json');
        $this->expectFetch('902877780916-bgnt.json');
        $this->expectFetchForUrl(
            'https://cms.thuecat.org/o/adaptive-media/image/167631738/Preview-1280x0/image',
            'cms.thuecat.org/image.jpg'
        );

        $severity = $this->runImportReturningSeverity();

        self::assertSame(
            'warning',
            $severity,
            'Data drift, not an operator error — at error a scheduler treats a '
            . 'healthy import as broken.'
        );
    }

    #[Test]
    public function relatesTwoDistinctInlineImagesSideBySide(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventTwoInlineMediaPreState.php');
        $this->expectFetch('e_two_inline_media-tdm.json');
        $this->expectFetchForUrl(
            'https://cdb.thuecat.org/assets/ttg/m-tdm/original/19c21523-343f-4b72-b13c-756ac4bde8c5/ad455b3e-dbfd-488a-92d5-9679fab51418.jpg',
            'cms.thuecat.org/image.jpg'
        );
        $this->expectFetchForUrl(
            'https://cdb.thuecat.org/assets/ttg/m-tdm/original/7cbe5bb1-160b-4916-802c-c64dd2f1bf9e/c4915c4a-9a68-4a51-8d4e-782158f6887d.jpg',
            'cms.thuecat.org/image.jpg'
        );

        $this->runImport();

        // Upstream usually repeats ONE node across both slots, which dedupes to
        // a single reference. Distinct nodes must not: both belong to the
        // editor, photo ranked first.
        //
        // The fixture's schema:position values contradict the delivery order
        // (photo carries 2, image 1), so an implementation sorting by position
        // would return these reversed.
        self::assertSame(
            [
                'ad455b3e-dbfd-488a-92d5-9679fab51418_85662d8758cdca09.jpg',
                'c4915c4a-9a68-4a51-8d4e-782158f6887d_31222b4549bdcfaa.jpg',
            ],
            $this->fetchRelatedFileNamesInOrder()
        );
    }

    #[Test]
    public function doesNotCarryMediaOverToAnEventThatDeclaresNone(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventMediaLeakPreState.php');
        $this->expectFetch('e_mixed_media-tdm.json');
        $this->expectFetch('e_no_media-tdm.json');
        $this->expectFetch('dms_167631738.json');
        $this->expectFetch('902877780916-bgnt.json');
        $this->expectFetchForUrl(
            'https://cdb.thuecat.org/assets/ttg/m-tdm/original/7cbe5bb1-160b-4916-802c-c64dd2f1bf9e/c4915c4a-9a68-4a51-8d4e-782158f6887d.jpg',
            'cms.thuecat.org/image.jpg'
        );
        $this->expectFetchForUrl(
            'https://cms.thuecat.org/o/adaptive-media/image/167631738/Preview-1280x0/image',
            'cms.thuecat.org/image.jpg'
        );

        $this->runImport();

        // Both media paths sit on the first event, so both are on trial.
        self::assertSame(
            [
                'e_mixed_media-tdm' => [
                    'c4915c4a-9a68-4a51-8d4e-782158f6887d_31222b4549bdcfaa.jpg',
                    'image_3e6a3987344f6d38.jpg',
                ],
                'e_no_media-tdm' => [],
            ],
            $this->fetchFileNamesByEventRemoteId()
        );
    }

    /**
     * File names per event, keyed by remote id. Events with no reference
     * appear too — that is the case under test.
     *
     * @return array<string, list<string>>
     */
    private function fetchFileNamesByEventRemoteId(): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tx_events_domain_model_event');
        $queryBuilder->getRestrictions()->removeAll();
        $events = $queryBuilder
            ->select('uid', 'remote_id')
            ->from('tx_events_domain_model_event')
            ->orderBy('uid', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative()
        ;

        $names = [];
        foreach ($events as $event) {
            if (!is_string($event['remote_id']) || !is_numeric($event['uid'])) {
                continue;
            }
            $key = (string)preg_replace('#^.*/#', '', $event['remote_id']);
            $names[$key] = $this->fetchFileNamesForEvent((int)$event['uid']);
        }

        return $names;
    }

    /**
     * @return list<string>
     */
    private function fetchFileNamesForEvent(int $eventUid): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('sys_file_reference');
        $queryBuilder->getRestrictions()->removeAll();
        $rows = $queryBuilder
            ->select('f.name')
            ->from('sys_file_reference', 'r')
            ->join('r', 'sys_file', 'f', $queryBuilder->expr()->eq('f.uid', $queryBuilder->quoteIdentifier('r.uid_local')))
            ->where(
                $queryBuilder->expr()->eq('r.tablenames', $queryBuilder->createNamedParameter('tx_events_domain_model_event')),
                $queryBuilder->expr()->eq('r.uid_foreign', $queryBuilder->createNamedParameter($eventUid)),
                $queryBuilder->expr()->eq('r.deleted', $queryBuilder->createNamedParameter(0))
            )
            ->orderBy('f.name', 'ASC')
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
            ->where($queryBuilder->expr()->eq('r.fieldname', $queryBuilder->createNamedParameter('images')))
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

    /**
     * @return list<array{uid: int, deleted: int}>
     */
    private function fetchReferenceState(): array
    {
        // Connection::select() applies the DefaultRestrictionContainer, which
        // hides deleted rows — the reaped reference must stay visible here.
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('sys_file_reference');
        $queryBuilder->getRestrictions()->removeAll();
        $rows = $queryBuilder
            ->select('uid', 'deleted')
            ->from('sys_file_reference')
            ->orderBy('uid', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative()
        ;

        return array_map(static fn (array $row): array => [
            'uid' => is_numeric($row['uid']) ? (int)$row['uid'] : 0,
            'deleted' => is_numeric($row['deleted']) ? (int)$row['deleted'] : 0,
        ], $rows);
    }

    private function seedExistingMediaFiles(): void
    {
        $folder = $this->instancePath . '/fileadmin-thuecat/thuecat';
        $source = $this->fixtureGuzzleBase . '/cms.thuecat.org/image.jpg';
        foreach ([
            'image_3e6a3987344f6d38.jpg',
            'dms_999999999_Nicht-mehr-geliefert.jpg',
        ] as $name) {
            copy($source, $folder . '/' . $name);
        }
    }

    /**
     * @return list<array{tablenames: string, fieldname: string}>
     */
    private function fetchReferences(): array
    {
        $rows = $this->getConnectionPool()
            ->getConnectionForTable('sys_file_reference')
            ->select(
                ['tablenames', 'fieldname'],
                'sys_file_reference',
                [],
                [],
                ['uid' => 'ASC']
            )
            ->fetchAllAssociative()
        ;

        return array_map(static fn (array $row): array => [
            'tablenames' => is_string($row['tablenames']) ? $row['tablenames'] : '',
            'fieldname' => is_string($row['fieldname']) ? $row['fieldname'] : '',
        ], $rows);
    }

    private function runImport(): void
    {
        $this->runImportReturningSeverity();
    }

    private function runImportSkippingMedia(): void
    {
        $this->workaroundExtbaseConfiguration();
        $configuration = $this->get(ImportConfigurationRepository::class)->findOneByUid(1);
        self::assertNotNull($configuration, 'Fixture configuration uid=1 not found');
        $this->get(Importer::class)->importConfiguration($configuration, null, null, false, true);
    }

    private function runImportReturningSeverity(): string
    {
        $this->workaroundExtbaseConfiguration();
        $configuration = $this->get(ImportConfigurationRepository::class)->findOneByUid(1);
        self::assertNotNull($configuration, 'Fixture configuration uid=1 not found');
        return $this->get(Importer::class)->importConfiguration($configuration);
    }
}
