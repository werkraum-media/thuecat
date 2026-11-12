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

namespace WerkraumMedia\ThueCat\Frontend\Cache;

use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;

class FrontendCacheRegistration
{
    final public const string TEASER = 'tx_thuecat_teaser';

    final public const string LIST = 'tx_thuecat_list';

    final public const string SEARCH_MASK = 'tx_thuecat_searchmask';

    /** One year; invalidation is by tag. */
    protected const LIFETIME = 31536000;

    public static function register(): void
    {
        foreach ([self::TEASER, self::LIST, self::SEARCH_MASK] as $identifier) {
            self::addCache($identifier);
        }
    }

    /**
     * DataHandler flushes only 'pages' group by the
     * tags it emits on save, which is the entire invalidation mechanism.
     */
    protected static function addCache(string $identifier): void
    {
        if (isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$identifier])) {
            return;
        }

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$identifier] = [
            'frontend' => VariableFrontend::class,
            'backend' => Typo3DatabaseBackend::class,
            'groups' => ['pages'],
            'options' => [
                'defaultLifetime' => self::LIFETIME,
            ],
        ];
    }
}
