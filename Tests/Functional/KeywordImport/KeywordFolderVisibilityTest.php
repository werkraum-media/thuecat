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

/**
 * Keyword categories are matched by where they are stored, not by whether the
 * storing page is visible in the frontend. Hiding the keyword storage folder
 * must not make an import build a second tree beside the existing one.
 */
class KeywordFolderVisibilityTest extends AbstractImportTestCase
{
    private const TERM_REMOTE_ID = 'keyword:https://thuecat.org/resources/475728955106-qdcc';
    private const SET_REMOTE_ID = 'keyword:https://thuecat.org/resources/155933862969-mofh';

    protected array $testExtensionsToLoad = [
        'werkraummedia/thuecat/',
        'werkraummedia/events/',
    ];

    protected string $fixtureGuzzleBase = __DIR__ . '/Fixtures/Guzzle';

    #[Test]
    public function keywordCategoriesOnAHiddenFolderAreReused(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/KeywordHiddenFolderPreState.php');
        $this->expectKeywordFetches();

        // First run creates the tree; hiding happens in the fixture, so the
        // second run is the one that must find it again.
        $this->runImport();
        $this->runImport();

        self::assertCount(
            1,
            $this->fetchCategoriesByRemoteId(self::TERM_REMOTE_ID),
            'The keyword term must be reused, not duplicated, on a hidden folder.'
        );
        self::assertCount(
            1,
            $this->fetchCategoriesByRemoteId(self::SET_REMOTE_ID),
            'The term set must be reused, not duplicated, on a hidden folder.'
        );
    }

    // Deleting the keyword storage folder itself is not tested here: it is a
    // configuration error that ImportConfigurationValidator rejects before any
    // matching happens, and it owns the coverage.

    /**
     * Staged once per URL per test however many runs happen: FetchData caches
     * on URL and api key alone, so the second run is served from cache.
     */
    private function expectKeywordFetches(): void
    {
        $this->expectFetch('126981310364-xwgt.json');
        $this->expectFetch('475728955106-qdcc.json');
        $this->expectFetch('155933862969-mofh.json');
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

        $rows = $queryBuilder
            ->select('uid', 'pid', 'parent', 'title', 'remote_id')
            ->from('sys_category')
            ->where(
                $queryBuilder->expr()->eq('remote_id', $queryBuilder->createNamedParameter($remoteId)),
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT))
            )
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative()
        ;

        return array_map(static fn (array $row): array => [
            'uid' => is_numeric($row['uid'] ?? null) ? (int)$row['uid'] : 0,
            'pid' => is_numeric($row['pid'] ?? null) ? (int)$row['pid'] : 0,
            'parent' => is_numeric($row['parent'] ?? null) ? (int)$row['parent'] : 0,
            'title' => is_string($row['title'] ?? null) ? $row['title'] : '',
            'remote_id' => is_string($row['remote_id'] ?? null) ? $row['remote_id'] : '',
        ], $rows);
    }
}
