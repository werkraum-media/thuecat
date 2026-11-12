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

namespace WerkraumMedia\ThueCat\Tests\Unit\Import;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Cache\Backend\SimpleFileBackend;
use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use WerkraumMedia\ThueCat\Import\ImportCacheRegistration;
use WerkraumMedia\ThueCat\Import\Settings\ImportSetting;
use WerkraumMedia\ThueCat\Import\Vocabulary\VocabularyIndexCache;

final class ImportCacheRegistrationTest extends TestCase
{
    private mixed $confVarsBackup;

    protected function setUp(): void
    {
        parent::setUp();
        // The suite runs with backupGlobals="false", so restore this by hand.
        $this->confVarsBackup = $GLOBALS['TYPO3_CONF_VARS'] ?? null;
        $GLOBALS['TYPO3_CONF_VARS'] = [];
    }

    protected function tearDown(): void
    {
        $GLOBALS['TYPO3_CONF_VARS'] = $this->confVarsBackup;

        parent::tearDown();
    }

    /**
     * @return array<mixed>|null
     */
    private function cacheConfiguration(string $identifier): ?array
    {
        $configuration = $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$identifier] ?? null;

        return is_array($configuration) ? $configuration : null;
    }

    #[Test]
    public function registersVocabularyCacheOutlivingItsOwnStalenessWindow(): void
    {
        ImportCacheRegistration::register();

        $configuration = $this->cacheConfiguration('thuecat_vocabulary');

        $this->assertIsArray($configuration);
        $this->assertSame(Typo3DatabaseBackend::class, $configuration['backend']);
        // The index decides its own staleness from a stored timestamp, so the
        // backend must not expire the entry first.
        $lifetime = $configuration['options']['defaultLifetime'] ?? null;
        $this->assertIsInt($lifetime);
        $this->assertGreaterThan(2 * VocabularyIndexCache::STALE_AFTER, $lifetime, 'The cache entry must comfortably outlive the staleness window.');
    }

    #[Test]
    public function registersImportCachesInSystemGroup(): void
    {
        ImportCacheRegistration::register();

        $this->assertSame(['system'], $this->cacheConfiguration('thuecat_vocabulary')['groups'] ?? null);
        $this->assertSame(['system'], $this->cacheConfiguration('thuecat_fetchdata')['groups'] ?? null);
    }

    #[Test]
    public function registersFetchDataCacheAlongsideTheVocabularyCache(): void
    {
        ImportCacheRegistration::register();

        $configuration = $this->cacheConfiguration('thuecat_fetchdata');

        $this->assertIsArray($configuration);
        $this->assertSame(Typo3DatabaseBackend::class, $configuration['backend']);
        $this->assertSame(ImportSetting::FetchCacheLifetime->default(), $configuration['options']['defaultLifetime'] ?? null);
    }

    #[Test]
    public function keepsAnIntegratorsOwnOptionsForTheFetchDataCache(): void
    {
        $GLOBALS['TYPO3_CONF_VARS'] = [
            'SYS' => [
                'caching' => [
                    'cacheConfigurations' => [
                        'thuecat_fetchdata' => [
                            'options' => [
                                'defaultLifetime' => 60,
                                'compression' => true,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        ImportCacheRegistration::register();

        $this->assertSame(
            ['defaultLifetime' => 60, 'compression' => true],
            $this->cacheConfiguration('thuecat_fetchdata')['options'] ?? null
        );
    }

    #[Test]
    public function keepsAnIntegratorsOwnBackendForTheVocabularyCache(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['thuecat_vocabulary'] = [
            'backend' => SimpleFileBackend::class,
        ];

        ImportCacheRegistration::register();

        $configuration = $this->cacheConfiguration('thuecat_vocabulary');

        $this->assertIsArray($configuration);
        // Defended per key: an override survives, untouched keys are filled in.
        $this->assertSame(SimpleFileBackend::class, $configuration['backend']);
        $this->assertIsArray($configuration['options'] ?? null);
    }
}
