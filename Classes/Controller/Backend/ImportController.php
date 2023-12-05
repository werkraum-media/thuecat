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
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Extbase\Annotation\IgnoreValidation;
use WerkraumMedia\ThueCat\Domain\Import\Importer;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfiguration;
use WerkraumMedia\ThueCat\Domain\Repository\Backend\ImportLogRepository;
use WerkraumMedia\ThueCat\Extension;
use WerkraumMedia\ThueCat\Typo3Wrapper\TranslationService;

class ImportController extends AbstractController
{
    public function __construct(
        private readonly Importer $importer,
        private readonly ImportLogRepository $repository,
        private readonly TranslationService $translation
    ) {
    }

    public function indexAction(): ResponseInterface
    {
        $this->moduleTemplate->assignMultiple([
            'imports' => $this->repository->findAll(),
        ]);

        return $this->htmlResponse();
    }

    #[IgnoreValidation(['argumentName' => 'importConfiguration'])]
    public function importAction(ImportConfiguration $importConfiguration): ResponseInterface
    {
        $importLog = $this->importer->importConfiguration($importConfiguration);

        if ($importLog->hasErrors()) {
            $this->createImportErrorFlashMessage($importConfiguration);
        } else {
            $this->createImportDoneFlashMessage($importConfiguration);
        }

        return $this->redirect('index', 'Backend\Configuration');
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
            ContextualFeedbackSeverity::ERROR
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
            ContextualFeedbackSeverity::OK
        );
    }
}
