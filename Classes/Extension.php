<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat;

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

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use WerkraumMedia\ThueCat\Controller\Backend\ImportController;
use WerkraumMedia\ThueCat\Controller\Backend\OverviewController;

class Extension
{
    public const EXTENSION_KEY = 'thuecat';

    public const EXTENSION_NAME = 'Thuecat';

    public const TT_CONTENT_GROUP = 'thuecat';

    public static function getLanguagePath(): string
    {
        return 'LLL:EXT:' . self::EXTENSION_KEY . '/Resources/Private/Language/';
    }

    public static function registerBackendModules(): void
    {
        ExtensionUtility::registerModule(
            self::EXTENSION_NAME,
            'site',
            'thuecat',
            '',
            [
                OverviewController::class => 'index',
                ImportController::class => 'import, index',
            ],
            [
                'access' => 'user,group',
                'icon' => 'EXT:' . self::EXTENSION_KEY . '/Resources/Public/Icons/module.svg',
                'labels' => self::getLanguagePath() . 'locallang_mod.xlf',
            ]
        );
    }

    public static function registerConfig(): void
    {
        $languagePath = self::getLanguagePath() . 'locallang_tca.xlf:tt_content';

        // TODO: Add Icon
        ExtensionManagementUtility::addPageTSConfig('
            mod.wizards.newContentElement.wizardItems.thuecat {
                header = ' . $languagePath . '.group
                show = *
                elements {
                    thuecat_tourist_attraction{
                        title = ' . $languagePath . '.thuecat_tourist_attraction
                        description =  ' . $languagePath . '.thuecat_tourist_attraction.description
                        tt_content_defValues {
                            CType = thuecat_tourist_attraction
                        }
                    }
                }
            }
        ');
    }
}
