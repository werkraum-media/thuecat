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
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use WerkraumMedia\ThueCat\Controller\Backend\ImportController;
use WerkraumMedia\ThueCat\Controller\Backend\OverviewController;

class Extension
{
    public const EXTENSION_KEY = 'thuecat';

    public const EXTENSION_NAME = 'Thuecat';

    public const TCA_SELECT_GROUP_IDENTIFIER = 'thuecat';

    public const PAGE_DOKTYPE_TOURIST_ATTRACTION = 950;

    public static function getLanguagePath(): string
    {
        return 'LLL:EXT:' . self::EXTENSION_KEY . '/Resources/Private/Language/';
    }

    public static function registerBackendModules(): void
    {
        ExtensionUtility::registerModule(
            self::EXTENSION_NAME,
            'thuecat',
            '',
            '',
            [],
            [
                'access' => 'user,group',
                'icon' => self::getIconPath() . 'ModuleGroup.svg',
                'labels' => self::getLanguagePath() . 'locallang_mod.xlf',
            ]
        );
        ExtensionUtility::registerModule(
            self::EXTENSION_NAME,
            'thuecat',
            'configurations',
            '',
            [
                OverviewController::class => 'index',
                ImportController::class => 'import',
            ],
            [
                'access' => 'user,group',
                'icon' => self::getIconPath() . 'ModuleConfigurations.svg',
                'labels' => self::getLanguagePath() . 'locallang_mod_configurations.xlf',
            ]
        );
        ExtensionUtility::registerModule(
            self::EXTENSION_NAME,
            'thuecat',
            'imports',
            '',
            [
                ImportController::class => 'index',
            ],
            [
                'access' => 'user,group',
                'icon' => self::getIconPath() . 'ModuleImports.svg',
                'labels' => self::getLanguagePath() . 'locallang_mod_imports.xlf',
            ]
        );
    }

    public static function registerConfig(): void
    {
        self::addCaching();
        self::addContentElements();
        self::addPageTypes();
        self::addIcons();
    }

    public static function getIconPath(): string
    {
        return 'EXT:' . self::EXTENSION_KEY . '/Resources/Public/Icons/';
    }

    private static function addContentElements(): void
    {
        $languagePath = self::getLanguagePath() . 'locallang_tca.xlf:tt_content';

        ExtensionManagementUtility::addPageTSConfig('
            mod.wizards.newContentElement.wizardItems.thuecat {
                header = ' . $languagePath . '.group
                show = *
                elements {
                    thuecat_tourist_attraction{
                        title = ' . $languagePath . '.thuecat_tourist_attraction
                        description =  ' . $languagePath . '.thuecat_tourist_attraction.description
                        iconIdentifier = tt_content_thuecat_tourist_attraction
                        tt_content_defValues {
                            CType = thuecat_tourist_attraction
                        }
                    }
                }
            }
        ');
    }

    private static function addPageTypes(): void
    {
        ExtensionManagementUtility::addUserTSConfig(
            "@import 'EXT:" . self::EXTENSION_KEY . "/Configuration/TSconfig/User/All.tsconfig'"
        );
    }

    private static function addIcons(): void
    {
        $iconFiles = GeneralUtility::getFilesInDir(GeneralUtility::getFileAbsFileName(self::getIconPath()));
        if (is_array($iconFiles) === false) {
            return;
        }

        $iconRegistry = GeneralUtility::makeInstance(IconRegistry::class);
        foreach ($iconFiles as $iconFile) {
            $iconRegistry->registerIcon(
                str_replace('.svg', '', $iconFile),
                SvgIconProvider::class,
                ['source' => self::getIconPath() . $iconFile]
            );
        }
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
