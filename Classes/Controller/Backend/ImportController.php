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
    private const ITEMS_PER_PAGE = 5;

    public function __construct(
        private readonly ImportLogRepository $repository,
        private readonly PaginationFactory $paginationFactory,
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
