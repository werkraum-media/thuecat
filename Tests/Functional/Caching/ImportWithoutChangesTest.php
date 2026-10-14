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

namespace WerkraumMedia\ThueCat\Tests\Functional\Caching;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use WerkraumMedia\ThueCat\Extension;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractCachingTestCase;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractImportTestCase;

/**
 * What an import that changes nothing does to the caches.
 *
 * It leaves the records alone — `tstamp` is untouched — and discards their
 * caches anyway. Why is not yet established.
 *
 * These tests pin the CURRENT behaviour rather than the desired one, so the day
 * it improves is visible.
 *
 * Needs the import harness and a real cache backend at once, so it adds the
 * caching configuration to the import base.
 */
class ImportWithoutChangesTest extends AbstractImportTestCase
{
    protected array $pathsToLinkInTestInstance = [
        // A renderable site that carries no import settings. The import fixture
        // uses an absolute base and never renders, so no site would resolve for
        // a test request; the frontend one renders but declares category and
        // keyword anchors pointing at pages and categories this fixture does
        // not have, which the import validator rejects.
        'typo3conf/ext/thuecat/Tests/Functional/Fixtures/Caching/Sites/' => 'typo3conf/sites',
    ];

    protected array $configurationToUseInTestInstance = [
        ...AbstractImportTestCase::CONFIGURATION,
        ...AbstractCachingTestCase::CONFIGURATION,
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportWithoutChanges.php');
        $this->setUpFrontendRootPage(1, [
            'EXT:thuecat/Configuration/TypoScript/Default/Setup.typoscript',
            'EXT:thuecat/Tests/Functional/Fixtures/Frontend/PluginRendering.typoscript',
        ]);
    }

    /** The record is not written: `tstamp` stands. */
    #[Test]
    public function anImportFindingNoChangesWritesNoRecord(): void
    {
        $this->import();
        $timestamps = $this->timestamps();
        self::assertNotSame([], $timestamps, 'The first import must store a record.');

        $this->import();

        self::assertSame($timestamps, $this->timestamps());
    }

    /**
     * …and the caches go anyway. Asserted as it is, not as it should be.
     *
     * When this is fixed the test fails and should be inverted — that is the
     * point of it.
     */
    #[Test]
    public function anImportFindingNoChangesDiscardsTheCachesRegardless(): void
    {
        $this->import();
        $this->warmCaches();

        self::assertNotSame([], $this->storedEntries()['teaser'], 'Teasers stored.');
        self::assertNotSame([], $this->storedEntries()['list'], 'A list stored.');

        $this->import();

        self::assertSame(
            ['teaser' => [], 'list' => []],
            $this->storedEntries(),
            'Known limitation: an import discards the caches of every record it visits.'
        );
    }

    #[Test]
    public function anImportChangingARecordDiscardsItsCaches(): void
    {
        $this->import();
        $this->warmCaches();
        self::assertNotSame([], $this->storedEntries()['teaser'], 'Teasers stored.');

        $this->import('attraction-with-category-renamed.json');

        self::assertSame(
            [],
            $this->storedEntries()['teaser'],
            'A changed record discards what displayed it.'
        );
    }

    protected function import(string $fixture = 'attraction-with-category.json'): void
    {
        $this->expectFetchForUrl(
            'https://thuecat.org/resources/attraction-with-category',
            'thuecat.org/resources/' . $fixture
        );
        // Bypassing the fetch cache: a cached response would stand in for the
        // staged one, and the record would never change.
        $this->importConfigurationBypassingCache(1);
    }

    private function warmCaches(): void
    {
        $this->executeFrontendSubRequest((new InternalRequest())->withPageId(30));
    }

    /**
     * @return array{teaser: list<string>, list: list<string>}
     */
    private function storedEntries(): array
    {
        return [
            'teaser' => $this->identifiers(Extension::CACHE_TEASER),
            'list' => $this->identifiers(Extension::CACHE_LIST),
        ];
    }

    /**
     * @return list<string>
     */
    private function identifiers(string $cacheIdentifier): array
    {
        $backend = $this->get(CacheManager::class)->getCache($cacheIdentifier)->getBackend();
        self::assertInstanceOf(Typo3DatabaseBackend::class, $backend);

        $identifiers = [];
        foreach ($backend->findIdentifiersByTag('tx_thuecat_tourist_attraction') as $identifier) {
            if (is_string($identifier)) {
                $identifiers[] = $identifier;
            }
        }
        foreach ($this->attractionUids() as $uid) {
            foreach ($backend->findIdentifiersByTag('tx_thuecat_tourist_attraction_' . $uid) as $identifier) {
                if (is_string($identifier)) {
                    $identifiers[] = $identifier;
                }
            }
        }
        $identifiers = array_values(array_unique($identifiers));
        sort($identifiers);

        return $identifiers;
    }

    /**
     * `tstamp` is written under the same condition that gates the update, so
     * one column shows whether anything was written at all.
     *
     * @return array<int, int>
     */
    private function timestamps(): array
    {
        $rows = $this->getConnectionPool()
            ->getConnectionForTable('tx_thuecat_tourist_attraction')
            ->select(['uid', 'tstamp'], 'tx_thuecat_tourist_attraction')
            ->fetchAllAssociative()
        ;

        $timestamps = [];
        foreach ($rows as $row) {
            $uid = $row['uid'] ?? null;
            $tstamp = $row['tstamp'] ?? null;
            if (is_scalar($uid) && is_scalar($tstamp)) {
                $timestamps[(int)$uid] = (int)$tstamp;
            }
        }
        ksort($timestamps);

        return $timestamps;
    }

    /**
     * @return list<int>
     */
    private function attractionUids(): array
    {
        return array_keys($this->timestamps());
    }
}
