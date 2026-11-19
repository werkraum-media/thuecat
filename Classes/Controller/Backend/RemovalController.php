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
use Symfony\Component\Messenger\MessageBusInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use WerkraumMedia\ThueCat\Removal\Message\RemovalStarted;
use WerkraumMedia\ThueCat\Removal\RemovalJobs;
use WerkraumMedia\ThueCat\Removal\RemovalJobState;
use WerkraumMedia\ThueCat\Removal\RemovalSelection;
use WerkraumMedia\ThueCat\Removal\RootOverview;
use WerkraumMedia\ThueCat\Removal\RootTables;
use WerkraumMedia\ThueCat\Removal\Scope;
use WerkraumMedia\ThueCat\Removal\Selection;

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
        protected readonly UriBuilder $uriBuilder,
        protected readonly RemovalSelection $removalSelection,
        protected readonly RemovalJobs $removalJobs,
        protected readonly MessageBusInterface $messageBus,
        protected readonly FlashMessageService $flashMessageService,
    ) {
    }

    public function indexAction(ServerRequestInterface $request): ResponseInterface
    {
        $sites = [];
        foreach ($this->rootOverview->pagesBySite($this->getBackendUser()) as $siteIdentifier => $pages) {
            if ($siteIdentifier === '') {
                $sites[] = [
                    'title' => $this->getLanguageService()->sL(self::LABELS . 'site.outside'),
                    'rootPageId' => 0,
                    'pages' => $pages,
                    'origins' => [],
                ];
                continue;
            }

            $rootPageId = $this->siteFinder->getSiteByIdentifier($siteIdentifier)->getRootPageId();
            $sites[] = [
                'title' => $this->pageTitle($rootPageId),
                'rootPageId' => $rootPageId,
                'pages' => $pages,
                'origins' => $this->rootOverview->originsOfSite($rootPageId),
            ];
        }

        $overview = $this->removalJobs->overview();

        $view = $this->moduleTemplateFactory->create($request);
        $view->assignMultiple([
            'sites' => $sites,
            'kinds' => $this->kinds(),
            'finishedJobs' => $this->listed($overview->finished),
            'openJobs' => $this->listed($overview->open),
            'stalledJobs' => $overview->stalledJobs,
            'cleanupDue' => $overview->cleanupDue,
        ]);

        return $view->renderResponse('Backend/Removal/Index');
    }

    public function confirmAction(ServerRequestInterface $request): ResponseInterface
    {
        $selection = $this->selectionFrom($request->getQueryParams());
        if (!$selection instanceof Selection) {
            return $this->redirectToIndex();
        }

        $kinds = array_column($this->kinds(), 'label', 'table');
        $originTitle = '';
        if ($selection->originConfigurationUid > 0) {
            foreach ($this->rootOverview->originsOfSite($selection->scopePage) as $origin) {
                if ($origin->configurationUid === $selection->originConfigurationUid) {
                    $originTitle = $origin->title;
                }
            }
        }

        $view = $this->moduleTemplateFactory->create($request);
        $view->assignMultiple([
            'selection' => $selection,
            'parameters' => $selection->toParameters(),
            'kind' => $kinds[$selection->rootTable] ?? '',
            'pageTitle' => $this->pageTitle($selection->scopePage),
            'originTitle' => $originTitle,
            'roots' => $this->removalSelection->count($selection),
        ]);

        return $view->renderResponse('Backend/Removal/Confirm');
    }

    public function startAction(ServerRequestInterface $request): ResponseInterface
    {
        $body = $request->getParsedBody();
        $body = is_array($body) ? $body : [];

        $selection = $this->selectionFrom($body);
        if (!$selection instanceof Selection) {
            return $this->redirectToIndex();
        }

        $jobUid = $this->removalJobs->queue(
            $selection,
            !empty($body['removeFiles']),
            !empty($body['updateReferenceIndex']),
            (int)$this->getBackendUser()->getUserId()
        );
        $this->messageBus->dispatch(new RemovalStarted($jobUid));

        $this->flashMessageService->getMessageQueueByIdentifier()->enqueue(new FlashMessage(
            $this->getLanguageService()->sL(self::LABELS . 'queued.text'),
            $this->getLanguageService()->sL(self::LABELS . 'queued.title'),
            ContextualFeedbackSeverity::OK,
            true
        ));

        return $this->redirectToIndex();
    }

    /**
     * @param array<mixed> $parameters
     */
    protected function selectionFrom(array $parameters): ?Selection
    {
        $selection = $parameters['selection'] ?? null;

        return is_array($selection) ? Selection::fromParameters($selection) : null;
    }

    protected function redirectToIndex(): ResponseInterface
    {
        return new RedirectResponse((string)$this->uriBuilder->buildUriFromRoute('thuecat_removal'), 303);
    }

    /**
     * @return list<array{table: string, label: string}>
     */
    protected function kinds(): array
    {
        $kinds = [];
        foreach (RootTables::ALL as $table) {
            $kinds[] = [
                'table' => $table,
                'label' => $this->getLanguageService()->sL($this->tcaSchemaFactory->get($table)->getTitle()),
            ];
        }

        return $kinds;
    }

    /**
     * @param list<RemovalJobState> $states
     *
     * @return list<array{state: RemovalJobState, selection: string, user: string}>
     */
    protected function listed(array $states): array
    {
        $jobs = [];
        foreach ($states as $state) {
            $user = BackendUtility::getRecord('be_users', $state->job->backendUser)['username'] ?? '';
            $jobs[] = [
                'state' => $state,
                'selection' => $this->describe($state->job->selection),
                'user' => is_string($user) ? $user : '',
            ];
        }

        return $jobs;
    }

    protected function describe(Selection $selection): string
    {
        $languageService = $this->getLanguageService();
        if ($selection->originConfigurationUid > 0) {
            return sprintf(
                $languageService->sL(self::LABELS . 'job.scope.origin'),
                $selection->originConfigurationUid,
                $this->pageTitle($selection->scopePage)
            );
        }

        $kind = $languageService->sL($this->tcaSchemaFactory->get($selection->rootTable)->getTitle());

        return match ($selection->scope) {
            Scope::Folder => sprintf(
                $languageService->sL(self::LABELS . 'confirm.scope.folder'),
                $kind,
                $this->pageTitle($selection->scopePage),
                $selection->scopePage
            ),
            Scope::Site => sprintf(
                $languageService->sL(self::LABELS . 'confirm.scope.site'),
                $kind,
                $this->pageTitle($selection->scopePage)
            ),
            Scope::Instance => sprintf($languageService->sL(self::LABELS . 'confirm.scope.instance'), $kind),
        };
    }

    protected function pageTitle(int $pageUid): string
    {
        return BackendUtility::getRecordTitle('pages', BackendUtility::getRecord('pages', $pageUid) ?? []);
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
