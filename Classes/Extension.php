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

use TYPO3\CMS\Core\Cache\Backend\TransientMemoryBackend;
use TYPO3\CMS\Core\DataHandling\PageDoktypeRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Extension
{
    final public const EXTENSION_KEY = 'thuecat';

    final public const EXTENSION_NAME = 'Thuecat';

    final public const TCA_SELECT_GROUP_IDENTIFIER = 'thuecat';

    final public const PAGE_DOKTYPE_TOURIST_ATTRACTION = 950;

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
        if (!isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$cacheIdentifier]['backend'])) {
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$cacheIdentifier]['backend'] = TransientMemoryBackend::class;
        }
    }
}
