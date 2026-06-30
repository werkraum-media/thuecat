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
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use WerkraumMedia\ThueCat\Domain\Repository\Backend\ImportConfigurationRepository;
use WerkraumMedia\ThueCat\Domain\Repository\Backend\OrganisationRepository;

class ConfigurationController extends AbstractController
{
    public function __construct(
        private readonly OrganisationRepository $organisationRepository,
        private readonly ImportConfigurationRepository $importConfigurationRepository
    ) {
    }

    public function indexAction(): ResponseInterface
    {
        $view = $this->initializeModuleTemplate($this->request);
        /** @var SiteInterface $site */
        $site = $this->request->getAttribute('site');
        if ($site instanceof NullSite) {
            $view->assign('noSite', true);
        } else {
            $importConfigurationStoragePid = 0;
            if ($site instanceof Site) {
                $configuration = $site->getConfiguration();
                if (is_array($configuration['settings']) && is_array($configuration['settings']['page']) && is_array($configuration['settings']['page']['pid']) && $configuration['settings']['page']['pid']['import_configuration']) {
                    $importConfigurationStoragePid = $configuration['settings']['page']['pid']['import_configuration'];
                }
            }

            $view->assignMultiple([
                'importConfigurations' => $this->importConfigurationRepository->findAll(),
                'organisations' => $this->organisationRepository->findAll(),
                'pid' => $importConfigurationStoragePid,
            ]);
        }

        return $view->renderResponse('Backend/Configuration/Index');
    }
}
