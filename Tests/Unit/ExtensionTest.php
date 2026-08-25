<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Unit;

/*
 * Copyright (C) 2021 Daniel Siepmann <coding@daniel-siepmann.de>
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

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Cache\Backend\SimpleFileBackend;
use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use WerkraumMedia\ThueCat\Extension;
use WerkraumMedia\ThueCat\Import\Settings\ImportSetting;
use WerkraumMedia\ThueCat\Import\Vocabulary\VocabularyIndexCache;

class ExtensionTest extends TestCase
{
    /** @var mixed */
    private $cachingBackup;

    protected function setUp(): void
    {
        parent::setUp();
        // The suite runs with backupGlobals="false", so restore this by hand.
        $this->cachingBackup = $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching'] ?? null;
        unset($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']);
    }

    protected function tearDown(): void
    {
        if ($this->cachingBackup === null) {
            unset($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']);
        } else {
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching'] = $this->cachingBackup;
        }
        parent::tearDown();
    }

    /**
     * @return array<mixed>|null
     */
    private static function cacheConfiguration(string $identifier): ?array
    {
        $configuration = $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$identifier] ?? null;

        return is_array($configuration) ? $configuration : null;
    }

    #[Test]
    public function returnsLanguagePath(): void
    {
        self::assertSame('LLL:EXT:thuecat/Resources/Private/Language/', Extension::getLanguagePath());
    }

    #[Test]
    public function registersVocabularyCacheOutlivingItsOwnStalenessWindow(): void
    {
        Extension::registerExtLocalconfConfigConfig();

        $configuration = self::cacheConfiguration('thuecat_vocabulary');

        self::assertIsArray($configuration);
        self::assertSame(Typo3DatabaseBackend::class, $configuration['backend']);
        // The index decides its own staleness from a stored timestamp, so the
        // backend must not expire the entry first.
        $lifetime = $configuration['options']['defaultLifetime'] ?? null;
        self::assertIsInt($lifetime);
        self::assertGreaterThan(
            2 * VocabularyIndexCache::STALE_AFTER,
            $lifetime,
            'The cache entry must comfortably outlive the staleness window.'
        );
    }

    #[Test]
    public function registersImportCachesInSystemGroup(): void
    {
        Extension::registerExtLocalconfConfigConfig();

        self::assertSame(['system'], self::cacheConfiguration('thuecat_vocabulary')['groups'] ?? null);
        self::assertSame(['system'], self::cacheConfiguration('thuecat_fetchdata')['groups'] ?? null);
    }

    #[Test]
    public function registersFrontendCachesInPagesGroup(): void
    {
        Extension::registerExtLocalconfConfigConfig();

        self::assertSame(['pages'], self::cacheConfiguration(Extension::CACHE_TEASER)['groups'] ?? null);
    }

    #[Test]
    public function registersFetchDataCacheAlongsideTheVocabularyCache(): void
    {
        Extension::registerExtLocalconfConfigConfig();

        $configuration = self::cacheConfiguration('thuecat_fetchdata');

        self::assertIsArray($configuration);
        self::assertSame(Typo3DatabaseBackend::class, $configuration['backend']);
        self::assertSame(
            ImportSetting::FetchCacheLifetime->default(),
            $configuration['options']['defaultLifetime'] ?? null
        );
    }

    #[Test]
    public function keepsAnIntegratorsOwnBackendForTheVocabularyCache(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['thuecat_vocabulary'] = [
            'backend' => SimpleFileBackend::class,
        ];

        Extension::registerExtLocalconfConfigConfig();

        $configuration = self::cacheConfiguration('thuecat_vocabulary');

        self::assertIsArray($configuration);
        // Defended per key: an override survives, untouched keys are filled in.
        self::assertSame(SimpleFileBackend::class, $configuration['backend']);
        self::assertIsArray($configuration['options'] ?? null);
    }
}
