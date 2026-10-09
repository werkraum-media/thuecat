<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS extension "thuecat".
 *
 * Copyright (C) werkraum-media <https://werkraum-media.de/>
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * For the full license text, see the LICENSE file distributed with this
 * extension.
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 */

namespace WerkraumMedia\ThueCat\Tests\Functional\Import;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractImportTestCase;

/**
 * Keywords land under the anchors of the record kind that cites them, so one
 * run can write into several keyword trees.
 */
class ScopedKeywordAnchorImportTest extends AbstractImportTestCase
{
    private const TRAIL = 'https://thuecat.org/resources/e_106954656-oatour';
    private const ATTRACTION = 'https://thuecat.org/resources/attraction-sharing-trail-keyword';
    private const TOURIST_INFORMATION = 'https://thuecat.org/resources/tourist-information-with-trail-keyword';
    private const SHARED_KEYWORD = 'keyword:https://thuecat.org/resources/887654277691-eatw';

    private const THUECAT_ANCHOR = 1200;
    private const TRAILS_ANCHOR = 1300;
    private const SHARED_TREE_ANCHOR = 200;

    protected string $fixtureGuzzleBase = __DIR__ . '/../Fixtures/Import/Guzzle';

    #[Test]
    public function trailRootResolvesKeywordsUnderTheTrailsAnchor(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/TrailKeywordAnchorsPreState.php');
        $this->expectTrailFetches();

        $this->importConfiguration(1);

        $trail = $this->fetchUidByRemoteId('tx_thuecat_trail', self::TRAIL);
        self::assertSame(self::TRAILS_ANCHOR, $this->anchorOfKeywordRelatedTo('tx_thuecat_trail', $trail));
    }

    /**
     * Keywords are exempt from the depth cap, so a trail reached from an
     * attraction carries them and must file them in its own tree.
     */
    #[Test]
    public function trailReachedFromAnAttractionResolvesKeywordsUnderTheTrailsAnchor(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/TrailKeywordAnchorsPreState.php');
        $this->expectFetchForUrl(
            'https://thuecat.org/resources/347070073883-rqbn',
            'thuecat.org/resources/attraction-in-trail-with-relations.json'
        );
        $this->expectTrailFetches();

        $this->importConfiguration(4);

        $trail = $this->fetchUidByRemoteId('tx_thuecat_trail', self::TRAIL);
        self::assertSame(self::TRAILS_ANCHOR, $this->anchorOfKeywordRelatedTo('tx_thuecat_trail', $trail));
    }

    #[Test]
    public function attractionAndTrailSharingAKeywordGetOneRecordPerTree(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/TrailKeywordAnchorsPreState.php');
        $this->expectFetch('attraction-sharing-trail-keyword.json');
        $this->expectTrailFetches();

        $this->importConfiguration(2);

        self::assertCount(2, $this->keywordUids(), 'One record per keyword tree.');

        $attraction = $this->fetchUidByRemoteId('tx_thuecat_tourist_attraction', self::ATTRACTION);
        $trail = $this->fetchUidByRemoteId('tx_thuecat_trail', self::TRAIL);
        self::assertSame(
            self::THUECAT_ANCHOR,
            $this->anchorOfKeywordRelatedTo('tx_thuecat_tourist_attraction', $attraction)
        );
        self::assertSame(self::TRAILS_ANCHOR, $this->anchorOfKeywordRelatedTo('tx_thuecat_trail', $trail));
    }

    #[Test]
    public function attractionAndTrailInOneTreeShareTheKeywordRecord(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/TrailKeywordsSharedTreePreState.php');
        $this->expectFetch('attraction-sharing-trail-keyword.json');
        $this->expectTrailFetches();

        $this->importConfiguration(1);

        self::assertCount(1, $this->keywordUids());

        $attraction = $this->fetchUidByRemoteId('tx_thuecat_tourist_attraction', self::ATTRACTION);
        $trail = $this->fetchUidByRemoteId('tx_thuecat_trail', self::TRAIL);
        self::assertSame(
            self::SHARED_TREE_ANCHOR,
            $this->anchorOfKeywordRelatedTo('tx_thuecat_tourist_attraction', $attraction)
        );
        self::assertSame(self::SHARED_TREE_ANCHOR, $this->anchorOfKeywordRelatedTo('tx_thuecat_trail', $trail));
    }

    /**
     * Relation kinds are shared between imports of every top-level kind, so
     * their keywords must not follow the root that reached them.
     */
    #[Test]
    public function relationKindResolvesUnderThuecatInATrailRun(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/TrailKeywordAnchorsPreState.php');
        $this->expectTrailFetches();
        $this->expectFetch('tourist-information-with-trail-keyword.json');

        $this->importConfiguration(3);

        $touristInformation = $this->fetchUidByRemoteId('tx_thuecat_tourist_information', self::TOURIST_INFORMATION);
        self::assertSame(
            self::THUECAT_ANCHOR,
            $this->anchorOfKeywordRelatedTo('tx_thuecat_tourist_information', $touristInformation)
        );
    }

    private function expectTrailFetches(): void
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

    /**
     * The anchor the owner's shared keyword hangs below, found by walking up
     * from the related record.
     */
    private function anchorOfKeywordRelatedTo(string $table, int $ownerUid): int
    {
        self::assertGreaterThan(0, $ownerUid, $table . ' record must be imported.');

        $related = array_values(array_intersect($this->keywordUids(), $this->relatedCategoryUids($table, $ownerUid)));
        self::assertCount(1, $related, 'The owner relates to exactly one record of the shared keyword.');

        $uid = $related[0];
        for ($depth = 0; $depth < 10; $depth++) {
            $parent = $this->parentOf($uid);
            if (in_array($parent, [self::THUECAT_ANCHOR, self::TRAILS_ANCHOR, self::SHARED_TREE_ANCHOR], true)) {
                return $parent;
            }
            if ($parent === 0) {
                break;
            }
            $uid = $parent;
        }

        self::fail('The keyword hangs below no configured anchor.');
    }

    /**
     * @return list<int>
     */
    private function keywordUids(): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('sys_category');
        $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());

        return array_map(
            self::asInt(...),
            $queryBuilder
                ->select('uid')
                ->from('sys_category')
                ->where(
                    $queryBuilder->expr()->eq('remote_id', $queryBuilder->createNamedParameter(self::SHARED_KEYWORD)),
                    $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT))
                )
                ->executeQuery()
                ->fetchFirstColumn()
        );
    }

    /**
     * @return list<int>
     */
    private function relatedCategoryUids(string $table, int $ownerUid): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('sys_category_record_mm');

        return array_map(
            self::asInt(...),
            $queryBuilder
                ->select('uid_local')
                ->from('sys_category_record_mm')
                ->where(
                    $queryBuilder->expr()->eq('uid_foreign', $queryBuilder->createNamedParameter($ownerUid, Connection::PARAM_INT)),
                    $queryBuilder->expr()->eq('tablenames', $queryBuilder->createNamedParameter($table))
                )
                ->executeQuery()
                ->fetchFirstColumn()
        );
    }

    private function parentOf(int $uid): int
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('sys_category');
        $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());

        return self::asInt($queryBuilder
            ->select('parent')
            ->from('sys_category')
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchOne());
    }

    private static function asInt(mixed $value): int
    {
        return is_numeric($value) ? (int)$value : 0;
    }
}
