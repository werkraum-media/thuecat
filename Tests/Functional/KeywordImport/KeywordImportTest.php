<?php

declare(strict_types=1);

/*
 * Copyright (C) 2026 werkraum-media
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 */

namespace WerkraumMedia\ThueCat\Tests\Functional\KeywordImport;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\Connection;
use WerkraumMedia\ThueCat\Domain\Repository\Backend\ImportConfigurationRepository;
use WerkraumMedia\ThueCat\Import\Importer;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractImportTestCase;

class KeywordImportTest extends AbstractImportTestCase
{
    protected array $testExtensionsToLoad = [
        'werkraummedia/thuecat/',
        'werkraummedia/events/',
    ];

    protected string $fixtureGuzzleBase = __DIR__ . '/Fixtures/Guzzle';

    #[Test]
    public function createsKeywordCategoryUnderTheKeywordAnchor(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/KeywordImportPreState.php');
        $this->expectKeywordFetches();

        $this->runImport();

        $created = $this->fetchCategoriesByRemoteId('keyword:https://thuecat.org/resources/475728955106-qdcc');
        self::assertCount(1, $created, 'Exactly one keyword category should be created.');
        self::assertSame('Landeshauptstadt Erfurt', $created[0]['title'], 'Seeded from the term label.');
        self::assertSame(30, $created[0]['pid'], 'Created at the configured keywordStoragePid.');
    }

    // The set sits between anchor and term; the term must not be a direct child.
    #[Test]
    public function nestsTheTermBeneathItsTermSet(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/KeywordImportPreState.php');
        $this->expectKeywordFetches();

        $this->runImport();

        $set = $this->fetchCategoriesByRemoteId('keyword:https://thuecat.org/resources/155933862969-mofh');
        $term = $this->fetchCategoriesByRemoteId('keyword:https://thuecat.org/resources/475728955106-qdcc');

        self::assertCount(1, $set, 'The term set becomes an intermediate category.');
        self::assertSame('Landkreise', $set[0]['title']);
        self::assertSame(200, $set[0]['parent'], 'The set hangs off the keyword anchor.');
        self::assertSame($set[0]['uid'], $term[0]['parent'], 'The term hangs off its set.');
    }

    // Live data never nests deeper than set -> term, so a fixed two-step walk
    // would pass every other test here. This chain is one level deeper.
    #[Test]
    public function buildsEveryIntermediateLevelOfADeeperChain(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/KeywordDeepNestingPreState.php');
        $this->expectFetch('deep-poi.json');
        $this->expectFetch('deep-term-leaf.json');
        $this->expectFetch('deep-set-mid.json');
        $this->expectFetch('deep-group-top.json');

        $this->runImport();

        $top = $this->fetchCategoriesByRemoteId('keyword:https://thuecat.org/resources/deep-group-top');
        $mid = $this->fetchCategoriesByRemoteId('keyword:https://thuecat.org/resources/deep-set-mid');
        $leaf = $this->fetchCategoriesByRemoteId('keyword:https://thuecat.org/resources/deep-term-leaf');

        self::assertCount(1, $top, 'The topmost group becomes a category.');
        self::assertCount(1, $mid, 'The intermediate set becomes a category.');
        self::assertCount(1, $leaf, 'The term itself becomes a category.');

        self::assertSame('Oberste Gruppe', $top[0]['title']);
        self::assertSame('Mittlere Gruppe', $mid[0]['title']);
        self::assertSame('Blattbegriff', $leaf[0]['title']);

        self::assertSame(200, $top[0]['parent'], 'The topmost group hangs off the keyword anchor.');
        self::assertSame($top[0]['uid'], $mid[0]['parent'], 'The set hangs off the group.');
        self::assertSame($mid[0]['uid'], $leaf[0]['parent'], 'The term hangs off the set.');
    }

    // DEFENSIVE: no mid-chain husk exists upstream. Both surveyed husks
    // (151338591378-xzxq, 446904873727-gnwe) are leaf references, so nothing
    // live is orphaned this way. Kept for the spec's "nearest usable ancestor".
    #[Test]
    public function attachesATermToTheNearestUsableAncestorAcrossAHusk(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/KeywordHuskPreState.php');
        $this->expectHuskFetches();

        $this->runImport();

        $top = $this->fetchCategoriesByRemoteId('keyword:https://thuecat.org/resources/husk-group-top');
        $leaf = $this->fetchCategoriesByRemoteId('keyword:https://thuecat.org/resources/husk-term-leaf');

        self::assertCount(1, $top, 'The usable group above the husk still becomes a category.');
        self::assertCount(1, $leaf, 'The term itself is unaffected by its unusable ancestor.');
        self::assertSame(
            $top[0]['uid'],
            $leaf[0]['parent'],
            'The term must climb past the husk to the nearest usable ancestor, not fall back to the anchor.'
        );
    }

    #[Test]
    public function createsNoCategoryForAnUnusableGroup(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/KeywordHuskPreState.php');
        $this->expectHuskFetches();

        $this->runImport();

        self::assertSame(
            [],
            $this->fetchCategoriesByRemoteId('keyword:https://thuecat.org/resources/husk-set-mid'),
            'A label-less group must never become a title-less category.'
        );
        self::assertSame(
            [],
            $this->fetchCategoriesByRemoteId('keyword:https://thuecat.org/resources/husk-standalone'),
            'A husk referenced directly by the record is skipped outright.'
        );
    }

    // A keyword is decoration; one bad vocabulary node may not cost the POI.
    #[Test]
    public function logsAWarningPerSkippedHuskAndStillImportsTheRecord(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/KeywordHuskPreState.php');
        $this->expectHuskFetches();

        $this->runImport();

        // Order follows the POI's keyword list: the referenced term is walked
        // first, so its husk ancestor precedes the standalone husk.
        self::assertSame(
            [
                [
                    'type' => 'referenceSkipped',
                    'severity' => 'warning',
                    'table_name' => 'sys_category',
                    'remote_id' => 'https://thuecat.org/resources/husk-set-mid',
                    'message' => 'Skipped reference "https://thuecat.org/resources/husk-set-mid"'
                        . ' for field "keywords": Keyword resource carries no usable label.',
                ],
                [
                    'type' => 'referenceSkipped',
                    'severity' => 'warning',
                    'table_name' => 'sys_category',
                    'remote_id' => 'https://thuecat.org/resources/husk-standalone',
                    'message' => 'Skipped reference "https://thuecat.org/resources/husk-standalone"'
                        . ' for field "keywords": Keyword resource carries no usable label.',
                ],
            ],
            $this->getSkippedReferenceLogEntries(),
            'Each unusable node is reported once, naming the resource.'
        );

        self::assertNotSame(
            [],
            $this->fetchRowsByRemoteId(
                'tx_thuecat_tourist_attraction',
                'https://thuecat.org/resources/husk-poi'
            ),
            'The owning record still imports.'
        );
    }

    /**
     * The enum a typed literal was used with must become a titled category of
     * its own. Nothing asserted this before, so the literal could sit directly
     * under the anchor and every other keyword test still passed.
     */
    #[Test]
    public function groupsAnOntologyLiteralUnderItsUsageTypeEnum(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/KeywordCollisionPreState.php');
        $this->expectFetch('collision-poi.json');
        $this->expectFetch('collision-term-museum.json');
        $this->expectFetch('collision-set.json');
        $this->expectFetchForUrl(
            'https://thuecat.org/ontology/thuecat/1.0/CollisionWeingut',
            'thuecat.org/ontology/thuecat/1.0/CollisionWeingut.json'
        );
        $this->expectFetchForUrl(
            'https://thuecat.org/ontology/thuecat/1.0/Ambiance',
            'thuecat.org/ontology/thuecat/1.0/Ambiance.json'
        );

        $this->runImport();

        $enum = $this->fetchCategoriesByRemoteId('keyword:https://thuecat.org/ontology/thuecat/1.0/Ambiance');
        $term = $this->fetchCategoriesByRemoteId('keyword:https://thuecat.org/ontology/thuecat/1.0/CollisionWeingut');

        self::assertCount(1, $enum, 'The usage-site enum becomes a category of its own.');
        self::assertSame('Ambiente', $enum[0]['title'], 'Titled from the enum\'s own upstream label, not the CURIE.');
        self::assertSame(
            200,
            $enum[0]['parent'],
            'The enum carries no term set, so the chain ends here and it sits on the anchor.'
        );

        self::assertCount(1, $term, 'The typed literal becomes a category.');
        self::assertSame(
            $enum[0]['uid'],
            $term[0]['parent'],
            'The literal must hang off its usage-site enum, not directly off the anchor.'
        );
    }

    // Before the keywords column existed, tableHasField() suppressed every
    // relation, so earlier tests only proved the CATEGORY was created.
    #[Test]
    public function relatesTheImportedKeywordsToTheAttraction(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/KeywordImportPreState.php');
        $this->expectKeywordFetches();

        $this->runImport();

        $term = $this->fetchCategoriesByRemoteId('keyword:https://thuecat.org/resources/475728955106-qdcc');
        $set = $this->fetchCategoriesByRemoteId('keyword:https://thuecat.org/resources/155933862969-mofh');

        // Each translation is its own record row with its own MM entry.
        $related = $this->fetchRelatedRecordLanguages(
            'tx_thuecat_tourist_attraction',
            $term[0]['uid'],
            'keywords'
        );
        self::assertSame(
            [0, 1, 2],
            $related,
            'The term must be related to the attraction in each language, once each.'
        );

        self::assertCount(1, $set, 'The term set still exists as a category.');
        self::assertSame(
            0,
            $this->countKeywordRelations('tx_thuecat_tourist_attraction', $set[0]['uid']),
            'Intermediate levels structure the tree; only the cited keyword is a relation.'
        );
    }

    #[Test]
    public function keywordRelationsUseTheKeywordFieldNotTheCategoryField(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/KeywordImportPreState.php');
        $this->expectKeywordFetches();

        $this->runImport();

        $term = $this->fetchCategoriesByRemoteId('keyword:https://thuecat.org/resources/475728955106-qdcc');

        self::assertSame(
            0,
            $this->countRelationsForField('tx_thuecat_tourist_attraction', $term[0]['uid'], 'categories'),
            'A keyword must never be written into the @type category relation.'
        );
    }

    // Out of scope here: these entities never call recordKeywords(), so nothing
    // is extracted. The absent term fetch proves the boundary holds at
    // extraction, not merely at the tableHasField guard downstream.
    #[Test]
    public function ignoresKeywordsOnOutOfScopeRecordTypes(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/KeywordOutOfScopePreState.php');
        $this->expectFetch('organisation-with-keyword.json');

        $this->runImport();

        self::assertSame(
            [],
            $this->fetchCategoriesByRemoteId('keyword:https://thuecat.org/resources/475728955106-qdcc'),
            'An out-of-scope type must not even create the keyword category.'
        );

        self::assertNotSame(
            [],
            $this->fetchRowsByRemoteId(
                'tx_thuecat_organisation',
                'https://thuecat.org/resources/organisation-with-keyword'
            ),
            'The organisation itself still imports.'
        );
    }

    #[Test]
    public function dropsRelationsToKeywordsUpstreamNoLongerProvides(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/KeywordReapPreState.php');
        $this->expectKeywordFetches();

        $this->runImport();

        self::assertSame(
            0,
            $this->countKeywordRelations('tx_thuecat_tourist_attraction', 202),
            'A keyword absent from the current payload must not stay related.'
        );
        self::assertSame(
            [0, 1, 2],
            $this->fetchRelatedRecordLanguages('tx_thuecat_tourist_attraction', 203, 'keywords'),
            'The keyword still provided keeps its relation, once per language.'
        );
    }

    // Editors may still use a keyword the import stopped delivering.
    #[Test]
    public function keepsTheCategoryRecordWhenItsLastRelationIsReaped(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/KeywordReapPreState.php');
        $this->expectKeywordFetches();

        $this->runImport();

        self::assertCount(
            1,
            $this->fetchCategoriesByRemoteId('keyword:https://thuecat.org/resources/stale-term'),
            'Reaping removes the relation, never the category record.'
        );
    }

    // Submitting the relation list is what removes, so a keyword that failed to
    // resolve is dropped unless its stored uids are carried forward. The second
    // keyword resolves, which is what brings the owner into the flush at all —
    // an owner collecting nothing never reaches it.
    #[Test]
    public function keepsStoredKeywordsWhenResolutionFailsTechnically(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/KeywordFailureGuardPreState.php');
        $this->expectFetch('guard-poi.json');
        $this->expectFetch('475728955106-qdcc.json');
        $this->expectFetch('155933862969-mofh.json');
        // One per attempt: maxAttempts defaults to 3.
        $this->expectFailure('guard-term-failing', 503, 'Service Unavailable');
        $this->expectFailure('guard-term-failing', 503, 'Service Unavailable');
        $this->expectFailure('guard-term-failing', 503, 'Service Unavailable');

        $this->runImport();

        self::assertNotSame(
            0,
            $this->countKeywordRelations('tx_thuecat_tourist_attraction', 202),
            'A term that failed to resolve keeps the relation it already had.'
        );
    }

    // 404 is upstream positively reporting the term absent, the one signal
    // allowed to cost a relation.
    #[Test]
    public function dropsStoredKeywordsWhenUpstreamReportsThemGone(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/KeywordFailureGuardPreState.php');
        $this->expectFetch('guard-poi.json');
        $this->expectFetch('475728955106-qdcc.json');
        $this->expectFetch('155933862969-mofh.json');
        $this->expectNotFound('guard-term-failing');

        $this->runImport();

        self::assertSame(
            0,
            $this->countKeywordRelations('tx_thuecat_tourist_attraction', 202),
            'A term upstream reports gone loses its relation.'
        );
    }

    /**
     * DOCUMENTS CURRENT BEHAVIOUR, not the desired end state. An owner that
     * collects nothing never enters the flush, so no list is submitted and its
     * relations survive. Backlog: "An owner that collects nothing is never
     * reaped" — a property of every collect-then-flush relation set, media
     * included, to be fixed pattern-wide rather than per feature.
     */
    #[Test]
    public function keepsRelationsWhenUpstreamDropsEveryKeyword(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/KeywordAllDroppedPreState.php');
        $this->expectFetch('poi-without-keywords.json');

        $this->runImport();

        self::assertNotSame(
            0,
            $this->countKeywordRelations('tx_thuecat_tourist_attraction', 203),
            'Not yet reaped: the owner collects nothing and never reaches the flush.'
        );

        self::assertNotSame(
            [],
            $this->fetchRowsByRemoteId(
                'tx_thuecat_tourist_attraction',
                'https://thuecat.org/resources/poi-without-keywords'
            ),
            'The record itself survives regardless.'
        );
    }

    // Reaping is per owner: a record this run never touched keeps its own
    // relations to a keyword another record dropped.
    #[Test]
    public function leavesOtherRecordsRelationsToTheSameKeywordUntouched(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/KeywordAllDroppedPreState.php');
        $this->expectFetch('poi-without-keywords.json');

        $this->runImport();

        self::assertSame(
            1,
            $this->countRelationsForOwner('tx_thuecat_tourist_attraction', 2, 'keywords'),
            'The untouched record keeps its relation to the shared keyword.'
        );
    }

    /**
     * The observed split — typed literals on places, free text on events —
     * describes today's data, not a contract. Both suites therefore exercise
     * every shape, so a shift upstream cannot silently break the untested one.
     */
    #[Test]
    public function relatesEveryKeywordShapeToThePlace(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/KeywordCollisionPreState.php');
        $this->expectFetch('collision-poi.json');
        $this->expectFetch('collision-term-museum.json');
        $this->expectFetch('collision-set.json');
        $this->expectFetchForUrl(
            'https://thuecat.org/ontology/thuecat/1.0/CollisionWeingut',
            'thuecat.org/ontology/thuecat/1.0/CollisionWeingut.json'
        );
        $this->expectFetchForUrl(
            'https://thuecat.org/ontology/thuecat/1.0/Ambiance',
            'thuecat.org/ontology/thuecat/1.0/Ambiance.json'
        );

        $this->runImport();

        foreach ([
            'reference' => 'keyword:https://thuecat.org/resources/collision-term-museum',
            'ontology literal' => 'keyword:https://thuecat.org/ontology/thuecat/1.0/CollisionWeingut',
            'free text' => 'keyword:text:museum',
        ] as $shape => $remoteId) {
            $rows = $this->fetchCategoriesByRemoteId($remoteId);
            self::assertCount(1, $rows, sprintf('Shape "%s" yields one category.', $shape));
            self::assertSame(
                1,
                $this->countKeywordRelations('tx_thuecat_tourist_attraction', $rows[0]['uid']),
                sprintf('Shape "%s" is related to the place.', $shape)
            );
        }
    }

    /**
     * Trimmed from the survey's densest root (15 keywords). Several terms share
     * one set, so the set must be created once however many members reach it.
     */
    #[Test]
    public function sharesOneSetCategoryAcrossItsMembers(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/KeywordManyPreState.php');
        $this->expectManyKeywordFetches();

        $this->runImport();

        $set = $this->fetchCategoriesByRemoteId('keyword:https://thuecat.org/resources/192875159827-xfqk');
        self::assertCount(1, $set, 'Three members reaching one set create it once.');

        foreach ([
            '023707063052-axjm',
            '569995942874-dnwg',
            '887654277691-eatw',
        ] as $term) {
            $rows = $this->fetchCategoriesByRemoteId('keyword:https://thuecat.org/resources/' . $term);
            self::assertCount(1, $rows, sprintf('Term %s becomes a category.', $term));
            self::assertSame($set[0]['uid'], $rows[0]['parent'], sprintf('Term %s hangs off the set.', $term));
        }
    }

    // Historic belongs to two enums upstream; only the @type at the usage site
    // says which one this usage meant.
    #[Test]
    public function takesTheParentEnumFromTheUsageSiteNotTheFetchedTerm(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/KeywordManyPreState.php');
        $this->expectManyKeywordFetches();

        $this->runImport();

        $enum = $this->fetchCategoriesByRemoteId('keyword:https://thuecat.org/ontology/thuecat/1.0/Ambiance');
        $term = $this->fetchCategoriesByRemoteId('keyword:https://thuecat.org/ontology/thuecat/1.0/Historic');

        self::assertCount(1, $enum, 'The usage-site enum becomes the parent.');
        self::assertSame($enum[0]['uid'], $term[0]['parent']);
        self::assertSame(
            [],
            $this->fetchCategoriesByRemoteId(
                'keyword:https://thuecat.org/ontology/thuecat/1.0/ConventionLocationTopics'
            ),
            'The term\'s other enum is never created.'
        );
    }

    #[Test]
    public function keywordsNeverLandUnderTheCategoryAnchor(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/KeywordImportPreState.php');
        $this->expectKeywordFetches();

        $this->runImport();

        foreach ($this->fetchCategoriesUnder(100) as $row) {
            self::assertStringStartsNotWith(
                'keyword:',
                (string)$row['remote_id'],
                'A keyword category appeared under the category anchor.'
            );
        }
    }

    // resolve() runs once per root, so dedup state that lived in a local would
    // let the second root stage a duplicate of the shared term. Queueing one
    // fetch per URL also asserts the second root reuses the cached response.
    #[Test]
    public function aTermSharedByTwoRootsYieldsOneCategory(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/KeywordTwoRootPreState.php');
        $this->expectFetch('126981310364-xwgt.json');
        $this->expectFetch('second-root-poi.json');
        $this->expectFetch('475728955106-qdcc.json');
        $this->expectFetch('155933862969-mofh.json');

        $this->runImport();

        self::assertCount(
            1,
            $this->fetchCategoriesByRemoteId('keyword:https://thuecat.org/resources/475728955106-qdcc'),
            'The shared term must yield exactly one category.'
        );
        self::assertCount(
            1,
            $this->fetchCategoriesByRemoteId('keyword:https://thuecat.org/resources/155933862969-mofh'),
            'Its set must not be duplicated either.'
        );
    }

    /**
     * The decisive separation test: one record carrying @type categories and
     * keywords in all three shapes, with titles deliberately duplicated across
     * the two trees. Nothing here may be shared between them.
     */
    #[Test]
    public function categoriesAndKeywordsNeverMeetEvenWhenTitlesCollide(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/KeywordCollisionPreState.php');
        $this->expectFetch('collision-poi.json');
        $this->expectFetch('collision-term-museum.json');
        $this->expectFetch('collision-set.json');
        $this->expectFetchForUrl(
            'https://thuecat.org/ontology/thuecat/1.0/CollisionWeingut',
            'thuecat.org/ontology/thuecat/1.0/CollisionWeingut.json'
        );
        $this->expectFetchForUrl(
            'https://thuecat.org/ontology/thuecat/1.0/Ambiance',
            'thuecat.org/ontology/thuecat/1.0/Ambiance.json'
        );

        $this->runImport();

        // Museum: type category + referenced term + free-text literal.
        // Weingut: type category + ontology CURIE.
        // Sammlung: the term set only, proving an intermediate level exists.
        $museumRows = $this->fetchCategoriesByTitle('Museum');
        $weingutRows = $this->fetchCategoriesByTitle('Weingut');

        self::assertCount(3, $museumRows, 'Museum collides across both trees three ways.');
        self::assertCount(2, $weingutRows, 'Weingut collides across both trees two ways.');
        self::assertCount(1, $this->fetchCategoriesByTitle('Sammlung'), 'The term set is created once.');

        foreach ([$museumRows, $weingutRows] as $sameTitle) {
            $remoteIds = array_column($sameTitle, 'remote_id');
            self::assertSame(
                $remoteIds,
                array_unique($remoteIds),
                'Same-titled rows must be distinct records with distinct remote_ids.'
            );
        }

        $checked = 0;
        foreach ($this->fetchAllCategories() as $row) {
            // The two anchors themselves are pre-state, not imported.
            if ((string)$row['remote_id'] === '') {
                continue;
            }
            $checked++;

            $underKeywordAnchor = $this->isDescendantOf((int)$row['uid'], 200);
            $isKeyword = str_starts_with((string)$row['remote_id'], 'keyword:');

            self::assertSame(
                $isKeyword,
                $underKeywordAnchor,
                sprintf(
                    'Row "%s" (%s) sits under the wrong anchor.',
                    $row['title'],
                    $row['remote_id']
                )
            );
        }

        // Guards the loop: without imported rows the assertions above are vacuous.
        self::assertGreaterThanOrEqual(5, $checked, 'Both trees must have been populated.');
    }

    /**
     * @return list<array{uid: int, pid: int, parent: int, title: string, remote_id: string}>
     */
    private function fetchCategoriesByTitle(string $title): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('sys_category');
        $queryBuilder->getRestrictions()->removeAll();

        return $this->narrow($queryBuilder
            ->select('uid', 'pid', 'parent', 'title', 'remote_id')
            ->from('sys_category')
            ->where($queryBuilder->expr()->eq('title', $queryBuilder->createNamedParameter($title)))
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative());
    }

    /**
     * @return list<array{uid: int, pid: int, parent: int, title: string, remote_id: string}>
     */
    private function fetchAllCategories(): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('sys_category');
        $queryBuilder->getRestrictions()->removeAll();

        return $this->narrow($queryBuilder
            ->select('uid', 'pid', 'parent', 'title', 'remote_id')
            ->from('sys_category')
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative());
    }

    private function isDescendantOf(int $uid, int $ancestorUid): bool
    {
        $seen = [];
        $current = $uid;
        while ($current > 0 && !isset($seen[$current])) {
            $seen[$current] = true;
            if ($current === $ancestorUid) {
                return true;
            }
            $current = $this->parentOf($current);
        }

        return false;
    }

    private function parentOf(int $uid): int
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('sys_category');
        $queryBuilder->getRestrictions()->removeAll();

        $parent = $queryBuilder
            ->select('parent')
            ->from('sys_category')
            ->where($queryBuilder->expr()->eq(
                'uid',
                $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)
            ))
            ->executeQuery()
            ->fetchOne()
        ;

        return is_numeric($parent) ? (int)$parent : 0;
    }

    /**
     * @param list<array<string, mixed>> $rows
     *
     * @return list<array{uid: int, pid: int, parent: int, title: string, remote_id: string}>
     */
    private function narrow(array $rows): array
    {
        return array_map(static fn (array $row): array => [
            'uid' => is_numeric($row['uid'] ?? null) ? (int)$row['uid'] : 0,
            'pid' => is_numeric($row['pid'] ?? null) ? (int)$row['pid'] : 0,
            'parent' => is_numeric($row['parent'] ?? null) ? (int)$row['parent'] : 0,
            'title' => is_string($row['title'] ?? null) ? $row['title'] : '',
            'remote_id' => is_string($row['remote_id'] ?? null) ? $row['remote_id'] : '',
        ], $rows);
    }

    private function expectKeywordFetches(): void
    {
        $this->expectFetch('126981310364-xwgt.json');
        $this->expectFetch('475728955106-qdcc.json');
        $this->expectFetch('155933862969-mofh.json');
    }

    private function countKeywordRelations(string $table, int $categoryUid): int
    {
        return $this->countRelationsForField($table, $categoryUid, 'keywords');
    }

    /**
     * Languages of the records related to one category, ascending. Proves both
     * how many relations exist and which record rows they belong to.
     *
     * @return list<int>
     */
    private function fetchRelatedRecordLanguages(string $table, int $categoryUid, string $field): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('sys_category_record_mm');
        $rows = $queryBuilder
            ->select('uid_foreign')
            ->from('sys_category_record_mm')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid_local',
                    $queryBuilder->createNamedParameter($categoryUid, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq('tablenames', $queryBuilder->createNamedParameter($table)),
                $queryBuilder->expr()->eq('fieldname', $queryBuilder->createNamedParameter($field))
            )
            ->executeQuery()
            ->fetchFirstColumn()
        ;

        $languages = [];
        foreach ($rows as $uid) {
            if (!is_numeric($uid)) {
                continue;
            }

            $recordQuery = $this->getConnectionPool()->getQueryBuilderForTable($table);
            $recordQuery->getRestrictions()->removeAll();
            $language = $recordQuery
                ->select('sys_language_uid')
                ->from($table)
                ->where($recordQuery->expr()->eq(
                    'uid',
                    $recordQuery->createNamedParameter((int)$uid, Connection::PARAM_INT)
                ))
                ->executeQuery()
                ->fetchOne()
            ;
            $languages[] = is_numeric($language) ? (int)$language : -1;
        }

        sort($languages);

        return $languages;
    }

    private function countRelationsForOwner(string $table, int $ownerUid, string $field): int
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('sys_category_record_mm');
        $count = $queryBuilder->count('uid_foreign')
            ->from('sys_category_record_mm')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid_foreign',
                    $queryBuilder->createNamedParameter($ownerUid, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq('tablenames', $queryBuilder->createNamedParameter($table)),
                $queryBuilder->expr()->eq('fieldname', $queryBuilder->createNamedParameter($field))
            )
            ->executeQuery()
            ->fetchOne()
        ;

        return is_numeric($count) ? (int)$count : 0;
    }

    private function countRelationsForField(string $table, int $categoryUid, string $field): int
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('sys_category_record_mm');
        $count = $queryBuilder->count('uid_local')
            ->from('sys_category_record_mm')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid_local',
                    $queryBuilder->createNamedParameter($categoryUid, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq('tablenames', $queryBuilder->createNamedParameter($table)),
                $queryBuilder->expr()->eq('fieldname', $queryBuilder->createNamedParameter($field))
            )
            ->executeQuery()
            ->fetchOne()
        ;

        return is_numeric($count) ? (int)$count : 0;
    }

    private function expectManyKeywordFetches(): void
    {
        $this->expectFetch('many-poi.json');
        $this->expectFetch('023707063052-axjm.json');
        $this->expectFetch('569995942874-dnwg.json');
        $this->expectFetch('887654277691-eatw.json');
        $this->expectFetch('192875159827-xfqk.json');
        $this->expectFetchForUrl(
            'https://thuecat.org/ontology/thuecat/1.0/Historic',
            'thuecat.org/ontology/thuecat/1.0/Historic.json'
        );
        $this->expectFetchForUrl(
            'https://thuecat.org/ontology/thuecat/1.0/Ambiance',
            'thuecat.org/ontology/thuecat/1.0/Ambiance.json'
        );
    }

    private function expectHuskFetches(): void
    {
        $this->expectFetch('husk-poi.json');
        $this->expectFetch('husk-term-leaf.json');
        $this->expectFetch('husk-set-mid.json');
        $this->expectFetch('husk-group-top.json');
        $this->expectFetch('husk-standalone.json');
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

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchRowsByRemoteId(string $table, string $remoteId): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll();

        return $queryBuilder
            ->select('uid', 'remote_id', 'title')
            ->from($table)
            ->where($queryBuilder->expr()->eq('remote_id', $queryBuilder->createNamedParameter($remoteId)))
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative()
        ;
    }

    private function runImport(): void
    {
        $this->workaroundExtbaseConfiguration();
        $configuration = $this->get(ImportConfigurationRepository::class)->findOneByUid(1);
        self::assertNotNull($configuration, 'Import configuration not found in pre-state.');
        $this->get(Importer::class)->importConfiguration($configuration);
    }

    /**
     * @return list<array{uid: int, pid: int, parent: int, title: string, remote_id: string}>
     */
    private function fetchCategoriesByRemoteId(string $remoteId): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('sys_category');
        $queryBuilder->getRestrictions()->removeAll();

        return $this->narrow($queryBuilder
            ->select('uid', 'pid', 'parent', 'title', 'remote_id')
            ->from('sys_category')
            ->where($queryBuilder->expr()->eq('remote_id', $queryBuilder->createNamedParameter($remoteId)))
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative());
    }

    /**
     * @return list<array{uid: int, pid: int, parent: int, title: string, remote_id: string}>
     */
    private function fetchCategoriesUnder(int $parent): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('sys_category');
        $queryBuilder->getRestrictions()->removeAll();

        return $this->narrow($queryBuilder
            ->select('uid', 'pid', 'parent', 'title', 'remote_id')
            ->from('sys_category')
            ->where($queryBuilder->expr()->eq(
                'parent',
                $queryBuilder->createNamedParameter($parent, Connection::PARAM_INT)
            ))
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative());
    }
}
