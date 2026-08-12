<?php

declare(strict_types=1);

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

namespace WerkraumMedia\ThueCat;

use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use WerkraumMedia\ThueCat\Import\Settings\ImportSetting;

class Extension
{
    final public const EXTENSION_KEY = 'thuecat';

    final public const EXTENSION_NAME = 'Thuecat';

    final public const TCA_SELECT_GROUP_IDENTIFIER = 'thuecat';

    final public const PAGE_DOKTYPE_TOURIST_ATTRACTION = 950;

    final public const CACHE_TEASER = 'tx_thuecat_teaser';

    final public const CACHE_LIST = 'tx_thuecat_list';

    final public const CACHE_SEARCH_MASK = 'tx_thuecat_searchmask';

    /** One year; invalidation is by tag. */
    private const FRONTEND_CACHE_LIFETIME = 31536000;

    private const FRONTEND_CACHE_IDENTIFIERS = [
        self::CACHE_TEASER,
        self::CACHE_LIST,
        self::CACHE_SEARCH_MASK,
    ];

    public static function getLanguagePath(): string
    {
        return 'LLL:EXT:' . self::EXTENSION_KEY . '/Resources/Private/Language/';
    }

    public static function registerExtLocalconfConfigConfig(): void
    {
        self::addCaching();
    }

    public static function getIconPath(): string
    {
        return 'EXT:' . self::EXTENSION_KEY . '/Resources/Public/Icons/';
    }

    private static function addCaching(): void
    {
        $cacheIdentifier = 'thuecat_fetchdata';
        if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$cacheIdentifier] ?? null)) {
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$cacheIdentifier] = [];
        }
        // Persistent so an aborted run's responses survive into the retry. Runs
        // in ext_localconf, before any configuration is known, so the lifetime
        // here is the code default; a per-configuration value is applied per
        // entry at write time instead.
        if (!isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$cacheIdentifier]['backend'])) {
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$cacheIdentifier]['backend'] = Typo3DatabaseBackend::class;
        }
        if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$cacheIdentifier]['options'] ?? null)) {
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$cacheIdentifier]['options'] = [];
        }
        if (!isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$cacheIdentifier]['options']['defaultLifetime'])) {
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$cacheIdentifier]['options']['defaultLifetime'] = ImportSetting::FetchCacheLifetime->default();
        }

        foreach (self::FRONTEND_CACHE_IDENTIFIERS as $identifier) {
            self::addFrontendCache($identifier);
        }
    }

    /**
     * Group `pages` is load-bearing: DataHandler flushes that whole group by the
     * tags it emits on save, which is the entire invalidation mechanism.
     */
    private static function addFrontendCache(string $identifier): void
    {
        if (isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$identifier])) {
            return;
        }

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$identifier] = [
            'frontend' => VariableFrontend::class,
            'backend' => Typo3DatabaseBackend::class,
            'groups' => ['pages'],
            'options' => [
                'defaultLifetime' => self::FRONTEND_CACHE_LIFETIME,
            ],
        ];
    }
}
