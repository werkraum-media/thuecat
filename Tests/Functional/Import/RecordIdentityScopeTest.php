<?php

declare(strict_types=1);

/*
 * Copyright (C) 2024 werkraum-media
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
use WerkraumMedia\ThueCat\Tests\Functional\AbstractImportTestCase;

/**
 * A remote_id identifies a record within the site that imports it. Two sites
 * importing the same upstream object each keep their own record; neither reads
 * or writes the other's.
 */
class RecordIdentityScopeTest extends AbstractImportTestCase
{
    private const TOWN_REMOTE_ID = 'https://thuecat.org/resources/043064193523-jcyt';
    private const ORGANISATION_REMOTE_ID = 'https://thuecat.org/resources/018132452787-ngbe';

    #[Test]
    public function twoSitesImportingOneObjectEachHoldTheirOwnRecord(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/TwoSitesPreState.php');
        $this->expectTownFetches();

        $this->importConfiguration(1);
        $this->importConfiguration(2);

        $towns = $this->fetchRecordsByRemoteId('tx_thuecat_town', self::TOWN_REMOTE_ID);
        self::assertCount(2, $towns, 'Each site must hold its own town record.');
        self::assertSame(
            [4010, 5010],
            array_column($towns, 'pid'),
            'One town per site, each in its own storage folder.'
        );
    }

    #[Test]
    public function importingSiteLeavesAForeignRecordUntouched(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/TwoSitesWithExistingRecordPreState.php');
        $this->expectTownFetches();

        // Only the second site imports; the first site's records are pre-seeded
        // and stale, so any write into them shows up as a changed title.
        $this->importConfiguration(2);

        $towns = $this->fetchRecordsByRemoteId('tx_thuecat_town', self::TOWN_REMOTE_ID);
        self::assertCount(2, $towns, 'The second site creates its own town beside the first site\'s.');

        $foreign = $this->recordWithPid($towns, 4010);
        self::assertSame(
            'Stale title of the first site',
            $foreign['title'],
            'The first site\'s town must keep its stored title.'
        );
        self::assertSame(4010, $foreign['pid'], 'The first site\'s town must keep its pid.');
    }

    #[Test]
    public function repeatedRunsStayWithTheirOwnRecord(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/TwoSitesPreState.php');
        $this->expectTownFetches();

        $this->importConfiguration(1);
        $this->importConfiguration(2);
        $this->importConfiguration(1);
        $this->importConfiguration(2);

        self::assertCount(
            2,
            $this->fetchRecordsByRemoteId('tx_thuecat_town', self::TOWN_REMOTE_ID),
            'A second run per site updates its own record rather than adding one.'
        );
    }

    #[Test]
    public function aRecordOnAnotherPageOfTheSameSiteIsMatched(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/TwoSitesPreState.php');
        // Same site as configuration 1 (root 4000), but stored on the category
        // folder rather than the configured storagePid.
        $this->seedTown(4020, 'Stale title on another page of the site');
        $this->expectTownFetches();

        $this->importConfiguration(1);

        $towns = $this->fetchRecordsByRemoteId('tx_thuecat_town', self::TOWN_REMOTE_ID);
        self::assertCount(1, $towns, 'A record elsewhere in the same site is matched, not duplicated.');
        self::assertNotSame(
            'Stale title on another page of the site',
            $towns[0]['title'],
            'The matched record must have been updated by the import.'
        );
    }

    #[Test]
    public function twoConfigurationsOfOneSiteShareARecord(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/TwoSitesPreState.php');
        // Point configuration 2 into the first site, so both configurations
        // write into site 4000 through different storage folders.
        $this->moveConfigurationStorage(2, 4020);
        $this->expectTownFetches();

        $this->importConfiguration(1);
        $this->importConfiguration(2);

        self::assertCount(
            1,
            $this->fetchRecordsByRemoteId('tx_thuecat_town', self::TOWN_REMOTE_ID),
            'Two configurations of one site share the record for an upstream object.'
        );
    }

    #[Test]
    public function aRelationTargetOfAnotherSiteIsNotReused(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/TwoSitesWithExistingRecordPreState.php');
        $this->expectTownFetches();

        $this->importConfiguration(2);

        $organisations = $this->fetchRecordsByRemoteId(
            'tx_thuecat_organisation',
            self::ORGANISATION_REMOTE_ID
        );
        self::assertCount(2, $organisations, 'The importing site resolves the relation target within itself.');

        $towns = $this->fetchRecordsByRemoteId('tx_thuecat_town', self::TOWN_REMOTE_ID);
        $importedTown = $this->recordWithPid($towns, 5010);
        $ownOrganisation = $this->recordWithPid($organisations, 5010);
        self::assertSame(
            $ownOrganisation['uid'],
            $importedTown['managed_by'],
            'The relation must point at the importing site\'s organisation, never the foreign one.'
        );
    }

    #[Test]
    public function aRelationTargetWithinTheSiteIsReused(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/TwoSitesPreState.php');
        $this->expectTownFetches();

        $this->importConfiguration(1);

        $organisations = $this->fetchRecordsByRemoteId(
            'tx_thuecat_organisation',
            self::ORGANISATION_REMOTE_ID
        );
        self::assertCount(1, $organisations, 'The relation target is created once within the site.');

        $towns = $this->fetchRecordsByRemoteId('tx_thuecat_town', self::TOWN_REMOTE_ID);
        self::assertSame(
            $organisations[0]['uid'],
            $towns[0]['managed_by'],
            'The town relates to the organisation of its own site.'
        );
    }

    #[Test]
    public function aRecordOnAHiddenPageIsStillMatched(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/HiddenStorageFolderPreState.php');
        $this->expectTownFetches();

        $this->importConfiguration(1);

        $towns = $this->fetchRecordsByRemoteId('tx_thuecat_town', self::TOWN_REMOTE_ID);
        self::assertCount(1, $towns, 'A hidden storage folder must not cause a duplicate record.');
        self::assertNotSame(
            'Stale title on a hidden page',
            $towns[0]['title'],
            'The record on the hidden page must have been matched and updated.'
        );
    }

    #[Test]
    public function aRecordOnAPageOutsideItsPublicationWindowIsStillMatched(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/HiddenStorageFolderPreState.php');
        // Visible, but its publication window closed in the past: the other way
        // enable-fields drop a page out of a frontend-oriented page-id set.
        $this->makeStorageFolderExpired(4010);
        $this->expectTownFetches();

        $this->importConfiguration(1);

        $towns = $this->fetchRecordsByRemoteId('tx_thuecat_town', self::TOWN_REMOTE_ID);
        self::assertCount(1, $towns, 'An expired storage folder must not cause a duplicate record.');
    }

    #[Test]
    public function aRecordOnADeletedPageIsNotMatched(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/TwoSitesPreState.php');
        // Page 4040 carries no configured role — not the record storagePid,
        // not a category or keyword anchor — so deleting it leaves the import
        // configuration valid and the run reaches record matching.
        $this->seedTown(4040, 'Stale title on a deleted page');
        $this->deleteStorageFolder(4040);
        $this->expectTownFetches();

        $this->importConfiguration(1);

        $towns = $this->fetchRecordsByRemoteId('tx_thuecat_town', self::TOWN_REMOTE_ID);
        self::assertCount(2, $towns, 'A record on a deleted page is out of scope, so a fresh one is created.');
        self::assertSame(
            'Stale title on a deleted page',
            $this->recordWithPid($towns, 4040)['title'],
            'The record on the deleted page is left untouched.'
        );
    }

    private function makeStorageFolderExpired(int $pid): void
    {
        $this->getConnectionPool()
            ->getConnectionForTable('pages')
            ->update('pages', ['hidden' => 0, 'endtime' => 1000000000], ['uid' => $pid])
        ;
    }

    private function deleteStorageFolder(int $pid): void
    {
        $this->getConnectionPool()
            ->getConnectionForTable('pages')
            ->update('pages', ['deleted' => 1], ['uid' => $pid])
        ;
    }

    /**
     * The town carries a managedBy reference, so one import fetches both the
     * town and its organisation.
     *
     * Staged once per URL per test, however many configurations run: the fetch
     * cache keys on URL and api key alone (FetchData::fetchUrl), so a second
     * import of the same URL is served from cache and never reaches HTTP.
     */
    private function expectTownFetches(): void
    {
        $this->expectFetch('043064193523-jcyt.json');
        $this->expectFetch('018132452787-ngbe.json');
    }

    private function seedTown(int $pid, string $title): void
    {
        $this->getConnectionPool()
            ->getConnectionForTable('tx_thuecat_town')
            ->insert('tx_thuecat_town', [
                'pid' => $pid,
                'remote_id' => self::TOWN_REMOTE_ID,
                'title' => $title,
            ])
        ;
    }

    private function moveConfigurationStorage(int $uid, int $storagePid): void
    {
        $connection = $this->getConnectionPool()
            ->getConnectionForTable('tx_thuecat_import_configuration')
        ;
        $configuration = $connection->select(
            ['configuration'],
            'tx_thuecat_import_configuration',
            ['uid' => $uid]
        )->fetchAssociative();
        self::assertIsArray($configuration, 'Fixture configuration uid=' . $uid . ' not found');

        $flexForm = is_string($configuration['configuration'] ?? null)
            ? $configuration['configuration']
            : '';
        $replaced = preg_replace(
            '#(<field index="storagePid">\s*<value index="vDEF">)\d+(</value>)#',
            '${1}' . $storagePid . '${2}',
            $flexForm
        );
        self::assertIsString($replaced);
        self::assertNotSame($flexForm, $replaced, 'storagePid was not rewritten in the flexform');

        $connection->update(
            'tx_thuecat_import_configuration',
            ['configuration' => $replaced],
            ['uid' => $uid]
        );
    }

    /**
     * @param list<array<string, mixed>> $records
     *
     * @return array<string, mixed>
     */
    private function recordWithPid(array $records, int $pid): array
    {
        foreach ($records as $record) {
            if ($record['pid'] === $pid) {
                return $record;
            }
        }

        self::fail('No record stored on pid ' . $pid);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchRecordsByRemoteId(string $table, string $remoteId): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll();

        $rows = $queryBuilder
            ->select('*')
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq('remote_id', $queryBuilder->createNamedParameter($remoteId)),
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter(0))
            )
            ->orderBy('pid')
            ->executeQuery()
            ->fetchAllAssociative()
        ;

        $result = [];
        foreach ($rows as $row) {
            $row['uid'] = is_numeric($row['uid'] ?? null) ? (int)$row['uid'] : 0;
            $row['pid'] = is_numeric($row['pid'] ?? null) ? (int)$row['pid'] : 0;
            if (array_key_exists('managed_by', $row)) {
                $row['managed_by'] = is_numeric($row['managed_by']) ? (int)$row['managed_by'] : 0;
            }
            $result[] = $row;
        }

        return $result;
    }
}
