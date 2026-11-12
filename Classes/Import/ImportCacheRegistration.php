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

namespace WerkraumMedia\ThueCat\Import;

use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use WerkraumMedia\ThueCat\Import\Settings\ImportSetting;

class ImportCacheRegistration
{
    /** One year. The index expires itself; this only stops the backend doing it first. */
    protected const VOCABULARY_LIFETIME = 31536000;

    public static function register(): void
    {
        self::addCache('thuecat_fetchdata', ImportSetting::FetchCacheLifetime->default(), ['system']);
        self::addCache('thuecat_vocabulary', self::VOCABULARY_LIFETIME, ['system']);
    }

    /**
     * Each key is defended separately so an integrator overriding one of them
     * keeps that override rather than having the whole entry replaced.
     *
     * @param list<string> $groups
     */
    protected static function addCache(string $cacheIdentifier, int $lifetime, array $groups): void
    {
        if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$cacheIdentifier] ?? null)) {
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$cacheIdentifier] = [];
        }

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$cacheIdentifier]['backend'] ??= Typo3DatabaseBackend::class;
        if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$cacheIdentifier]['groups'] ?? null)) {
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$cacheIdentifier]['groups'] = $groups;
        }

        if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$cacheIdentifier]['options'] ?? null)) {
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$cacheIdentifier]['options'] = [];
        }

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$cacheIdentifier]['options']['defaultLifetime'] ??= $lifetime;
    }
}
