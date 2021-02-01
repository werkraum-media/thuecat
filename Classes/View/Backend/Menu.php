<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\View\Backend;

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

use TYPO3\CMS\Backend\Template\Components\MenuRegistry;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use WerkraumMedia\ThueCat\Controller\Backend\ImportController;
use WerkraumMedia\ThueCat\Controller\Backend\OverviewController;
use WerkraumMedia\ThueCat\Extension;
use WerkraumMedia\ThueCat\Typo3Wrapper\TranslationService;

class Menu
{
    private TranslationService $translation;

    public function __construct(
        TranslationService $translation
    ) {
        $this->translation = $translation;
    }

    public function addMenu(
        MenuRegistry $registry,
        UriBuilder $uriBuilder,
        string $controllerClassName
    ): void {
        $menu = $registry->makeMenu();
        $menu->setIdentifier('action');

        $menuItem = $menu->makeMenuItem();
        $menuItem->setTitle($this->translation->translate('module.overview.headline', Extension::EXTENSION_NAME));
        $menuItem->setHref($uriBuilder->reset()->uriFor('index', [], 'Backend\Overview'));
        $menuItem->setActive($controllerClassName === OverviewController::class);
        $menu->addMenuItem($menuItem);

        $menuItem = $menu->makeMenuItem();
        $menuItem->setTitle($this->translation->translate('module.imports.headline', Extension::EXTENSION_NAME));
        $menuItem->setHref($uriBuilder->reset()->uriFor('index', [], 'Backend\Import'));
        $menuItem->setActive($controllerClassName === ImportController::class);
        $menu->addMenuItem($menuItem);

        $registry->addMenu($menu);
    }
}
