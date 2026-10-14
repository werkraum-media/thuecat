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

namespace WerkraumMedia\ThueCat\Typo3\EventListener;

use TYPO3\CMS\Backend\Context\PageContext;
use TYPO3\CMS\Backend\Controller\Event\ModifyPageLayoutContentEvent;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Authentication\AbstractUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;

/**
 * Shows the ThueCat page role in the page module of a page carrying routed
 * ThueCat plugins, and recommends the role those plugins need.
 *
 * thuecat registers no content element types, so the package defining them
 * maps each routed type to its role in page TSconfig:
 * `tx_thuecat.pageRoles.<CType> = <role>`. An unmapped type does not count.
 */
#[AsEventListener]
readonly class PageRoleHintListener
{
    protected const FIELD = 'tx_thuecat_page_role';

    public function __construct(
        protected ConnectionPool $connectionPool,
        protected TcaSchemaFactory $tcaSchemaFactory,
        protected BackendViewFactory $backendViewFactory,
        protected LanguageServiceFactory $languageServiceFactory,
    ) {
    }

    public function __invoke(ModifyPageLayoutContentEvent $event): void
    {
        $request = $event->getRequest();
        $pageContext = $request->getAttribute('pageContext');
        if (!$pageContext instanceof PageContext || $pageContext->pageRecord === null) {
            return;
        }

        $recommendedRoles = $this->recommendedRoles($pageContext);
        if ($recommendedRoles === []) {
            return;
        }

        $currentRole = $pageContext->pageRecord[self::FIELD] ?? '';
        $currentRole = is_string($currentRole) ? $currentRole : '';
        $mismatch = !in_array($currentRole, $recommendedRoles, true);
        $backendUser = $GLOBALS['BE_USER'] ?? null;
        $languageService = $this->languageServiceFactory->createFromUserPreferences(
            $backendUser instanceof AbstractUserAuthentication ? $backendUser : null
        );
        $labels = $this->roleLabels($languageService);

        $view = $this->backendViewFactory->create($request, ['werkraummedia/thuecat']);
        $view->assignMultiple([
            'state' => $mismatch ? ContextualFeedbackSeverity::WARNING : ContextualFeedbackSeverity::INFO,
            'currentRole' => $currentRole === ''
                ? $languageService->sL('LLL:EXT:thuecat/Resources/Private/Language/locallang.xlf:pageRole.hint.current.none')
                : ($labels[$currentRole] ?? $currentRole),
            'recommendedRole' => $mismatch
                ? implode(', ', array_map(static fn (string $role): string => $labels[$role] ?? $role, $recommendedRoles))
                : '',
        ]);

        $event->addHeaderContent($view->render('Backend/PageRoleHint'));
    }

    /**
     * @return list<string>
     */
    protected function recommendedRoles(PageContext $pageContext): array
    {
        $configuration = $pageContext->pageTsConfig['tx_thuecat'] ?? null;
        $mapping = is_array($configuration) ? ($configuration['pageRoles'] ?? null) : null;
        if (!is_array($mapping) || $mapping === []) {
            return [];
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tt_content');
        // Hidden content elements still belong to the page an editor arranges.
        $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());
        $contentTypes = $queryBuilder
            ->select('CType')
            ->distinct()
            ->from('tt_content')
            ->where($queryBuilder->expr()->eq(
                'pid',
                $queryBuilder->createNamedParameter($pageContext->pageId, Connection::PARAM_INT)
            ))
            ->executeQuery()
            ->fetchFirstColumn()
        ;

        $roles = [];
        foreach ($contentTypes as $contentType) {
            if (!is_string($contentType)) {
                continue;
            }
            $role = $mapping[$contentType] ?? null;
            if (is_string($role) && $role !== '') {
                $roles[] = $role;
            }
        }

        return array_values(array_unique($roles));
    }

    /**
     * @return array<string, string>
     */
    protected function roleLabels(LanguageService $languageService): array
    {
        $items = $this->tcaSchemaFactory->get('pages')->getField(self::FIELD)->getConfiguration()['items'] ?? [];
        if (!is_array($items)) {
            return [];
        }

        $labels = [];
        foreach ($items as $item) {
            if (!is_array($item) || !is_string($item['value'] ?? null) || !is_string($item['label'] ?? null)) {
                continue;
            }
            $labels[$item['value']] = $languageService->sL($item['label']);
        }

        return $labels;
    }
}
