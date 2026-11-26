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
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfigurationOverviewRow;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportJobOverviewRow;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLog;
use WerkraumMedia\ThueCat\Domain\Repository\Backend\ImportConfigurationOverview;
use WerkraumMedia\ThueCat\Domain\Repository\Backend\ImportLogRepository;
use WerkraumMedia\ThueCat\Import\Job\ImportJob;
use WerkraumMedia\ThueCat\Import\Job\ImportJobs;

#[AsController]
class ImportController
{
    protected const LABELS = 'LLL:EXT:thuecat/Resources/Private/Language/locallang_mod_imports.xlf:';

    protected const TCA_LABELS = 'LLL:EXT:thuecat/Resources/Private/Language/locallang_tca.xlf:tx_thuecat_import_configuration.';

    /**
     * The presets the schedule field offers, by interval in seconds.
     */
    /**
     * The badge state of each outcome of a configuration's last finished job.
     */
    protected const OUTCOME_STATES = [
        'done' => 'success',
        'warnings' => 'warning',
        'errors' => 'danger',
        'failed' => 'danger',
        'stopped' => 'secondary',
    ];

    protected const SCHEDULE_PRESETS = [
        3600 => 'hourly',
        21600 => 'sixHourly',
        86400 => 'daily',
        604800 => 'weekly',
    ];

    public function __construct(
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        protected readonly ImportConfigurationOverview $configurationOverview,
        protected readonly SiteFinder $siteFinder,
        protected readonly LanguageServiceFactory $languageServiceFactory,
        protected readonly ImportLogRepository $importLogRepository,
        protected readonly UriBuilder $uriBuilder,
        protected readonly ImportJobs $importJobs,
        protected readonly FlashMessageService $flashMessageService,
        protected readonly PageRenderer $pageRenderer,
        protected readonly ViewFactoryInterface $viewFactory,
    ) {
    }

    public function indexAction(ServerRequestInterface $request): ResponseInterface
    {
        $sites = [];
        $outsideSites = null;
        $folds = $this->folds();
        foreach ($this->configurationOverview->bySite($this->getBackendUser()) as $siteIdentifier => $rows) {
            $configurations = array_map(fn (ImportConfigurationOverviewRow $row): array => $this->listed($row, $folds), $rows);
            if ($siteIdentifier === '') {
                $outsideSites = [
                    'identifier' => '',
                    'title' => $this->getLanguageService()->sL(self::LABELS . 'site.outside'),
                    'configurations' => $configurations,
                ];
                continue;
            }

            $sites[] = [
                'identifier' => $siteIdentifier,
                'title' => $this->pageTitle($this->siteFinder->getSiteByIdentifier($siteIdentifier)->getRootPageId()),
                'configurations' => $configurations,
            ];
        }

        usort($sites, static fn (array $a, array $b): int => strnatcasecmp($a['title'], $b['title']));
        if ($outsideSites !== null) {
            $sites[] = $outsideSites;
        }

        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/modal.js');
        $this->pageRenderer->loadJavaScriptModule('@werkraummedia/thuecat/imports-module.js');

        $view = $this->moduleTemplateFactory->create($request);
        $view->assign('sites', $sites);
        $view->assign('folds', $folds);

        return $view->renderResponse('Backend/Import/Index');
    }

    public function startAction(ServerRequestInterface $request): ResponseInterface
    {
        $body = $request->getParsedBody();
        $body = is_array($body) ? $body : [];

        $configurationUid = $this->postedUid($request, 'configuration');

        $jobUid = $this->mayManage($configurationUid) ? $this->importJobs->queueManual(
            $configurationUid,
            !empty($body['skipMedia']),
            !empty($body['bypassCache']),
            (int)$this->getBackendUser()->getUserId()
        ) : null;

        $this->notify($jobUid === null ? 'start.refused' : 'start.queued', $jobUid !== null);

        return $this->redirectToIndex();
    }

    public function stopAction(ServerRequestInterface $request): ResponseInterface
    {
        $job = $this->importJobs->find($this->postedUid($request, 'job'));

        $stoppable = $job instanceof ImportJob
            && in_array($job->status, ['queued', 'running'], true)
            && $this->mayManage($job->configurationUid);
        if ($stoppable) {
            $this->importJobs->stop($job->uid);
        }

        $this->notify($stoppable ? 'stop.stopping' : 'stop.refused', $stoppable);

        return $this->redirectToIndex();
    }

    /**
     * Each requested configuration's block, rendered as the index renders it, so the module's JavaScript swaps it in
     * whole: its runs, outcome, open job and buttons as well as jobs queued after the page was loaded.
     */
    public function statusAction(ServerRequestInterface $request): ResponseInterface
    {
        $query = $request->getQueryParams();
        $requested = GeneralUtility::intExplode(',', is_string($query['configurations'] ?? null) ? $query['configurations'] : '', true);
        $readable = $this->readableConfigurations();
        $folds = $this->folds();

        $blocks = [];
        foreach ($requested as $uid) {
            if (!isset($readable[$uid])) {
                continue;
            }

            $view = $this->viewFactory->create(new ViewFactoryData(
                templateRootPaths: ['EXT:thuecat/Resources/Private/Templates'],
                partialRootPaths: ['EXT:thuecat/Resources/Private/Partials'],
                request: $request,
            ));
            $view->assign('configuration', $this->listed($readable[$uid], $folds));
            $blocks[$uid] = $view->render('Backend/Import/Configuration');
        }

        return new JsonResponse(['configurations' => $blocks]);
    }

    protected function postedUid(ServerRequestInterface $request, string $field): int
    {
        $body = $request->getParsedBody();
        $value = is_array($body) ? $body[$field] ?? null : null;

        return is_numeric($value) ? (int)$value : 0;
    }

    /**
     * A posted form may name any configuration, so the listing's own read check decides.
     */
    protected function mayManage(int $configurationUid): bool
    {
        return $this->getBackendUser()->check('modules', 'thuecat_imports')
            && isset($this->readableConfigurations()[$configurationUid]);
    }

    /**
     * @return array<int, ImportConfigurationOverviewRow> keyed by uid
     */
    protected function readableConfigurations(): array
    {
        $readable = [];
        foreach ($this->configurationOverview->bySite($this->getBackendUser()) as $rows) {
            foreach ($rows as $row) {
                $readable[$row->uid] = $row;
            }
        }

        return $readable;
    }

    protected function notify(string $label, bool $succeeded): void
    {
        $this->flashMessageService->getMessageQueueByIdentifier()->enqueue(new FlashMessage(
            $this->getLanguageService()->sL(self::LABELS . $label . '.text'),
            $this->getLanguageService()->sL(self::LABELS . $label . '.title'),
            $succeeded ? ContextualFeedbackSeverity::OK : ContextualFeedbackSeverity::ERROR,
            true
        ));
    }

    protected function redirectToIndex(): ResponseInterface
    {
        return new RedirectResponse((string)$this->uriBuilder->buildUriFromRoute('thuecat_imports'), 303);
    }

    /**
     * The folded blocks the user opened or closed, as the module's JavaScript stores them. A block the user left
     * alone follows its default.
     *
     * @return array{open: list<string>, closed: list<string>}
     */
    protected function folds(): array
    {
        $moduleData = $this->getBackendUser()->uc['thuecat_imports'] ?? [];
        $moduleData = is_array($moduleData) ? $moduleData : [];

        return [
            'open' => GeneralUtility::trimExplode(',', is_string($moduleData['open'] ?? null) ? $moduleData['open'] : '', true),
            'closed' => GeneralUtility::trimExplode(',', is_string($moduleData['closed'] ?? null) ? $moduleData['closed'] : '', true),
        ];
    }

    /**
     * @param array{open: list<string>, closed: list<string>} $folds
     *
     * @return array{row: ImportConfigurationOverviewRow, type: string, schedule: string, outcome: array{label: string, state: string}|null, fold: array{default: string, open: bool}, jobs: list<array<string, mixed>>}
     */
    protected function listed(ImportConfigurationOverviewRow $row, array $folds): array
    {
        // An open job's status is what the row shows; an outcome from before it would contradict it.
        $outcomeState = $row->openJobUid > 0 ? null : self::OUTCOME_STATES[$row->lastOutcome] ?? null;
        // A last job that ended with errors shows its log without a click, unless the user closed it.
        $openByDefault = $outcomeState === 'danger';
        $fold = 'jobs-' . $row->uid;

        return [
            'row' => $row,
            'type' => $this->getLanguageService()->sL(self::TCA_LABELS . 'type.' . $row->type),
            'schedule' => $this->schedule($row->scheduleInterval),
            'outcome' => $outcomeState === null ? null : [
                'label' => $this->getLanguageService()->sL(self::LABELS . 'outcome.' . $row->lastOutcome),
                'state' => $outcomeState,
            ],
            'fold' => [
                'default' => $openByDefault ? 'open' : 'closed',
                'open' => in_array($fold, $folds['open'], true) || ($openByDefault && !in_array($fold, $folds['closed'], true)),
            ],
            'jobs' => array_map(fn (ImportJobOverviewRow $job): array => $this->listedJob($job, $folds['open']), $row->jobs),
        ];
    }

    /**
     * @param list<string> $openFolds
     *
     * @return array{row: ImportJobOverviewRow, trigger: string, user: string, log: ImportLog|null, logOpen: bool, progress: int, estimate: string}
     */
    protected function listedJob(ImportJobOverviewRow $job, array $openFolds): array
    {
        $user = $job->backendUser > 0 ? BackendUtility::getRecord('be_users', $job->backendUser)['username'] ?? '' : '';
        $log = $job->importLog > 0 ? $this->importLogRepository->findByUid($job->importLog) : null;

        return [
            'row' => $job,
            'trigger' => $this->getLanguageService()->sL(self::LABELS . 'job.trigger.' . $job->trigger),
            'user' => is_string($user) ? $user : '',
            'log' => $log instanceof ImportLog ? $log : null,
            'logOpen' => in_array('log-' . $job->uid, $openFolds, true),
            'progress' => $job->totalItems > 0 ? intdiv(100 * $job->finishedItems, $job->totalItems) : 0,
            'estimate' => $job->estimate === null ? '' : $this->estimate($job->estimate),
        ];
    }

    /**
     * The label of each unit pair orders and names its units, as a translation needs.
     */
    protected function estimate(int $seconds): string
    {
        $units = array_slice(array_filter([
            'd' => intdiv($seconds, 86400),
            'h' => intdiv($seconds, 3600) % 24,
            'min' => intdiv($seconds, 60) % 60,
            's' => $seconds % 60,
        ]), 0, 2);

        return sprintf($this->getLanguageService()->sL(self::LABELS . 'job.estimate.' . implode('.', array_keys($units))), ...array_values($units));
    }

    protected function schedule(int $interval): string
    {
        $languageService = $this->getLanguageService();
        if ($interval === 0) {
            return $languageService->sL(self::LABELS . 'schedule.manual');
        }

        if (array_key_exists($interval, self::SCHEDULE_PRESETS)) {
            return $languageService->sL(self::TCA_LABELS . 'schedule_interval.' . self::SCHEDULE_PRESETS[$interval]);
        }

        return sprintf($languageService->sL(self::LABELS . 'schedule.minutes'), intdiv($interval, 60));
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
