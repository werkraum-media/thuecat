<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLog;
use WerkraumMedia\ThueCat\Domain\Repository\Backend\ImportLogRepository;
use WerkraumMedia\ThueCat\Import\FetchFailureVerdict;
use WerkraumMedia\ThueCat\Import\Importer\FetchData;
use WerkraumMedia\ThueCat\Import\Importer\FetchData\ResourceNotFoundException;
use WerkraumMedia\ThueCat\Import\ImportLogger;
use WerkraumMedia\ThueCat\Import\MediaFileDownloader;
use WerkraumMedia\ThueCat\Import\Parser\Entity\Events\Support\StaleDateReaper;
use WerkraumMedia\ThueCat\Import\Parser\Entity\Support\MediaFieldMap;
use WerkraumMedia\ThueCat\Import\Parser\Parser;
use WerkraumMedia\ThueCat\Import\Repositories\SysCategoryRepository;
use WerkraumMedia\ThueCat\Import\Resolver;
use WerkraumMedia\ThueCat\Import\SysCategory\ChainBuilder;
use WerkraumMedia\ThueCat\Import\SysCategory\ParentStrategies;
use WerkraumMedia\ThueCat\Import\SysCategory\SysCategoryProvisioner;
use WerkraumMedia\ThueCat\Import\SysCategory\TitleResolver;
use WerkraumMedia\ThueCat\Import\Vocabulary\VocabularyProvider;

class ImporterTest extends AbstractImportTestCase
{
    #[Test]
    public function importsFreshOrganization(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsFreshOrganization.php');
        $this->expectFetch('018132452787-ngbe.json');

        $this->importConfiguration(1);

        $this->assertPHPDataSet(__DIR__ . '/Assertions/Import/ImportsFreshOrganization.php');
    }

    #[Test]
    public function importsTown(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsTown.php');
        $this->expectFetch('043064193523-jcyt.json');
        $this->expectFetch('018132452787-ngbe.json');

        $this->importConfiguration(1);

        $this->assertPHPDataSet(__DIR__ . '/Assertions/Import/ImportsTown.php');
    }

    #[Test]
    public function importsTownWithRelation(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsTownWithRelation.php');
        // Pre-seeded org row is stale ("Old title"); per the STATUS_FOUND
        // contract the resolver must refresh it before the FK is wired,
        // so the org URL is fetched too even though its uid already exists.
        $this->expectFetch('043064193523-jcyt.json');
        $this->expectFetch('018132452787-ngbe.json');

        $this->importConfiguration(1);

        $this->assertPHPDataSet(__DIR__ . '/Assertions/Import/ImportsTownWithRelation.php');
    }

    #[Test]
    public function importsTownWhenManagedByReferenceIsMissing(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsTownWithMissingRelation.php');
        $this->expectFetch('043064193523-jcyt.json');
        $this->expectNotFound('018132452787-ngbe');

        $this->importConfiguration(1);

        // The town is the point: a dead managedBy reference must cost the
        // relation, not the record that holds it.
        $this->assertPHPDataSet(__DIR__ . '/Assertions/Import/ImportsTownWithMissingRelation.php');
    }

    #[Test]
    public function importsTownWhenManagedByReferenceFailsWithServerError(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsTownWithMissingRelation.php');
        $this->expectFetch('043064193523-jcyt.json');
        $this->expectFailure('018132452787-ngbe', 500, 'Internal Server Error');

        $this->importConfiguration(1);

        $this->assertPHPDataSet(__DIR__ . '/Assertions/Import/ImportsTownWithMissingRelation.php');
    }

    #[Test]
    public function importsTownWhenManagedByReferenceReturnsNonJson(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsTownWithMissingRelation.php');
        $this->expectFetch('043064193523-jcyt.json');
        // 200 with an HTML error page — decoding throws, not the HTTP layer.
        $this->expectFailure('018132452787-ngbe', 200, '<html><body>Gateway</body></html>');

        $this->importConfiguration(1);

        $this->assertPHPDataSet(__DIR__ . '/Assertions/Import/ImportsTownWithMissingRelation.php');
    }

    #[Test]
    public function importsTownWhenManagedByReferenceReturnsEmptyGraph(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsTownWithMissingRelation.php');
        $this->expectFetch('043064193523-jcyt.json');
        $this->expectFailure('018132452787-ngbe', 200, '{"@graph": []}');

        $this->importConfiguration(1);

        $this->assertPHPDataSet(__DIR__ . '/Assertions/Import/ImportsTownWithMissingRelation.php');
    }

    #[Test]
    public function doesNotCarryARelationOverToARecordThatDeclaresNone(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsTwoAttractionsOnlyOneWithTown.php');
        $this->expectFetch('attraction-with-town.json');
        $this->expectFetch('attraction-without-town.json');
        $this->expectFetch('043064193523-jcyt.json');

        $this->importConfiguration(1);

        // Shared entity instances: an unfilled bucket keeps the previous
        // record's. Not media-specific.
        self::assertSame(
            [
                'https://thuecat.org/resources/attraction-with-town' => 1,
                'https://thuecat.org/resources/attraction-without-town' => 0,
            ],
            $this->fetchTownByAttractionRemoteId()
        );
    }

    /**
     * Town uid per attraction, 0 when unset.
     *
     * @return array<string, int>
     */
    private function fetchTownByAttractionRemoteId(): array
    {
        $queryBuilder = $this->get(ConnectionPool::class)->getQueryBuilderForTable('tx_thuecat_tourist_attraction');
        $queryBuilder->getRestrictions()->removeAll();
        $rows = $queryBuilder
            ->select('remote_id', 'town')
            ->from('tx_thuecat_tourist_attraction')
            ->orderBy('uid', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative()
        ;

        $towns = [];
        foreach ($rows as $row) {
            if (!is_string($row['remote_id'])) {
                continue;
            }
            $towns[$row['remote_id']] = is_numeric($row['town']) ? (int)$row['town'] : 0;
        }

        return $towns;
    }

    #[Test]
    public function importsTouristInformationWithRelation(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsTouristInformationWithRelation.php');
        // Importer fetches the info first, then drains containedInPlace and
        // managedBy. Order doesn't matter to the faker — it only checks each
        // URL is fetched exactly the declared number of times.
        $this->expectFetch('333039283321-xxwg.json');
        // The info's schema:photo/image reference this media node; the
        // resolver still fetches it during the media drain even though the
        // stubbed downloader skips the actual file download + storage.
        $this->expectFetch('dms_5162598.json');
        $this->expectFetch('043064193523-jcyt.json');
        $this->expectFetch('573211638937-gmqb.json');
        $this->expectFetch('e_108867196-oatour.json');
        $this->expectFetch('e_1492818-oatour.json');
        $this->expectFetch('e_16571065-oatour.json');
        $this->expectFetch('e_16659193-oatour.json');
        $this->expectFetch('e_18179059-oatour.json');
        $this->expectFetch('e_18429754-oatour.json');
        $this->expectFetch('e_18429974-oatour.json');
        $this->expectFetch('e_18550292-oatour.json');
        $this->expectFetch('e_21827958-oatour.json');
        $this->expectFetch('e_39285647-oatour.json');
        $this->expectFetch('e_52469786-oatour.json');
        $this->expectFetch('356133173991-cryw.json');
        $this->expectFetch('018132452787-ngbe.json');

        $this->importConfiguration(1);

        $this->assertPHPDataSet(__DIR__ . '/Assertions/Import/ImportsTouristInformationWithRelation.php');
    }

    #[Test]
    public function importsTouristAttractionWithSingleSlogan(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsTouristAttractionWithSingleSlogan.php');
        $this->expectFetch('attraction-with-single-slogan.json');
        $this->expectFetch('018132452787-ngbe.json');

        $this->importConfiguration(1);

        $this->assertPHPDataSet(__DIR__ . '/Assertions/Import/ImportsTouristAttractionWithSingleSlogan.php');
    }

    #[Test]
    public function importsTouristAttractionWithCategories(): void
    {
        // Two @types map to categories (Museum, Synagogue), the rest are ignored
        // structural types. Both categories are created under the configured
        // parent and wired via sys_category_record_mm.
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsTouristAttractionWithCategories.php');
        $this->expectFetch('attraction-with-category.json');

        $this->importConfiguration(1);

        $this->assertPHPDataSet(__DIR__ . '/Assertions/Import/ImportsTouristAttractionWithCategories.php');
    }

    /**
     * An outer relation on an earlier root points at a record that is a root
     * itself later in the same run. Resolving that relation fetches and stages
     * the record at depth 1; its own root turn then finds it already updated
     * and drops the row, taking the staged category bucket with it. The record
     * must keep the categories its @types map to, whichever sighting wires
     * them. Regression guard for #13027.
     */
    #[Test]
    public function importsCategoriesForAttractionSightedAsRelationBeforeItsOwnRoot(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsAttractionSightedAsRelationFirst.php');
        $this->expectFetch('attraction-referencing-later-root.json');
        // Fetched once: resolving the relation marks it updated, so its own
        // root turn short-circuits before fetching again.
        $this->expectFetch('attraction-sighted-as-relation-first.json');

        $this->importConfiguration(1);

        self::assertSame(
            ['Bürgerpark', 'Park', 'Öffentlicher Park'],
            $this->fetchCategoryTitlesOf('https://thuecat.org/resources/attraction-sighted-as-relation-first'),
            'The relation-first attraction lost the categories its @types map to.'
        );
    }

    #[Test]
    public function importsTouristAttractionWithSloganArray(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsTouristAttractionWithSloganArray.php');
        $this->expectFetch('attraction-with-slogan-array.json');
        $this->expectFetch('018132452787-ngbe.json');

        $this->importConfiguration(1);

        $this->assertPHPDataSet(__DIR__ . '/Assertions/Import/ImportsTouristAttractionWithSloganArray.php');
    }

    /**
     * Visit-once contract: two attraction roots in one configuration both
     * reference the same managedBy organization. The org URL is staged
     * exactly once. Under the URL-keyed faker, a re-fetch surfaces as an
     * "unexpected request" error (the bag for that URL is empty on the
     * second attempt) — which is the only way the resolve-once short-circuit
     * (ResolverContext::isUpdated) is exercised by the suite.
     */
    #[Test]
    public function importsTwoAttractionsSharingOrgFetchesOrgOnce(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsTwoAttractionsSharingOrg.php');
        // Three URLs in total; the org appears exactly once. If isUpdated
        // ever regresses, the second managedBy resolution will trip the
        // empty-bag error for the org URL.
        $this->expectFetch('attraction-with-single-slogan.json');
        $this->expectFetch('018132452787-ngbe.json');
        $this->expectFetch('attraction-with-slogan-array.json');

        $this->importConfiguration(1);

        $this->assertPHPDataSet(__DIR__ . '/Assertions/Import/ImportsTwoAttractionsSharingOrg.php');
    }

    /**
     * Regression test for the production defect: two roots referenced one
     * dead resource and BOTH roots were discarded, twice over.
     *
     * The 404 is staged exactly once. A second fetch of a URL already known
     * to be missing would drain the faker's bag and fail the test — which is
     * what proves the dedup suppresses re-fetching across roots.
     */
    #[Test]
    public function importsTwoAttractionsWhenSharedOrgIsMissing(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsTwoAttractionsSharingOrg.php');
        $this->expectFetch('attraction-with-single-slogan.json');
        $this->expectFetch('attraction-with-slogan-array.json');
        $this->expectNotFound('018132452787-ngbe');

        $this->importConfiguration(1);

        $this->assertPHPDataSet(__DIR__ . '/Assertions/Import/ImportsTwoAttractionsSharingMissingOrg.php');
    }

    #[Test]
    public function runWithOnlySkippedReferencesReportsWarning(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsTownWithMissingRelation.php');
        $this->expectFetch('043064193523-jcyt.json');
        $this->expectNotFound('018132452787-ngbe');

        $severity = $this->importConfigurationReturningSeverity(1);

        self::assertSame(
            'warning',
            $severity,
            'A vanished upstream reference is data drift, not an operator error. '
            . 'Anything at error or above makes the command exit non-zero and any '
            . 'scheduler treat a healthy import as broken.'
        );
    }

    /**
     * Outer-call failures are outages, not drift — the warning treatment
     * stops at the reference seam.
     */
    #[Test]
    public function failingRootFetchStillReportsError(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsTownWithMissingRelation.php');
        $this->expectNotFound('043064193523-jcyt');

        $severity = $this->importConfigurationReturningSeverity(1);

        self::assertSame(
            'error',
            $severity,
            'A root that cannot be fetched is an outage. If this ever reports warning, '
            . 'a scheduler would treat a run that imported nothing as healthy.'
        );
    }

    #[Test]
    public function failingUrlProviderFetchFailsTheRun(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsSyncScope.php');
        GuzzleClientFaker::expectNotFoundForUrl(
            'https://cdb.thuecat.org/api/ext-sync/get-updated-nodes?syncScopeId=dd4615dc-58a6-4648-a7ce-4950293a06db&showTotal=true'
        );

        $this->expectException(ResourceNotFoundException::class);

        $this->importConfigurationReturningSeverity(1);
    }

    #[Test]
    public function programmingErrorWhileResolvingIsNotSwallowed(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsTownWithRelation.php');
        $this->expectFetch('043064193523-jcyt.json');
        $this->expectFetch('018132452787-ngbe.json');
        // @phpstan-ignore method.notFound (functional test container is the Symfony Container, which has set())
        $this->getContainer()->set(Resolver::class, $this->buildResolverThrowingError());

        $severity = $this->importConfigurationReturningSeverity(1);

        self::assertSame(
            'error',
            $severity,
            'An Error is our own defect, not upstream drift. Demoting it to a skipped '
            . 'reference would hide our bugs behind a run that reports success.'
        );
    }

    #[Test]
    public function runWithSkippedReferenceAndMappingErrorReportsError(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsWithSkipAndMappingError.php');
        $this->expectFetch('unfetchable-reference.json');
        $this->expectFetch('043064193523-jcyt.json');
        $this->expectNotFound('018132452787-ngbe');

        $severity = $this->importConfigurationReturningSeverity(1);

        self::assertSame(
            'error',
            $severity,
            'A skipped reference must not mask a real failure: the run carries the '
            . 'highest severity seen, so the mapping error still decides the exit code.'
        );
    }

    /**
     * The log must name each affected record, not just the dead URL — the
     * production log left table_name and remote_id empty, so nothing said
     * which record to fix upstream.
     */
    #[Test]
    public function logsSkippedReferenceOncePerOwningParent(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsTwoAttractionsSharingOrg.php');
        $this->expectFetch('attraction-with-single-slogan.json');
        $this->expectFetch('attraction-with-slogan-array.json');
        $this->expectNotFound('018132452787-ngbe');

        $this->importConfiguration(1);

        $this->assertPHPDataSet(__DIR__ . '/Assertions/Import/LogsSkippedReferencePerParent.php');
    }

    /**
     * Re-staging short-circuit: two URLs in one configuration return JSON
     * payloads that share a remote_id but carry different scalar fields.
     * URL 1 stages the row; URL 2's parse hits the resolver's rekey pass
     * where ResolverContext::isUpdated drops it (Resolver.php line 211).
     * If isUpdated regresses to a no-op, URL 2's row reuses the same NEW…
     * key (line 220) and overwrites URL 1's title in the dataMap before
     * DataHandler runs — the DB ends up with "Second parse should be
     * dropped" and the assertion fails.
     *
     * This is what `importsTwoAttractionsSharingOrgFetchesOrgOnce` cannot
     * verify: that test's visit-once guarantee is supplied by the
     * remoteIdToKey payload cache (Resolver.php line 440), which fires
     * before isUpdated has a chance to.
     */
    #[Test]
    public function importsSameAttractionTwiceKeepsFirstParse(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsSameAttractionTwice.php');
        $this->expectFetch('attraction-duplicate-first.json');
        $this->expectFetch('attraction-duplicate-second.json');

        $this->importConfiguration(1);

        $this->assertPHPDataSet(__DIR__ . '/Assertions/Import/ImportsSameAttractionTwice.php');
    }

    // Each site language gets its own address row, from the source's @language tags.
    #[Test]
    public function importsAddressTranslations(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsAddressTranslations.php');
        $this->expectFetch('900000000001-goet.json');

        $this->importConfiguration(1);

        $this->assertPHPDataSet(__DIR__ . '/Assertions/Import/ImportsAddressTranslations.php');
    }

    /**
     * DataHandler appends inline children, so a re-import must match on the
     * derived remote_id to update rows in place rather than stacking them.
     */
    #[Test]
    public function reimportingKeepsOneAddressRowPerParentPerLanguage(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ReimportAddressTranslations.php');
        $this->expectFetch('900000000001-goet.json');

        $this->importConfiguration(1);

        $this->assertPHPDataSet(__DIR__ . '/Assertions/Import/ImportsAddressTranslations.php');
    }

    #[Test]
    public function importsTouristAttractionsWithSpecialOpeningHours(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsTouristAttractionWithSpecialOpeningHours.php');
        $this->expectFetch('special-opening-hours.json');
        $this->expectFetch('018132452787-ngbe.json');

        $this->importConfiguration(1);

        $this->assertPHPDataSet(__DIR__ . '/Assertions/Import/ImportsTouristAttractionsWithSpecialOpeningHours.php');
    }

    /**
     * The same parking facility is referenced by two roots via
     * parkingFacilityNearBy, so it is sighted twice in one run. Its opening
     * hours are manufactured as inline children of the parking row; on the
     * second sighting the resolve-once short-circuit drops the already-staged
     * child rows along with their pending parent-wiring transient, leaving
     * the OH orphaned (parentid=0). Every OH row must instead wire to the
     * parking parent. Regression guard for #10902.
     */
    #[Test]
    public function importsOpeningHoursForParkingFacilityReferencedByTwoRoots(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsContainedParkingWithOpeningHours.php');
        $this->expectFetch('attraction-with-parking-nearby.json');
        $this->expectFetch('second-attraction-with-parking-nearby.json');
        $this->expectFetch('018132452787-ngbe.json');
        // Referenced by both roots but fetched once (resolve-once contract).
        $this->expectFetch('396420044896-drzt.json');
        // A sibling parking on the first root, fetched after drzt — its merge
        // re-rekeys the payload and drops drzt's not-yet-wired OH children.
        $this->expectFetch('000000000001-scnd.json');
        // drzt keeps its media so the functional and unit copies of the record
        // stay identical; reached through a relation, so it drains now.
        $this->expectFetch('dms_6486108.json');

        $this->importConfiguration(1);

        /** @var list<array{uid: string, parentid: string, parenttable: string}> $openingHours */
        $openingHours = $this->getAllRecords('tx_thuecat_opening_hours');
        self::assertNotEmpty($openingHours, 'No opening hours imported for the contained parking facility.');

        /** @var list<array{uid: string}> $parkingFacilities */
        $parkingFacilities = $this->getAllRecords('tx_thuecat_parking_facility');
        $parkingUids = array_map(static fn (array $row): string => (string)$row['uid'], $parkingFacilities);

        foreach ($openingHours as $row) {
            self::assertNotSame('0', (string)$row['parentid'], 'Opening hours row ' . $row['uid'] . ' is orphaned (parentid=0).');
            self::assertSame('tx_thuecat_parking_facility', $row['parenttable'], 'Opening hours row ' . $row['uid'] . ' has the wrong parenttable.');
            self::assertContains((string)$row['parentid'], $parkingUids, 'Opening hours row ' . $row['uid'] . ' points at a non-existent parking facility.');
        }
    }

    #[Test]
    public function importsTouristAttractionWithAccessibilitySpecification(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsTouristAttractionWithAccessibilitySpecification.php');
        $this->expectFetch('attraction-with-accessibility-specification.json');
        $this->expectFetch('018132452787-ngbe.json');
        $this->expectFetch('e_331baf4eeda4453db920dde62f7e6edc-rfa-accessibility-specification.json');

        $this->importConfiguration(1);

        $this->assertPHPDataSet(__DIR__ . '/Assertions/Import/ImportsTouristAttractionWithAccessibilitySpecification.php');
        /** @var list<array{accessibility_specification: string}> $records */
        $records = $this->getAllRecords('tx_thuecat_tourist_attraction');
        self::assertStringEqualsFile(__DIR__ . '/Fixtures/Import/ImportsTouristAttractionWithAccessibilitySpecificationGerman.txt', $records[0]['accessibility_specification'] . PHP_EOL);
        self::assertStringEqualsFile(__DIR__ . '/Fixtures/Import/ImportsTouristAttractionWithAccessibilitySpecificationEnglish.txt', $records[1]['accessibility_specification'] . PHP_EOL);
    }

    /**
     * An outer relation on an earlier root points at a record that is a root
     * itself later in the same run, so it enters the payload at depth 1. The
     * generic fetch cap then drops its accessibility reference without
     * fetching, and without logging. Shaping the blob stages no rows and
     * follows no references, so the cap must not apply to it.
     * Regression guard for #13027.
     */
    #[Test]
    public function shapesAccessibilityOfAttractionSightedAsRelationBeforeItsOwnRoot(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsAccessibilityForAttractionSightedAsRelationFirst.php');
        $this->expectFetch('attraction-referencing-accessibility-root.json');
        // Fetched once: resolving the relation marks it updated, so its own
        // root turn short-circuits before fetching again.
        $this->expectFetch('attraction-with-accessibility-sighted-as-relation-first.json');
        $this->expectFetch('e_331baf4eeda4453db920dde62f7e6edc-rfa-accessibility-specification.json');

        $this->importConfiguration(1);

        self::assertNotSame(
            '',
            $this->fetchAccessibilitySpecificationOf(
                'https://thuecat.org/resources/attraction-with-accessibility-sighted-as-relation-first'
            ),
            'The relation-first attraction lost its accessibility specification.'
        );
    }

    /**
     * containedInPlace routinely carries POIs alongside towns. A POI now has a
     * field to land on, so it relates instead of being reported. A reference no
     * entity handles produced no record at all: there is no relation to drop,
     * so it stays silent.
     *
     * The silent case uses a type that is unmappable by construction — a
     * `test:` namespace no entity handles now or later. Real unmapped types
     * (trails, at the time of writing) eventually get a model, and the day
     * they do this test would silently stop proving anything.
     *
     * Regression guard for #13027.
     */
    #[Test]
    public function containedInPlaceRelatesPoisAndStaysSilentOnReferencesThatImportedNothing(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsAttractionContainedInAPoi.php');
        $this->expectFetch('attraction-contained-in-a-poi.json');
        $this->expectFetch('a-park-not-a-town.json');
        // No entity handles test:NeverModelled, so it imports nothing.
        $this->expectFetch('never-modelled-place.json');

        $this->importConfiguration(1);

        self::assertSame(
            [],
            $this->getLogEntriesOfType('referenceUnrelatable'),
            'The POI relates now, and the unmodelled reference never produced a record.'
        );
        self::assertCount(0, $this->fetchImportLog()->getUnrelatableReferences());

        // The park imported as an attraction and landed on contained_in_attraction.
        $parkUid = $this->fetchUidByRemoteId(
            'tx_thuecat_tourist_attraction',
            'https://thuecat.org/resources/a-park-not-a-town'
        );
        self::assertGreaterThan(0, $parkUid, 'The park imported as an attraction.');
        self::assertSame(
            (string)$parkUid,
            $this->fetchContainedInAttractionOf('https://thuecat.org/resources/attraction-contained-in-a-poi')
        );

        self::assertSame([], $this->getLogEntriesOfType('mappingError'));
    }

    private function fetchImportLog(): ImportLog
    {
        $logs = $this->get(ImportLogRepository::class)->findAll();
        $log = $logs->getFirst();
        self::assertInstanceOf(ImportLog::class, $log);

        return $log;
    }

    private function fetchUidByRemoteId(string $table, string $remoteId): int
    {
        $queryBuilder = $this->get(ConnectionPool::class)->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll();

        $value = $queryBuilder
            ->select('uid')
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq(
                    'remote_id',
                    $queryBuilder->createNamedParameter($remoteId)
                )
            )
            ->executeQuery()
            ->fetchOne()
        ;

        return is_numeric($value) ? (int)$value : 0;
    }

    private function fetchContainedInAttractionOf(string $remoteId): string
    {
        $queryBuilder = $this->get(ConnectionPool::class)->getQueryBuilderForTable('tx_thuecat_tourist_attraction');
        $queryBuilder->getRestrictions()->removeAll();

        $value = $queryBuilder
            ->select('contained_in_attraction')
            ->from('tx_thuecat_tourist_attraction')
            ->where(
                $queryBuilder->expr()->eq(
                    'remote_id',
                    $queryBuilder->createNamedParameter($remoteId)
                )
            )
            ->executeQuery()
            ->fetchOne()
        ;

        return is_string($value) ? $value : '';
    }

    private function fetchAccessibilitySpecificationOf(string $remoteId): string
    {
        $queryBuilder = $this->get(ConnectionPool::class)->getQueryBuilderForTable('tx_thuecat_tourist_attraction');
        $queryBuilder->getRestrictions()->removeAll();

        $value = $queryBuilder
            ->select('accessibility_specification')
            ->from('tx_thuecat_tourist_attraction')
            ->where(
                $queryBuilder->expr()->eq(
                    'remote_id',
                    $queryBuilder->createNamedParameter($remoteId)
                )
            )
            ->executeQuery()
            ->fetchOne()
        ;

        return is_string($value) ? $value : '';
    }

    #[Test]
    public function importsBasedOnSyncScope(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsSyncScope.php');
        // SyncScopeUrlProvider first hits the get-updated-nodes endpoint to
        // collect the URL list, then the importer fetches each in turn.
        // Order doesn't matter — every URL must be fetched exactly the
        // declared number of times.
        $this->expectFetchForUrl(
            'https://cdb.thuecat.org/api/ext-sync/get-updated-nodes?syncScopeId=dd4615dc-58a6-4648-a7ce-4950293a06db&showTotal=true',
            'cdb.thuecat.org/api/ext-sync/get-updated-nodes/dd4615dc-58a6-4648-a7ce-4950293a06db.json'
        );
        // Three roots from get-updated-nodes: dara, zmqf, yyno. Each is
        // depth 0; their direct references resolve at depth 1; anything
        // beyond is depth-capped (ResolverContext::MAX_FETCH_DEPTH = 1)
        // and the bucket is dropped without a fetch. The pre-seeded Town
        // 043064193523-jcyt is referenced from a depth-0 root, so the
        // STATUS_FOUND contract refreshes it via HTTP — its existing uid
        // is reused, the row's fields are overwritten with the fetched
        // payload.
        $this->expectFetch('835224016581-dara.json');
        $this->expectFetch('018132452787-ngbe.json');
        $this->expectFetch('573211638937-gmqb.json');
        $this->expectFetch('508431710173-wwne.json');
        $this->expectFetch('dms_5159216.json');
        $this->expectFetch('dms_5159186.json');
        $this->expectFetch('396420044896-drzt.json');
        $this->expectFetch('165868194223-zmqf.json');
        $this->expectFetch('497839263245-edbm.json');
        $this->expectFetch('dms_5099196.json');
        $this->expectFetch('e_23bec7f80c864c358da033dd75328f27-rfa.json');
        $this->expectFetch('215230952334-yyno.json');
        $this->expectFetch('052821473718-oxfq.json');
        $this->expectFetch('dms_134362.json');
        $this->expectFetch('dms_134288.json');
        $this->expectFetch('dms_652340.json');
        $this->expectFetch('440055527204-ocar.json');
        // Pre-seeded town referenced from a depth-0 root — refreshed via
        // HTTP under the STATUS_FOUND contract (see comment above).
        $this->expectFetch('043064193523-jcyt.json');
        // Resolver follows references in the dara graph to resources that
        // don't exist upstream and 404 in production.
        $this->expectNotFound('dms_5713563');
        // drzt's asset, reached through a relation — drains now that media is
        // exempt from the fetch depth cap.
        $this->expectFetch('dms_6486108.json');

        $this->importConfiguration(1);

        $this->assertPHPDataSet(__DIR__ . '/Assertions/Import/ImportsSyncScope.php');
    }

    #[Test]
    public function mappingErrorNamesTheRootUrlItWasProcessing(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsWithSkipAndMappingError.php');
        $this->expectFetch('unfetchable-reference.json');
        $this->expectFetch('043064193523-jcyt.json');
        $this->expectNotFound('018132452787-ngbe');

        $this->importConfigurationReturningSeverity(1);

        $entries = $this->getLogEntriesOfType('mappingError');

        self::assertCount(1, $entries);
        self::assertSame(
            'https://thuecat.org/resources/unfetchable-reference',
            $entries[0]['remote_id']
        );
    }

    #[Test]
    public function mappingErrorRetainsExceptionDetailAndSeverity(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsWithSkipAndMappingError.php');
        $this->expectFetch('unfetchable-reference.json');
        $this->expectFetch('043064193523-jcyt.json');
        $this->expectNotFound('018132452787-ngbe');

        $severity = $this->importConfigurationReturningSeverity(1);

        $entries = $this->getLogEntriesOfType('mappingError');

        self::assertSame('error', $severity);
        self::assertSame('error', $entries[0]['severity']);
        self::assertNotSame('', $entries[0]['message']);

        self::assertIsString($entries[0]['context']);
        $context = json_decode($entries[0]['context'], true);
        self::assertIsArray($context);
        self::assertArrayHasKey('file', $context);
        self::assertArrayHasKey('line', $context);
        self::assertSame(
            'https://thuecat.org/resources/unfetchable-reference',
            $context['url'] ?? null
        );
    }

    #[Test]
    public function fetchingErrorNamesTheRootUrlItWasProcessing(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsFreshOrganization.php');
        // 401 is what FetchData turns into an InvalidResponseException.
        $this->expectFailure('018132452787-ngbe', 401);

        $this->importConfigurationReturningSeverity(1);

        $entries = $this->getLogEntriesOfType('fetchingError');

        self::assertCount(1, $entries);
        self::assertSame(
            'https://thuecat.org/resources/018132452787-ngbe',
            $entries[0]['remote_id']
        );
    }

    /**
     * A root URL whose every attempt fails must be recorded and skipped like
     * any other fetch failure, not escape and abort the run.
     */
    #[Test]
    public function exhaustedRetryOnARootUrlIsRecordedAsAFetchingError(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsFreshOrganization.php');
        // One per attempt: maxAttempts defaults to 3.
        $this->expectFailure('018132452787-ngbe', 500);
        $this->expectFailure('018132452787-ngbe', 500);
        $this->expectFailure('018132452787-ngbe', 500);

        $this->importConfigurationReturningSeverity(1);

        $entries = $this->getLogEntriesOfType('fetchingError');

        self::assertCount(1, $entries);
        self::assertSame(
            'https://thuecat.org/resources/018132452787-ngbe',
            $entries[0]['remote_id']
        );
        self::assertSame('error', $entries[0]['severity']);

        self::assertIsString($entries[0]['context']);
        $context = json_decode($entries[0]['context'], true);
        self::assertIsArray($context);
        self::assertSame(3, $context['attempts'] ?? null, 'Attempt count is recorded.');
        self::assertSame('retryExhausted', $context['cause'] ?? null, 'Cause is machine-readable.');
    }

    /**
     * Category titles related to an attraction, by its remote_id, sorted so the
     * assertion does not depend on wiring order.
     *
     * @return list<string>
     */
    private function fetchCategoryTitlesOf(string $remoteId): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('sys_category');
        $queryBuilder->getRestrictions()->removeAll();

        $rows = $queryBuilder
            ->select('category.title')
            ->from('sys_category', 'category')
            ->join(
                'category',
                'sys_category_record_mm',
                'mm',
                $queryBuilder->expr()->eq('mm.uid_local', $queryBuilder->quoteIdentifier('category.uid'))
            )
            ->join(
                'mm',
                'tx_thuecat_tourist_attraction',
                'attraction',
                $queryBuilder->expr()->eq('mm.uid_foreign', $queryBuilder->quoteIdentifier('attraction.uid'))
            )
            ->where(
                $queryBuilder->expr()->eq(
                    'attraction.remote_id',
                    $queryBuilder->createNamedParameter($remoteId)
                ),
                $queryBuilder->expr()->eq(
                    'mm.tablenames',
                    $queryBuilder->createNamedParameter('tx_thuecat_tourist_attraction')
                ),
                // The table shares sys_category_record_mm between its category
                // and keyword relations.
                $queryBuilder->expr()->eq(
                    'mm.fieldname',
                    $queryBuilder->createNamedParameter('categories')
                )
            )
            ->executeQuery()
            ->fetchAllAssociative()
        ;

        $titles = [];
        foreach ($rows as $row) {
            if (is_string($row['title'])) {
                $titles[] = $row['title'];
            }
        }
        sort($titles);

        return $titles;
    }

    private function buildResolverThrowingError(): ResolverThrowingErrorStub
    {
        return new ResolverThrowingErrorStub(
            $this->get(ConnectionPool::class),
            $this->get(FetchData::class),
            $this->get(Parser::class),
            $this->get(TcaSchemaFactory::class),
            $this->get(MediaFileDownloader::class),
            $this->get(SysCategoryRepository::class),
            $this->get(ImportLogger::class),
            $this->get(StaleDateReaper::class),
            $this->get(MediaFieldMap::class),
            $this->get(FetchFailureVerdict::class),
            $this->get(SysCategoryProvisioner::class),
            $this->get(ChainBuilder::class),
            $this->get(TitleResolver::class),
            $this->get(ParentStrategies::class),
            $this->get(VocabularyProvider::class),
        );
    }
}
