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

namespace WerkraumMedia\ThueCat\Tests\Functional\Caching;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use WerkraumMedia\ThueCat\Extension;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractCachingTestCase;

/**
 * Membership in the `pages` group is what makes DataHandler flush our caches:
 * processClearCacheQueue() calls flushCachesInGroupByTags('pages', …). A
 * misspelled group leaves the caches working but never invalidated, which no
 * rendering test would notice.
 *
 * CacheManager exposes no reader for group members, so membership is asserted
 * through the effect it exists for rather than by reflecting on configuration.
 */
class CacheRegistrationTest extends AbstractCachingTestCase
{
    /**
     * @return array<string, array{0: string}>
     */
    public static function cacheIdentifiers(): array
    {
        return [
            'teaser' => [Extension::CACHE_TEASER],
            'list' => [Extension::CACHE_LIST],
            'search mask' => [Extension::CACHE_SEARCH_MASK],
        ];
    }

    #[Test]
    #[DataProvider('cacheIdentifiers')]
    public function cacheIsRegisteredAsVariableFrontend(string $identifier): void
    {
        self::assertInstanceOf(
            VariableFrontend::class,
            $this->get(CacheManager::class)->getCache($identifier)
        );
    }

    /**
     * The backend default is one hour. Entries are written without a lifetime,
     * so a lost `defaultLifetime` option would expire them while still valid.
     */
    #[Test]
    #[DataProvider('cacheIdentifiers')]
    public function cacheKeepsEntriesForAYear(string $identifier): void
    {
        $this->get(CacheManager::class)->getCache($identifier)->set('entry', 'rendered');

        $expires = $this->getConnectionPool()
            ->getConnectionForTable('cache_' . $identifier)
            ->select(['expires'], 'cache_' . $identifier, ['identifier' => 'entry'])
            ->fetchOne()
        ;

        self::assertIsNumeric($expires);
        // Well past the one-hour default, without pinning the exact second.
        self::assertGreaterThan(time() + 31535000, (int)$expires);
    }

    #[Test]
    #[DataProvider('cacheIdentifiers')]
    public function cacheIsFlushedWithThePagesGroup(string $identifier): void
    {
        $cacheManager = $this->get(CacheManager::class);
        $cache = $cacheManager->getCache($identifier);

        $cache->set('entry', 'rendered', ['tx_thuecat_tourist_attraction_1']);
        self::assertSame('rendered', $cache->get('entry'), 'Entry must be stored to prove anything.');

        $cacheManager->flushCachesInGroupByTags('pages', ['tx_thuecat_tourist_attraction_1']);

        self::assertFalse(
            $cache->get('entry'),
            'Flushing the pages group must discard this entry; the cache is outside the group.'
        );
    }
}
