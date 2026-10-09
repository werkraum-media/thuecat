<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS extension "thuecat".
 *
 * Copyright (C) werkraum-media <https://werkraum-media.de/>
 * Copyright (C) 2021 Daniel Siepmann <coding@daniel-siepmann.de>
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
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use WerkraumMedia\ThueCat\Domain\Repository\Backend\ImportLogRepository;
use WerkraumMedia\ThueCat\Pagination\PaginationFactory;

class ImportController extends ActionController
{
    protected const ITEMS_PER_PAGE = 5;

    public function __construct(
        protected readonly ImportLogRepository $repository,
        protected readonly PaginationFactory $paginationFactory,
        protected readonly ModuleTemplateFactory $moduleTemplateFactory
    ) {
    }

    protected function initializeModuleTemplate(
        ServerRequestInterface $request,
    ): ModuleTemplate {
        return $this->moduleTemplateFactory->create($request);
    }

    public function indexAction(int $currentPage = 1): ResponseInterface
    {
        $view = $this->initializeModuleTemplate($this->request);
        $view->assignMultiple([
            'imports' => $this->paginationFactory->withFixedItemsPerPage(
                $this->repository->findAll(),
                $currentPage,
                self::ITEMS_PER_PAGE
            ),
        ]);

        return $view->renderResponse('Backend/Import/Index');
    }
}
