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
use WerkraumMedia\ThueCat\Tests\Functional\AbstractImportTestCase;

class TrailRelationImportTest extends AbstractImportTestCase
{
    private const REMOTE_ID = 'https://thuecat.org/resources/e_106954656-oatour';

    protected string $fixtureGuzzleBase = __DIR__ . '/../Fixtures/Import/Guzzle';

    #[Test]
    public function resolvesTrailKeywordsUnderTheKeywordAnchor(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsTrailWithRelations.php');
        $this->expectKeywordFetches();

        $this->importConfiguration(1);

        $term = $this->fetchCategoryByRemoteId(
            'keyword:https://thuecat.org/resources/887654277691-eatw'
        );
        self::assertSame('Fotospot', $term['title']);
        self::assertSame(30, $term['pid'], 'Created at the configured keywordStoragePid.');

        $set = $this->fetchCategoryByRemoteId(
            'keyword:https://thuecat.org/resources/192875159827-xfqk'
        );
        self::assertSame(200, $set['parent'], 'The term set hangs off the keyword anchor.');
        self::assertSame($set['uid'], $term['parent'], 'The term hangs off its set.');
    }

    #[Test]
    public function relatesEveryKeywordToTheTrail(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsTrailWithRelations.php');
        $this->expectKeywordFetches();

        $this->importConfiguration(1);

        $trailUid = $this->fetchUidByRemoteId('tx_thuecat_trail', self::REMOTE_ID);
        self::assertGreaterThan(0, $trailUid, 'The trail must be imported.');

        self::assertSame(
            6,
            $this->countKeywordRelations($trailUid),
            'Every keyword the trail carries must relate to it.'
        );
    }

    #[Test]
    public function trailWithoutKeywordsOrContentResponsibleImports(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsTrailWithRelations.php');
        $this->expectFetchForUrl(self::REMOTE_ID, 'thuecat.org/resources/trail-without-relations.json');

        $this->importConfiguration(1);

        $trailUid = $this->fetchUidByRemoteId('tx_thuecat_trail', self::REMOTE_ID);
        self::assertGreaterThan(0, $trailUid, 'Absent relations must not block the import.');
        self::assertSame(0, $this->countKeywordRelations($trailUid));
        self::assertSame(0, $this->fetchTrailManagedBy());
    }

    #[Test]
    public function resolvesContentResponsibleIntoManagedBy(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsTrailWithRelations.php');
        $this->expectFetchForUrl(self::REMOTE_ID, 'thuecat.org/resources/trail-with-content-responsible.json');
        $this->expectFetch('018132452787-ngbe.json');

        $this->importConfiguration(1);

        $organisationUid = $this->fetchUidByRemoteId(
            'tx_thuecat_organisation',
            'https://thuecat.org/resources/018132452787-ngbe'
        );
        self::assertGreaterThan(0, $organisationUid, 'The organisation must be imported.');

        self::assertSame(
            $organisationUid,
            $this->fetchTrailManagedBy(),
            'contentResponsible must resolve to the real organisation row.'
        );
    }

    private function expectKeywordFetches(): void
    {
        $this->expectFetch('e_106954656-oatour.json');
        foreach ([
            '856934189528-xfec',
            '685822377106-mbtz',
            '055661589550-rnxb',
            '986455731991-nmbx',
            '916373333853-mknj',
            '887654277691-eatw',
        ] as $term) {
            $this->expectFetch($term . '.json');
        }
        $this->expectFetch('192875159827-xfqk.json');
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

    /** @return array{uid: int, pid: int, parent: int, title: string} */
    private function fetchCategoryByRemoteId(string $remoteId): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('sys_category');
        $queryBuilder->getRestrictions()->removeAll();

        $row = $queryBuilder
            ->select('uid', 'pid', 'parent', 'title')
            ->from('sys_category')
            ->where(
                $queryBuilder->expr()->eq('remote_id', $queryBuilder->createNamedParameter($remoteId)),
                $queryBuilder->expr()->eq(
                    'sys_language_uid',
                    $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                )
            )
            ->executeQuery()
            ->fetchAssociative()
        ;

        self::assertIsArray($row, 'No category for "' . $remoteId . '".');

        return [
            'uid' => is_numeric($row['uid'] ?? null) ? (int)$row['uid'] : 0,
            'pid' => is_numeric($row['pid'] ?? null) ? (int)$row['pid'] : 0,
            'parent' => is_numeric($row['parent'] ?? null) ? (int)$row['parent'] : 0,
            'title' => is_string($row['title'] ?? null) ? $row['title'] : '',
        ];
    }

    private function fetchTrailManagedBy(): int
    {
        $managedBy = $this->fetchRowByRemoteId('tx_thuecat_trail', self::REMOTE_ID)['managed_by'] ?? null;

        return is_numeric($managedBy) ? (int)$managedBy : 0;
    }
}
