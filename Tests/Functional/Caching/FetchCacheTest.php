<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional\Caching;

/*
 * Copyright (C) 2026 werkraum-media
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 */

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use TYPO3\CMS\Core\Cache\CacheManager;
use WerkraumMedia\ThueCat\Import\Settings\ImportSetting;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractImportTestCase;
use WerkraumMedia\ThueCat\Tests\Functional\GuzzleClientFaker;

/**
 * The fetch cache must outlive a run so an abort-and-retry is cheap.
 *
 * Staged-but-unconsumed fetches fail in tearDown, so what a run staged is the
 * assertion: nothing staged proves a cache hit, two staged prove two requests.
 */
class FetchCacheTest extends AbstractImportTestCase
{
    #[Test]
    public function cacheIsRegisteredWithAPersistentBackend(): void
    {
        $backend = $this->get(CacheManager::class)->getCache('thuecat_fetchdata')->getBackend();

        self::assertInstanceOf(
            Typo3DatabaseBackend::class,
            $backend,
            'A transient backend dies with the process, so a re-run re-fetches everything.'
        );
    }

    #[Test]
    public function aSecondRunServesTheResponseFromTheCache(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsFreshOrganization.php');
        $this->expectFetch('018132452787-ngbe.json');

        $this->importConfiguration(1);
        // Nothing staged: an HTTP request here has no response to consume.
        $this->importConfiguration(1);

        self::assertSame(
            1,
            GuzzleClientFaker::countConsumed(),
            'Two runs, one request: the second was served from the cache.'
        );
    }

    #[Test]
    public function entriesAreWrittenToTheDatabase(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsFreshOrganization.php');
        $this->expectFetch('018132452787-ngbe.json');

        $this->importConfiguration(1);

        $rows = $this->getConnectionPool()
            ->getConnectionForTable('cache_thuecat_fetchdata')
            ->count('*', 'cache_thuecat_fetchdata', [])
        ;

        self::assertGreaterThan(0, $rows, 'The entry survives the process only if it is on disk.');
    }

    #[Test]
    public function entriesExpireAfterTheConfiguredLifetime(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsFreshOrganization.php');
        $this->expectFetch('018132452787-ngbe.json');

        $this->importConfiguration(1);

        $expires = $this->getConnectionPool()
            ->getConnectionForTable('cache_thuecat_fetchdata')
            ->select(['expires'], 'cache_thuecat_fetchdata', [])
            ->fetchOne()
        ;

        self::assertIsNumeric($expires);
        $lifetime = ImportSetting::FetchCacheLifetime->default();
        self::assertLessThanOrEqual(time() + $lifetime, (int)$expires, 'A stale snapshot must not outlive the lifetime.');
        self::assertGreaterThan(time(), (int)$expires);
    }

    #[Test]
    public function theBypassForcesAFreshFetch(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsFreshOrganization.php');
        $this->expectFetch('018132452787-ngbe.json');
        // Staged twice: the bypassing run must go to the API despite the entry.
        $this->expectFetch('018132452787-ngbe.json');

        $this->importConfiguration(1);
        $this->importConfigurationBypassingCache(1);

        self::assertSame(
            2,
            GuzzleClientFaker::countConsumed(),
            'The bypassing run went to the API despite a warm cache.'
        );
    }

    #[Test]
    public function bypassingDoesNotChangeWhatIsImported(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsFreshOrganization.php');
        $this->expectFetch('018132452787-ngbe.json');

        $this->importConfigurationBypassingCache(1);

        $this->assertPHPDataSet(__DIR__ . '/../Assertions/Import/ImportsFreshOrganization.php');
    }
}
