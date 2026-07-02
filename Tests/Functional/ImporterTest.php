<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use WerkraumMedia\ThueCat\Domain\Repository\Backend\ImportConfigurationRepository;
use WerkraumMedia\ThueCat\Import\Importer;

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

        $this->importConfiguration(1);

        $this->assertPHPDataSet(__DIR__ . '/Assertions/Import/ImportsSyncScope.php');
    }

    private function importConfiguration(int $uid): void
    {
        $this->workaroundExtbaseConfiguration();
        $configuration = $this->get(ImportConfigurationRepository::class)->findOneByUid($uid);
        self::assertNotNull($configuration, 'Fixture configuration uid=' . $uid . ' not found');
        $this->get(Importer::class)->importConfiguration($configuration);
    }
}
