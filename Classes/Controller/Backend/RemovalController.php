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

namespace WerkraumMedia\ThueCat\Controller\Backend;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Site\SiteFinder;
use WerkraumMedia\ThueCat\Removal\RootOverview;
use WerkraumMedia\ThueCat\Removal\RootTables;

#[AsController]
class RemovalController
{
    protected const LABELS = 'LLL:EXT:thuecat/Resources/Private/Language/locallang_mod_removal.xlf:';

    public function __construct(
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        protected readonly RootOverview $rootOverview,
        protected readonly SiteFinder $siteFinder,
        protected readonly TcaSchemaFactory $tcaSchemaFactory,
        protected readonly LanguageServiceFactory $languageServiceFactory,
    ) {
    }

    public function indexAction(ServerRequestInterface $request): ResponseInterface
    {
        $sites = [];
        foreach ($this->rootOverview->pagesBySite($this->getBackendUser()) as $siteIdentifier => $pages) {
            if ($siteIdentifier === '') {
                $sites[] = [
                    'title' => $this->getLanguageService()->sL(self::LABELS . 'site.outside'),
                    'pages' => $pages,
                    'origins' => [],
                ];
                continue;
            }

            $rootPageId = $this->siteFinder->getSiteByIdentifier($siteIdentifier)->getRootPageId();
            $sites[] = [
                'title' => BackendUtility::getRecordTitle('pages', BackendUtility::getRecord('pages', $rootPageId) ?? []),
                'pages' => $pages,
                'origins' => $this->rootOverview->originsOfSite($rootPageId),
            ];
        }

        $kinds = [];
        foreach (RootTables::ALL as $table) {
            $kinds[] = $this->getLanguageService()->sL($this->tcaSchemaFactory->get($table)->getTitle());
        }

        $view = $this->moduleTemplateFactory->create($request);
        $view->assignMultiple([
            'sites' => $sites,
            'kinds' => $kinds,
        ]);

        return $view->renderResponse('Backend/Removal/Index');
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        /** @var BackendUserAuthentication $backendUser the backend routing only dispatches to a module with a user */
        $backendUser = $GLOBALS['BE_USER'];

        return $backendUser;
    }

    protected function getLanguageService(): LanguageService
    {
        return $this->languageServiceFactory->createFromUserPreferences($this->getBackendUser());
    }
}
