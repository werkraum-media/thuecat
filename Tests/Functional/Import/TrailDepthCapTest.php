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

namespace WerkraumMedia\ThueCat\Tests\Functional\Import;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractImportTestCase;

/**
 * MAX_FETCH_DEPTH is 1: a trail reached through an attraction's containment sits
 * at depth 1, so its own reference buckets are dropped rather than followed.
 * Keywords and media short-circuit before that check and stay exempt.
 */
class TrailDepthCapTest extends AbstractImportTestCase
{
    private const TRAIL_ID = 'https://thuecat.org/resources/e_106954656-oatour';
    private const ATTRACTION_ID = 'https://thuecat.org/resources/347070073883-rqbn';
    private const ORGANISATION_ID = 'https://thuecat.org/resources/018132452787-ngbe';

    protected string $fixtureGuzzleBase = __DIR__ . '/../Fixtures/Import/Guzzle';

    #[Test]
    public function trailReachedAsAReferenceKeepsItsScalarFields(): void
    {
        $this->importAttractionOnly();

        $trail = $this->fetchTrail();
        self::assertSame(self::TRAIL_ID, $trail['remote_id']);
        self::assertSame('Goethe-Erlebnisweg', $trail['title']);
    }

    #[Test]
    public function trailReachedAsAReferenceLeavesItsOwnRelationsUnset(): void
    {
        $this->importAttractionOnly();

        // contentResponsible sits behind the depth check, so it is dropped.
        self::assertSame(0, $this->fetchTrailManagedBy());
    }

    #[Test]
    public function nothingIsReportedAboutTheUnfollowedReferences(): void
    {
        $this->importAttractionOnly();

        self::assertSame([], $this->getLogEntriesOfType('referenceUnrelatable'));
        self::assertSame([], $this->getLogEntriesOfType('referenceSkipped'));
    }

    #[Test]
    public function keywordsResolveEvenAtTheDepthCap(): void
    {
        $this->importAttractionOnly();

        self::assertSame(
            6,
            $this->countKeywordRelations($this->fetchTrailUid()),
            'A trail reached as a reference must still get its keywords.'
        );
    }

    #[Test]
    public function childRecordsSurviveBeingReachedAsAReference(): void
    {
        $this->importAttractionOnly();

        self::assertCount(
            1,
            $this->fetchChildren('tx_thuecat_trail_location'),
            'The start location is manufactured during parse(), not fetched.'
        );
    }

    #[Test]
    public function promotingTheTrailToARootGainsTheRelationsTheCapLeftUnset(): void
    {
        $this->importAttractionOnly();
        self::assertSame(0, $this->fetchTrailManagedBy(), 'Precondition: the cap left it unset.');

        // Same trail, now a root of its own, so depth 0 and no cap.
        // the first run cached the trail, so a plain import would
        // serve it from cache and fetch nothing.
        $this->expectFetchForUrl(self::TRAIL_ID, 'thuecat.org/resources/trail-with-content-responsible.json');
        $this->expectFetch('018132452787-ngbe.json');
        $this->importConfigurationBypassingCache(2);

        $organisationUid = $this->fetchUidByRemoteId('tx_thuecat_organisation', self::ORGANISATION_ID);
        self::assertGreaterThan(0, $organisationUid, 'The organisation must now be imported.');
        self::assertSame(
            $organisationUid,
            $this->fetchTrailManagedBy(),
            'A later root import must fill in what the cap skipped.'
        );
    }

    #[Test]
    public function aRelationSurvivesTheTrailLaterBeingReachedAsAReference(): void
    {
        // Root first, so the trail really holds managed_by ...
        $this->expectFetchForUrl(self::TRAIL_ID, 'thuecat.org/resources/trail-with-content-responsible.json');
        $this->expectFetch('018132452787-ngbe.json');
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsAttractionInTrailWithRelations.php');
        $this->importConfiguration(2);

        $organisationUid = $this->fetchUidByRemoteId('tx_thuecat_organisation', self::ORGANISATION_ID);
        self::assertSame($organisationUid, $this->fetchTrailManagedBy(), 'Precondition: the relation is set.');

        // ... then re-enter it as the attraction's reference, where the cap
        // applies. Skipping a relation must not mean clearing it.
        $this->expectFetchForUrl(
            self::ATTRACTION_ID,
            'thuecat.org/resources/attraction-in-trail-with-relations.json'
        );
        $this->expectFetchForUrl(self::TRAIL_ID, 'thuecat.org/resources/e_106954656-oatour.json');
        $this->expectKeywordFetches();
        $this->importConfigurationBypassingCache(1);

        self::assertSame(
            $organisationUid,
            $this->fetchTrailManagedBy(),
            'managed_by must survive the trail being re-imported as a reference.'
        );
    }

    #[Test]
    public function keywordsSurviveTheTrailLaterBeingReachedAsAReference(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsAttractionInTrailWithRelations.php');
        $this->expectFetch('e_106954656-oatour.json');
        $this->expectKeywordFetches();
        $this->importConfiguration(2);

        $trailUid = $this->fetchTrailUid();
        self::assertSame(6, $this->countKeywordRelations($trailUid), 'Precondition: keywords are set.');

        $this->expectFetchForUrl(
            self::ATTRACTION_ID,
            'thuecat.org/resources/attraction-in-trail-with-relations.json'
        );
        $this->expectFetchForUrl(self::TRAIL_ID, 'thuecat.org/resources/e_106954656-oatour.json');
        $this->expectKeywordFetches();
        $this->importConfigurationBypassingCache(1);

        self::assertSame(
            6,
            $this->countKeywordRelations($trailUid),
            'Keywords must neither be cleared nor duplicated by the re-entry.'
        );
    }

    private function importAttractionOnly(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsAttractionInTrailWithRelations.php');
        $this->expectFetchForUrl(
            self::ATTRACTION_ID,
            'thuecat.org/resources/attraction-in-trail-with-relations.json'
        );
        $this->expectFetchForUrl(self::TRAIL_ID, 'thuecat.org/resources/e_106954656-oatour.json');
        $this->expectKeywordFetches();

        $this->importConfiguration(1);
    }

    private function expectKeywordFetches(): void
    {
        foreach ([
            '856934189528-xfec',
            '685822377106-mbtz',
            '055661589550-rnxb',
            '986455731991-nmbx',
            '916373333853-mknj',
            '887654277691-eatw',
            '192875159827-xfqk',
        ] as $resource) {
            $this->expectFetch($resource . '.json');
        }
    }

    private function fetchTrailUid(): int
    {
        $uid = $this->fetchUidByRemoteId('tx_thuecat_trail', self::TRAIL_ID);
        self::assertGreaterThan(0, $uid, 'The trail must be imported.');

        return $uid;
    }

    /** @return array<string, mixed> */
    private function fetchTrail(): array
    {
        return $this->fetchRowByRemoteId('tx_thuecat_trail', self::TRAIL_ID);
    }

    private function fetchTrailManagedBy(): int
    {
        $managedBy = $this->fetchTrail()['managed_by'] ?? null;

        return is_numeric($managedBy) ? (int)$managedBy : 0;
    }

    private function countKeywordRelations(int $trailUid): int
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('sys_category_record_mm');

        $count = $queryBuilder
            ->count('uid_local')
            ->from('sys_category_record_mm')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid_foreign',
                    $queryBuilder->createNamedParameter($trailUid, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'tablenames',
                    $queryBuilder->createNamedParameter('tx_thuecat_trail')
                ),
                $queryBuilder->expr()->eq(
                    'fieldname',
                    $queryBuilder->createNamedParameter('keywords')
                )
            )
            ->executeQuery()
            ->fetchOne()
        ;

        return is_numeric($count) ? (int)$count : 0;
    }

    /** @return list<array<string, mixed>> */
    private function fetchChildren(string $table): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());

        return $queryBuilder
            ->select('*')
            ->from($table)
            ->where($queryBuilder->expr()->eq(
                'sys_language_uid',
                $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
            ))
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative()
        ;
    }
}
