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
        $ancestor = $this->fetchCategoriesByRemoteId('type:schema:Event');
        self::assertCount(1, $ancestor);
        self::assertSame(
            $ancestor[0]['uid'],
            $created[0]['parent'],
            'Created beneath its own ancestor, which hangs off the configured parent.'
        );
        self::assertSame(100, $ancestor[0]['parent'], 'The chain still starts at the configured parent.');
        self::assertSame(20, $created[0]['pid'], 'Created at the configured categoryStoragePid.');
        self::assertSame('Kulturveranstaltung', $created[0]['title'], 'Seeded with the mapped label.');

        $this->assertEventHasCategory((int)$created[0]['uid']);
    }

    #[Test]
    public function createsAndWiresEveryMappedCategoryOfAMultiTypeEvent(): void
    {
        // Distel maps to two categories; the fixture's test: type is unmappable
        // by construction, so no real URI can silently start matching it.
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventCategoryImportDistelPreState.php');
        $this->expectFetch('e_100771372-hubev.json');

        $this->runImport();

        $series = $this->fetchCategoriesByRemoteId('type:schema:EventSeries');
        $culture = $this->fetchCategoriesByRemoteId('type:thuecat:CultureEvent');
        self::assertCount(1, $series, 'Veranstaltungsserie category created.');
        self::assertCount(1, $culture, 'Kulturveranstaltung category created.');

        foreach ([$series[0], $culture[0]] as $category) {
            self::assertGreaterThan(0, $category['parent'], 'Created somewhere in the anchor\'s tree.');
            self::assertSame(20, $category['pid'], 'Created at categoryStoragePid.');
        }
        self::assertSame('Veranstaltungsserie', $series[0]['title']);
        self::assertSame('Kulturveranstaltung', $culture[0]['title']);

        // The unmappable type creates nothing. Counting every row under the
        // anchor would count ancestors too, which say nothing about it.
        self::assertSame(
            [],
            $this->fetchCategoriesByRemoteId('type:test:UnmappedByConstruction'),
            'An unmappable type creates no category.'
        );
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
        // Distel: schema:EventSeries needs the fallback map for its title and so
        // is reported; thuecat:CultureEvent resolves wholly upstream and is not.
        // test:UnmappedByConstruction is unmatched; structural types
        // (schema:Thing/Event, dcmitype/ttgds:Event) are consciously ignored.
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

        $type = 'schema:EventSeries';
        self::assertArrayHasKey($type, $matchedByType, $type . ' logged as matched.');
        $entry = $matchedByType[$type];
        self::assertSame('sys_category', $entry['table_name']);
        self::assertGreaterThan(0, $entry['record_uid'], 'Resolved category uid is stored.');
        self::assertSame(
            'Veranstaltungsserie',
            $this->categoryTitle($entry['record_uid']),
            'record_uid points at the category created for this type.'
        );

        self::assertArrayNotHasKey(
            'thuecat:CultureEvent',
            $matchedByType,
            'Upstream titled it without the map, so it needs nobody\'s attention.'
        );

        // Containment, not exact lists: the ignore list is intentionally sparse
        // and will grow, so pinning the full unmatched set would be brittle.
        self::assertContains(
            'test:UnmappedByConstruction',
            array_column($unmatched, 'remote_id'),
            'An unmappable type is logged as unmatched.'
        );

        foreach (array_merge($matched, $unmatched) as $entry) {
            self::assertSame('categories', $entry['kind']);
        }
    }

    // The report names what a person maintains. thuecat:CultureEvent resolves
    // wholly upstream in the seeded vocabulary, so nobody needs to know about
    // it; schema:EventSeries needs the map for its title.
    #[Test]
    public function reportsNothingForATypeResolvedWhollyUpstream(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventCategoryImportDistelPreState.php');
        $this->expectFetch('e_100771372-hubev.json');

        $this->runImport();

        self::assertNotContains(
            'thuecat:CultureEvent',
            array_column($this->fetchLogEntries('categoryMatched'), 'remote_id'),
            'Upstream answered every configured language; the map was never consulted.'
        );
    }

    #[Test]
    public function reportsATypeTheFallbackMapHadToTitle(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventCategoryImportDistelPreState.php');
        $this->expectFetch('e_100771372-hubev.json');

        $this->runImport();

        self::assertContains(
            'schema:EventSeries',
            array_column($this->fetchLogEntries('categoryMatched'), 'remote_id'),
            'The vocabulary does not know it, so its title came from the map.'
        );
    }

    #[Test]
    public function stillReportsATypeNeitherSourceNames(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventCategoryImportDistelPreState.php');
        $this->expectFetch('e_100771372-hubev.json');

        $this->runImport();

        self::assertContains(
            'test:UnmappedByConstruction',
            array_column($this->fetchLogEntries('categoryUnmatched'), 'remote_id')
        );
    }

    #[Test]
    public function keepsIgnoredTypesOutOfBothReports(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventCategoryImportDistelPreState.php');
        $this->expectFetch('e_100771372-hubev.json');

        $this->runImport();

        $reported = array_merge(
            array_column($this->fetchLogEntries('categoryMatched'), 'remote_id'),
            array_column($this->fetchLogEntries('categoryUnmatched'), 'remote_id')
        );

        foreach (['schema:Thing', 'dcmitype:Event', 'ttgds:Event'] as $structural) {
            self::assertNotContains($structural, $reported, $structural . ' is consciously ignored.');
        }
    }

    // schema:EventSeries has no chain in the seeded vocabulary, which is what
    // the report exists to surface: a type nobody has modelled upstream.
    #[Test]
    public function reportsATypeThatHasNoHierarchyAtAll(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventCategoryImportDistelPreState.php');
        $this->expectFetch('e_100771372-hubev.json');

        $this->runImport();

        self::assertContains(
            'schema:EventSeries',
            array_column($this->fetchLogEntries('categoryWithoutHierarchy'), 'remote_id')
        );
    }

    #[Test]
    public function reportsTheParentPassedOverWhenATypeBranches(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventCategoryImportPreState.php');
        // CultureEvent branches: neither parent is an ancestor of the other, so
        // one is followed and the other reported.
        $this->seedVocabularyIndex([
            'thuecat:CultureEvent' => [['schema:Event', 'schema:Series'], ['de' => 'Kulturveranstaltung']],
            'schema:Event' => [[], ['de' => 'Veranstaltung']],
            'schema:Series' => [[], ['de' => 'Serie']],
        ]);
        $this->expectFetch('e_19542-hubev.json');

        $this->runImport();

        $entries = $this->fetchLogEntries('categoryParentChosen');

        self::assertContains(
            'thuecat:CultureEvent',
            array_column($entries, 'remote_id'),
            'A genuine branch is a decision worth reviewing against real data.'
        );
    }

    #[Test]
    public function reportsNoChoiceWhereParentsMerelyRestateTheChain(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventCategoryImportPreState.php');
        // Both parents named, but one is an ancestor of the other: reduction
        // settles it, so there is no choice to report.
        $this->seedVocabularyIndex([
            'thuecat:CultureEvent' => [['schema:Event', 'schema:MusicEvent'], ['de' => 'Kulturveranstaltung']],
            'schema:MusicEvent' => [['schema:Event'], ['de' => 'Musikereignis']],
            'schema:Event' => [[], ['de' => 'Veranstaltung']],
        ]);
        $this->expectFetch('e_19542-hubev.json');

        $this->runImport();

        self::assertSame([], $this->fetchLogEntries('categoryParentChosen'));
    }

    // A category is a record like any other: an editor sees it in the language
    // they work in, or does not see it at all.
    #[Test]
    public function createsATranslationForACategoryUpstreamNamesInThatLanguage(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventCategoryImportPreState.php');
        $this->expectFetch('e_19542-hubev.json');

        $this->runImport();

        self::assertSame(
            ['Culture event'],
            $this->fetchCategoryTranslations('type:thuecat:CultureEvent', 1),
            'The English label upstream carries must reach the database.'
        );
    }

    #[Test]
    public function translatesAnAncestorTheSameWay(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventCategoryImportPreState.php');
        $this->expectFetch('e_19542-hubev.json');

        $this->runImport();

        self::assertSame(
            ['Event'],
            $this->fetchCategoryTranslations('type:schema:Event', 1),
            'An ancestor is as visible to an editor as the type itself.'
        );
    }

    #[Test]
    public function createsNoTranslationForALanguageUpstreamDoesNotName(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventCategoryImportPreState.php');
        $this->expectFetch('e_19542-hubev.json');

        $this->runImport();

        // The site configures French; upstream offers none, so none is invented.
        self::assertSame([], $this->fetchCategoryTranslations('type:thuecat:CultureEvent', 2));
    }

    // The two languages part company here, and the difference is worth
    // knowing: a default-language title is the editor's, a translated one is
    // upstream's and is rewritten on every import.
    #[Test]
    public function keepsAnEditorsDefaultLanguageTitleOnReimport(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventCategoryImportRenamedPreState.php');
        $this->expectFetch('e_19542-hubev.json');

        $this->runImport();

        $all = $this->fetchCategoriesByRemoteId('type:thuecat:CultureEvent');
        self::assertCount(1, $all);
        self::assertSame('Kultur (renamed)', $all[0]['title']);
    }

    #[Test]
    public function overwritesAnEditorsTranslatedTitleOnReimport(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventCategoryImportRenamedPreState.php');
        $this->expectFetch('e_19542-hubev.json');

        $this->runImport();

        // DOCUMENTS CURRENT BEHAVIOUR: submitting the translated title is what
        // keeps it current with upstream, and the same write discards an
        // editor's wording. The default language protects itself by never being
        // rewritten; a translation has no such guard.
        self::assertSame(
            ['Culture event'],
            $this->fetchCategoryTranslations('type:thuecat:CultureEvent', 1),
            'The editor\'s "Culture (renamed by editor)" does not survive.'
        );
    }

    // The pre-state stands for a prior import: category and event already
    // exist, so this run is the second one.
    #[Test]
    public function reimportDoesNotDuplicateCategoryTranslations(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventCategoryImportReimportPreState.php');
        $this->expectFetch('e_19542-hubev.json');

        $this->runImport();

        self::assertCount(
            1,
            $this->fetchCategoryTranslations('type:thuecat:CultureEvent', 1),
            'A second run updates the translation rather than adding another.'
        );
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

    // schema:EventSeries is mapped to a title but absent from the vocabulary,
    // so it has a name and no ancestry.
    #[Test]
    public function createsAFlatCategoryForATypeTheVocabularyDoesNotKnow(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventCategoryImportDistelPreState.php');
        $this->expectFetch('e_100771372-hubev.json');

        $this->runImport();

        $series = $this->fetchCategoriesByRemoteId('type:schema:EventSeries');

        self::assertCount(1, $series, 'No hierarchy must not cost the category.');
        self::assertSame(100, $series[0]['parent'], 'With nothing above it, it hangs off the anchor.');
        self::assertSame('Veranstaltungsserie', $series[0]['title'], 'Titled from the fallback map.');
    }

    #[Test]
    public function relatesTheRecordToAFlatCategory(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventCategoryImportDistelPreState.php');
        $this->expectFetch('e_100771372-hubev.json');

        $this->runImport();

        $series = $this->fetchCategoriesByRemoteId('type:schema:EventSeries');
        self::assertCount(1, $series);

        self::assertSame(
            1,
            $this->countCategoryRelations($series[0]['uid']),
            'A type is related whether or not it has ancestry.'
        );
    }

    #[Test]
    public function createsNothingForATypeWithNeitherHierarchyNorTitle(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventCategoryImportDistelPreState.php');
        $this->expectFetch('e_100771372-hubev.json');

        $this->runImport();

        self::assertSame(
            [],
            $this->fetchCategoriesByRemoteId('type:test:UnmappedByConstruction'),
            'Unknown to both sources: the skipping rule governs, not this one.'
        );
    }

    // The upgrade path: a type imported flat before its vocabulary covered it
    // gains ancestry without losing the uid a plugin configuration references.
    #[Test]
    public function reParentsAFlatCategoryOnceItsHierarchyBecomesKnown(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventCategoryImportRenamedPreState.php');
        // The seeded vocabulary knew nothing of CultureEvent when uid 101 was
        // created flat; now it does.
        $this->seedVocabularyIndex([
            'thuecat:CultureEvent' => [['schema:Event'], ['de' => 'Kulturveranstaltung']],
            'schema:Event' => [[], ['de' => 'Veranstaltung']],
        ]);
        $this->expectFetch('e_19542-hubev.json');

        $this->runImport();

        $all = $this->fetchCategoriesByRemoteId('type:thuecat:CultureEvent');
        $ancestor = $this->fetchCategoriesByRemoteId('type:schema:Event');

        self::assertCount(1, $all, 'The flat category is moved, not replaced.');
        self::assertSame(101, $all[0]['uid']);
        self::assertCount(1, $ancestor);
        self::assertSame($ancestor[0]['uid'], $all[0]['parent']);
    }

    // The migration case: categories imported before the hierarchy existed sit
    // flat under the anchor. Their uids are referenced from plugin
    // configurations, so re-parenting must move them, never replace them.
    #[Test]
    public function reParentsAPreExistingFlatCategoryInPlace(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventCategoryImportRenamedPreState.php');
        $this->expectFetch('e_19542-hubev.json');

        $this->runImport();

        $all = $this->fetchCategoriesByRemoteId('type:thuecat:CultureEvent');
        self::assertCount(1, $all, 'Re-parenting must not create a second record.');
        self::assertSame(101, $all[0]['uid'], 'The uid a plugin configuration may reference survives.');

        $ancestor = $this->fetchCategoriesByRemoteId('type:schema:Event');
        self::assertCount(1, $ancestor);
        self::assertSame($ancestor[0]['uid'], $all[0]['parent'], 'It moves beneath its ancestor.');
    }

    #[Test]
    public function keepsTheRecordRelatedToTheSameUidAfterReParenting(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventCategoryImportRenamedPreState.php');
        $this->expectFetch('e_19542-hubev.json');

        $this->runImport();

        // A relation pointing at a replaced record would look identical in the
        // tree and be wrong everywhere it is referenced.
        $this->assertEventHasCategory(101);
    }

    #[Test]
    public function reParentingPreservesTheEditorsTitle(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventCategoryImportRenamedPreState.php');
        $this->expectFetch('e_19542-hubev.json');

        $this->runImport();

        $all = $this->fetchCategoriesByRemoteId('type:thuecat:CultureEvent');
        self::assertSame(
            'Kultur (renamed)',
            $all[0]['title'],
            'Moving a category is not an occasion to rename it.'
        );
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

        $created = array_values(array_filter(
            $all,
            fn (array $row): bool => $this->isInRootlineOf($row['uid'], 100)
        ));
        self::assertCount(1, $created, 'Exactly one category is created within the configured parent\'s tree.');
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

    // Pre-state: the event carries CultureEvent (still named), MusicEvent (no
    // longer named) and an editor's own category. The payload names only the
    // first.
    // The event names thuecat:CultureEvent; the vocabulary puts schema:Event
    // above it, and schema:Thing above that.
    #[Test]
    public function createsACategoryForEachAncestorOfTheNamedType(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventCategoryImportPreState.php');
        $this->expectFetch('e_19542-hubev.json');

        $this->runImport();

        $ancestor = $this->fetchCategoriesByRemoteId('type:schema:Event');
        $named = $this->fetchCategoriesByRemoteId('type:thuecat:CultureEvent');

        self::assertCount(1, $ancestor, 'The ancestor becomes a category of its own.');
        self::assertCount(1, $named);
        self::assertSame($ancestor[0]['uid'], $named[0]['parent'], 'The named type hangs beneath it.');
    }

    #[Test]
    public function relatesTheRecordOnlyToTheTypeItNames(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventCategoryImportPreState.php');
        $this->expectFetch('e_19542-hubev.json');

        $this->runImport();

        $ancestor = $this->fetchCategoriesByRemoteId('type:schema:Event');
        $named = $this->fetchCategoriesByRemoteId('type:thuecat:CultureEvent');
        self::assertCount(1, $ancestor, 'The ancestor must exist before its relations mean anything.');
        self::assertCount(1, $named);

        self::assertSame(
            1,
            $this->countCategoryRelations($named[0]['uid']),
            'The type the payload names is related.'
        );
        self::assertSame(
            0,
            $this->countCategoryRelations($ancestor[0]['uid']),
            'Its ancestor organises the tree without being a relation.'
        );
    }

    #[Test]
    public function createsNoCategoryForACutOffClass(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventCategoryImportPreState.php');
        $this->expectFetch('e_19542-hubev.json');

        $this->runImport();

        self::assertSame(
            [],
            $this->fetchCategoriesByRemoteId('type:schema:Thing'),
            'Every record is a Thing, so a category for it says nothing.'
        );
    }

    #[Test]
    public function hangsTheTopmostAncestorOffTheConfiguredParent(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventCategoryImportPreState.php');
        $this->expectFetch('e_19542-hubev.json');

        $this->runImport();

        $ancestor = $this->fetchCategoriesByRemoteId('type:schema:Event');
        self::assertCount(1, $ancestor);

        self::assertSame(100, $ancestor[0]['parent'], 'The chain hangs off the anchor, not off nothing.');
    }

    #[Test]
    public function dropsTheRelationToATypeUpstreamNoLongerNames(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventCategoryDroppedTypePreState.php');
        $this->expectFetch('e_19542-hubev.json');

        $this->runImport();

        self::assertSame(
            0,
            $this->countCategoryRelations(102),
            'A type the payload no longer names loses its relation.'
        );
        self::assertSame(
            1,
            $this->countCategoryRelations(101),
            'The type still named keeps its relation.'
        );
    }

    #[Test]
    public function keepsTheCategoryRecordAfterItsRelationIsDropped(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventCategoryDroppedTypePreState.php');
        $this->expectFetch('e_19542-hubev.json');

        $this->runImport();

        self::assertCount(
            1,
            $this->fetchCategoriesByRemoteId('type:schema:MusicEvent'),
            'Only the relation goes; editors may still be using the category.'
        );
    }

    // The importer replaces the whole relation list, so anything it does not
    // submit is removed — including what an editor put there by hand.
    #[Test]
    public function keepsACategoryAnEditorAddedToAnImportedRecord(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventCategoryDroppedTypePreState.php');
        $this->expectFetch('e_19542-hubev.json');

        $this->runImport();

        self::assertSame(
            1,
            $this->countCategoryRelations(103),
            'A category with no remote_id is outside the importer\'s reach.'
        );
    }

    // A root that never parsed submits no relation list, so nothing of its
    // records is replaced. Pinned because reconciliation removes by omission:
    // an empty submission would look exactly like "upstream dropped everything".
    #[Test]
    public function keepsRelationsOfARecordWhoseRootFailedToFetch(): void
    {
        $this->expectErrors = true;
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventCategoryDroppedTypePreState.php');
        // One per attempt: a 500 is retried, and maxAttempts defaults to 3.
        $this->expectFailure('e_19542-hubev', 500);
        $this->expectFailure('e_19542-hubev', 500);
        $this->expectFailure('e_19542-hubev', 500);

        $this->runImport();

        self::assertSame(
            1,
            $this->countCategoryRelations(101),
            'A failed fetch must not be read as an upstream removal.'
        );
        self::assertSame(
            1,
            $this->countCategoryRelations(102),
            'Not even the relation a healthy run would have dropped.'
        );
        self::assertSame(
            1,
            $this->countCategoryRelations(103),
            'The editor\'s category is untouched either way.'
        );
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
    /**
     * Titles of the translations of one category, in a given language.
     *
     * @return list<string>
     */
    private function fetchCategoryTranslations(string $remoteId, int $languageUid): array
    {
        $parents = $this->fetchCategoriesByRemoteId($remoteId);
        if ($parents === []) {
            return [];
        }

        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('sys_category');
        $queryBuilder->getRestrictions()->removeAll();
        $rows = $queryBuilder->select('title')
            ->from('sys_category')
            ->where(
                $queryBuilder->expr()->eq('l10n_parent', $queryBuilder->createNamedParameter($parents[0]['uid'], Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter($languageUid, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT))
            )
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative()
        ;

        return array_values(array_map(
            static fn (array $r): string => is_string($r['title']) ? $r['title'] : '',
            $rows
        ));
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
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
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

    /**
     * Whether the category sits anywhere beneath the anchor. With a hierarchy
     * an imported category may hang off an ancestor rather than off the anchor
     * itself, so a direct-parent check would only ever have described a flat
     * tree.
     */
    private function isInRootlineOf(int $categoryUid, int $anchorUid): bool
    {
        $seen = [];
        $current = $categoryUid;

        while ($current > 0 && !isset($seen[$current])) {
            $seen[$current] = true;
            $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('sys_category');
            $queryBuilder->getRestrictions()->removeAll();
            $parent = $queryBuilder->select('parent')
                ->from('sys_category')
                ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($current, Connection::PARAM_INT)))
                ->executeQuery()
                ->fetchOne()
            ;
            $current = is_numeric($parent) ? (int)$parent : 0;

            if ($current === $anchorUid) {
                return true;
            }
        }

        return false;
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
