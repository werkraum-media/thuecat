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

use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Extbase\Annotation as Extbase;
use WerkraumMedia\ThueCat\Domain\Import\Importer;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfiguration;
use WerkraumMedia\ThueCat\Domain\Repository\Backend\ImportLogRepository;
use WerkraumMedia\ThueCat\Extension;
use WerkraumMedia\ThueCat\Typo3Wrapper\TranslationService;

class ImportController extends AbstractController
{
    /**
     * @var Importer
     */
    private $importer;

    /**
     * @var ImportLogRepository
     */
    private $repository;

    /**
     * @var TranslationService
     */
    private $translation;

    public function __construct(
        Importer $importer,
        ImportLogRepository $repository,
        TranslationService $translation
    ) {
        $this->importer = $importer;
        $this->repository = $repository;
        $this->translation = $translation;
    }

    public function indexAction(): void
    {
        $this->view->assignMultiple([
            'imports' => $this->repository->findAll(),
        ]);
    }

    /**
     * @Extbase\IgnoreValidation("importConfiguration")
     */
    public function importAction(ImportConfiguration $importConfiguration): void
    {
        $importLog = $this->importer->importConfiguration($importConfiguration);

        if ($importLog->hasErrors()) {
            $this->createImportErrorFlashMessage($importConfiguration);
        } else {
            $this->createImportDoneFlashMessage($importConfiguration);
        }

        $this->redirect('index', 'Backend\Configuration');
    }

    private function createImportErrorFlashMessage(ImportConfiguration $importConfiguration): void
    {
        $this->addFlashMessage(
            $this->translation->translate(
                'controller.backend.import.import.error.text',
                Extension::EXTENSION_NAME,
                [$importConfiguration->getTitle()]
            ),
            $this->translation->translate(
                'controller.backend.import.import.error.title',
                Extension::EXTENSION_NAME
            ),
            AbstractMessage::ERROR
        );
    }

    private function createImportDoneFlashMessage(ImportConfiguration $importConfiguration): void
    {
        $this->addFlashMessage(
            $this->translation->translate(
                'controller.backend.import.import.success.text',
                Extension::EXTENSION_NAME,
                [$importConfiguration->getTitle()]
            ),
            $this->translation->translate(
                'controller.backend.import.import.success.title',
                Extension::EXTENSION_NAME
            ),
            AbstractMessage::OK
        );
    }
}
