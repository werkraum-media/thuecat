<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional\EventsImport;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\Connection;
use WerkraumMedia\ThueCat\Domain\Repository\Backend\ImportConfigurationRepository;
use WerkraumMedia\ThueCat\Import\Importer;
use WerkraumMedia\ThueCat\Import\StoragePidConfigurationException;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractImportTestCase;

// End-to-end category wiring: the Kreuzchor fixture maps to one category title
// ("Kulturveranstaltung"). These tests pin the find-or-create + site-scope +
// rootline-guard behaviour of Resolver::wireCategories, plus idempotency on
// re-import.
class EventCategoryImportTest extends AbstractImportTestCase
{
    protected array $testExtensionsToLoad = [
        'werkraummedia/thuecat/',
        'werkraummedia/events/',
    ];

    protected string $fixtureGuzzleBase = __DIR__ . '/Fixtures/Guzzle';
    protected string $fixtureDomain = 'cdb.int.thuecat.org';
    protected string $fixturePath = 'api/resources';

    #[Test]
    public function createsCategoryAsDirectChildOfParentAtCategoryStoragePid(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventCategoryImportPreState.php');
        $this->expectFetch('e_19542-hubev.json');

        $this->runImport();

        $created = $this->fetchCategoriesByRemoteId('type:thuecat:CultureEvent');
        self::assertCount(1, $created, 'Exactly one category should be created.');
        self::assertSame(100, $created[0]['parent'], 'Created as a direct child of the configured parent.');
        self::assertSame(20, $created[0]['pid'], 'Created at the configured categoryStoragePid.');
        self::assertSame('Kulturveranstaltung', $created[0]['title'], 'Seeded with the mapped label.');

        $this->assertEventHasCategory((int)$created[0]['uid']);
    }

    #[Test]
    public function createsAndWiresEveryMappedCategoryOfAMultiTypeEvent(): void
    {
        // Distel maps to two categories; schema:ComedyEvent is unmapped.
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventCategoryImportDistelPreState.php');
        $this->expectFetch('e_100771372-hubev.json');

        $this->runImport();

        $series = $this->fetchCategoriesByRemoteId('type:schema:EventSeries');
        $culture = $this->fetchCategoriesByRemoteId('type:thuecat:CultureEvent');
        self::assertCount(1, $series, 'Veranstaltungsserie category created.');
        self::assertCount(1, $culture, 'Kulturveranstaltung category created.');

        foreach ([$series[0], $culture[0]] as $category) {
            self::assertSame(100, $category['parent'], 'Created under the configured parent.');
            self::assertSame(20, $category['pid'], 'Created at categoryStoragePid.');
        }
        self::assertSame('Veranstaltungsserie', $series[0]['title']);
        self::assertSame('Kulturveranstaltung', $culture[0]['title']);

        // The one event carries both categories, and nothing was created for the
        // unmapped schema:ComedyEvent (exactly two category rows under parent 100).
        self::assertSame(2, $this->countCategoriesUnderParent(100), 'Only the two mapped categories exist.');
        $this->assertEventHasCategory((int)$series[0]['uid']);
        $this->assertEventHasCategory((int)$culture[0]['uid']);
    }

    #[Test]
    public function sharesOneCategoryAcrossEventsFromDifferentRootUrls(): void
    {
        // Two roots, both mapping to Kulturveranstaltung. resolve() runs once per
        // URL, so this pins the run-scoped dedup (categoryKeyByRemoteId on the
        // context) — the shared category must be created exactly once.
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventCategoryImportTwoRootsPreState.php');
        $this->expectFetch('e_19542-hubev.json');
        $this->expectFetch('e_100771372-hubev.json');

        $this->runImport();

        $culture = $this->fetchCategoriesByRemoteId('type:thuecat:CultureEvent');
        self::assertCount(1, $culture, 'Shared category is created once across both roots.');

        // Both events relate to the single shared category.
        self::assertSame(2, $this->countCategoryRelations((int)$culture[0]['uid']), 'Both events wired to it.');
    }

    #[Test]
    public function logsMatchedAndUnmatchedTypesForTheIntegratorReport(): void
    {
        // Distel: thuecat:CultureEvent + schema:EventSeries map; schema:ComedyEvent
        // is unmatched; structural types (schema:Thing/Event, dcmitype/ttgds:Event)
        // are consciously ignored and must not appear.
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventCategoryImportDistelPreState.php');
        $this->expectFetch('e_100771372-hubev.json');

        $this->runImport();

        $matched = $this->fetchLogEntries('categoryMatched');
        $unmatched = $this->fetchLogEntries('categoryUnmatched');

        // Matched entries carry the RESOLVED sys_category uid (record_uid +
        // table_name), not a label snapshot — so the report renders the current
        // title live and survives editor renames. Assert each matched type points
        // at the category actually created for it.
        $matchedByType = [];
        foreach ($matched as $entry) {
            $matchedByType[$entry['remote_id']] = $entry;
        }

        foreach (['schema:EventSeries', 'thuecat:CultureEvent'] as $type) {
            self::assertArrayHasKey($type, $matchedByType, $type . ' logged as matched.');
            $entry = $matchedByType[$type];
            self::assertSame('sys_category', $entry['table_name']);
            self::assertGreaterThan(0, $entry['record_uid'], 'Resolved category uid is stored.');
            self::assertSame(
                $type === 'schema:EventSeries' ? 'Veranstaltungsserie' : 'Kulturveranstaltung',
                $this->categoryTitle($entry['record_uid']),
                'record_uid points at the category created for this type.'
            );
        }

        // Containment, not exact lists: the ignore list is intentionally sparse
        // and will grow, so pinning the full unmatched set would be brittle.
        self::assertContains(
            'schema:ComedyEvent',
            array_column($unmatched, 'remote_id'),
            'An unmappable type is logged as unmatched.'
        );

        foreach (array_merge($matched, $unmatched) as $entry) {
            self::assertSame('categories', $entry['kind']);
        }
    }

    #[Test]
    public function reusesCategoryByRemoteIdEvenAfterEditorRenamedItsTitle(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventCategoryImportRenamedPreState.php');
        $this->expectFetch('e_19542-hubev.json');

        $this->runImport();

        // Matched by remote_id despite the changed title → uid 101 reused, no
        // duplicate, and the editor's rename is preserved.
        $all = $this->fetchCategoriesByRemoteId('type:thuecat:CultureEvent');
        self::assertCount(1, $all, 'No new category — the renamed one is reused by remote_id.');
        self::assertSame(101, $all[0]['uid']);
        self::assertSame('Kultur (renamed)', $all[0]['title'], 'Import must not rename an existing category.');

        $this->assertEventHasCategory(101);
    }

    #[Test]
    public function reusesExistingCategoryWhenParentIsAnywhereInItsRootline(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventCategoryImportReusePreState.php');
        $this->expectFetch('e_19542-hubev.json');

        $this->runImport();

        // uid 101 is a grandchild of parent 100; no new category is created.
        $all = $this->fetchCategoriesByRemoteId('type:thuecat:CultureEvent');
        self::assertCount(1, $all, 'No duplicate category is created — the grandchild is reused.');
        self::assertSame(101, $all[0]['uid']);

        $this->assertEventHasCategory(101);
    }

    #[Test]
    public function doesNotReuseCategoryOutsideParentRootlineOrOutsideSite(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventCategoryImportForeignPreState.php');
        $this->expectFetch('e_19542-hubev.json');

        $this->runImport();

        // Decoys 301 (wrong parent) and 401 (other site) survive; a fresh one is
        // created under 100.
        $all = $this->fetchCategoriesByRemoteId('type:thuecat:CultureEvent');
        self::assertCount(3, $all, 'Two decoys remain plus one freshly created category.');

        $uids = array_column($all, 'uid');
        self::assertContains(301, $uids, 'In-site wrong-parent decoy is left untouched.');
        self::assertContains(401, $uids, 'Other-site decoy is left untouched.');

        $created = array_values(array_filter($all, static fn (array $r): bool => $r['parent'] === 100));
        self::assertCount(1, $created, 'Exactly one category is created under the configured parent.');
        self::assertSame(20, $created[0]['pid']);

        $this->assertEventHasCategory((int)$created[0]['uid']);
    }

    #[Test]
    public function reimportCreatesNoDuplicateCategoryOrRelation(): void
    {
        // Pre-state already holds the category (uid 101) + event + MM from a
        // prior import; importing once more must stay idempotent.
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventCategoryImportReimportPreState.php');
        $this->expectFetch('e_19542-hubev.json');

        $this->runImport();

        $all = $this->fetchCategoriesByRemoteId('type:thuecat:CultureEvent');
        self::assertCount(1, $all, 'Re-import reuses the category, no duplicate sys_category row.');
        self::assertSame(101, $all[0]['uid']);

        $mmCount = $this->countCategoryRelations((int)$all[0]['uid']);
        self::assertSame(1, $mmCount, 'Re-import does not duplicate the sys_category_record_mm row.');
    }

    #[Test]
    public function skipsCategoryWiringWhenNoParentConfigured(): void
    {
        // Same pre-state minus the category fields → categoryParent/pid resolve
        // to 0 → wiring is skipped, no category touched, event still imported.
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventCategoryImportNoParentPreState.php');
        $this->expectFetch('e_19542-hubev.json');

        $this->runImport();

        self::assertCount(0, $this->fetchCategoriesByRemoteId('type:thuecat:CultureEvent'));
        self::assertSame(1, $this->countEvents(), 'Event is imported even though category wiring is skipped.');
    }

    #[Test]
    public function abortsBeforeFetchingWhenStoragePidHasNoSite(): void
    {
        // No expectFetch(): the pre-flight validator must throw before the URL
        // loop, so no HTTP request is attempted.
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventCategoryImportNoSitePreState.php');

        $this->expectException(StoragePidConfigurationException::class);
        $this->expectExceptionCode(1752570000);

        $this->runImport();
    }

    private function runImport(): void
    {
        $this->workaroundExtbaseConfiguration();
        $configuration = $this->get(ImportConfigurationRepository::class)->findOneByUid(1);
        self::assertNotNull($configuration, 'Import configuration not found in pre-state.');
        $this->get(Importer::class)->importConfiguration($configuration);
    }

    /**
     * @return list<array{uid: int, pid: int, parent: int, title: string}>
     */
    private function fetchCategoriesByRemoteId(string $remoteId): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('sys_category');
        $queryBuilder->getRestrictions()->removeAll();
        $rows = $queryBuilder->select('uid', 'pid', 'parent', 'title')
            ->from('sys_category')
            ->where(
                $queryBuilder->expr()->eq('remote_id', $queryBuilder->createNamedParameter($remoteId)),
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT))
            )
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative()
        ;

        return array_map(static fn (array $r): array => [
            'uid' => (int)(is_numeric($r['uid']) ? $r['uid'] : 0),
            'pid' => (int)(is_numeric($r['pid']) ? $r['pid'] : 0),
            'parent' => (int)(is_numeric($r['parent']) ? $r['parent'] : 0),
            'title' => is_string($r['title']) ? $r['title'] : '',
        ], $rows);
    }

    private function assertEventHasCategory(int $categoryUid): void
    {
        self::assertSame(
            1,
            $this->countCategoryRelations($categoryUid),
            'Event must be related to the category via sys_category_record_mm.'
        );
    }

    private function countCategoryRelations(int $categoryUid): int
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('sys_category_record_mm');
        $count = $queryBuilder->count('uid_local')
            ->from('sys_category_record_mm')
            ->where(
                $queryBuilder->expr()->eq('uid_local', $queryBuilder->createNamedParameter($categoryUid, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('tablenames', $queryBuilder->createNamedParameter('tx_events_domain_model_event')),
                $queryBuilder->expr()->eq('fieldname', $queryBuilder->createNamedParameter('categories'))
            )
            ->executeQuery()
            ->fetchOne()
        ;

        return is_numeric($count) ? (int)$count : 0;
    }

    private function countCategoriesUnderParent(int $parentUid): int
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('sys_category');
        $queryBuilder->getRestrictions()->removeAll();
        $count = $queryBuilder->count('uid')
            ->from('sys_category')
            ->where(
                $queryBuilder->expr()->eq('parent', $queryBuilder->createNamedParameter($parentUid, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT))
            )
            ->executeQuery()
            ->fetchOne()
        ;

        return is_numeric($count) ? (int)$count : 0;
    }

    /**
     * @return list<array{remote_id: string, kind: string, table_name: string, record_uid: int}>
     */
    private function fetchLogEntries(string $type): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tx_thuecat_import_log_entry');
        $queryBuilder->getRestrictions()->removeAll();
        $rows = $queryBuilder->select('remote_id', 'kind', 'table_name', 'record_uid')
            ->from('tx_thuecat_import_log_entry')
            ->where($queryBuilder->expr()->eq('type', $queryBuilder->createNamedParameter($type)))
            ->orderBy('remote_id')
            ->executeQuery()
            ->fetchAllAssociative()
        ;

        return array_map(static fn (array $r): array => [
            'remote_id' => is_string($r['remote_id']) ? $r['remote_id'] : '',
            'kind' => is_string($r['kind']) ? $r['kind'] : '',
            'table_name' => is_string($r['table_name']) ? $r['table_name'] : '',
            'record_uid' => is_numeric($r['record_uid']) ? (int)$r['record_uid'] : 0,
        ], $rows);
    }

    private function categoryTitle(int $uid): string
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('sys_category');
        $queryBuilder->getRestrictions()->removeAll();
        $title = $queryBuilder->select('title')
            ->from('sys_category')
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchOne()
        ;

        return is_string($title) ? $title : '';
    }

    private function countEvents(): int
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tx_events_domain_model_event');
        $queryBuilder->getRestrictions()->removeAll();
        $count = $queryBuilder->count('uid')
            ->from('tx_events_domain_model_event')
            ->where($queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchOne()
        ;

        return is_numeric($count) ? (int)$count : 0;
    }
}
