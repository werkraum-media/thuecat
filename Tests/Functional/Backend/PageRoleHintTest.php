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

namespace WerkraumMedia\ThueCat\Tests\Functional\Backend;

use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Backend\Context\PageContextFactory;
use TYPO3\CMS\Backend\Controller\Event\ModifyPageLayoutContentEvent;
use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Site\SiteFinder;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractImportTestCase;

class PageRoleHintTest extends AbstractImportTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->importPHPDataSet(__DIR__ . '/Fixtures/PageRoleHint.php');
    }

    #[Test]
    public function recommendsRoleForPluginPageWithoutRole(): void
    {
        $header = $this->pageModuleHeader(10);

        self::assertStringContainsString('callout-warning', $header);
        self::assertStringContainsString('Tourist attraction detail', $header);
    }

    #[Test]
    public function showsMatchingRoleWithoutWarning(): void
    {
        $header = $this->pageModuleHeader(20);

        self::assertStringContainsString('callout-info', $header);
        self::assertStringContainsString('Tourist attraction detail', $header);
        self::assertStringNotContainsString('callout-warning', $header);
    }

    #[Test]
    public function warnsOnMismatchingRoleShowingCurrentAndRecommended(): void
    {
        $header = $this->pageModuleHeader(30);

        self::assertStringContainsString('callout-warning', $header);
        self::assertStringContainsString('Trail detail', $header);
        self::assertStringContainsString('Tourist attraction detail', $header);
    }

    #[Test]
    public function showsNothingWithoutRoutedPlugin(): void
    {
        self::assertSame('', $this->pageModuleHeader(40));
    }

    #[Test]
    public function showsNothingForUnmappedContentElement(): void
    {
        self::assertSame('', $this->pageModuleHeader(50));
    }

    protected function pageModuleHeader(int $pageId): string
    {
        $request = (new ServerRequest('https://example.com/typo3/module/web/layout'))
            ->withQueryParams(['id' => $pageId])
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('normalizedParams', NormalizedParams::createFromRequest(new ServerRequest()))
            ->withAttribute('site', $this->get(SiteFinder::class)->getSiteByPageId($pageId))
            ->withAttribute('route', $this->get(Router::class)->getRoute('web_layout'))
        ;
        $backendUser = $GLOBALS['BE_USER'];
        self::assertInstanceOf(BackendUserAuthentication::class, $backendUser);
        $request = $request->withAttribute(
            'pageContext',
            $this->get(PageContextFactory::class)->createFromRequest($request, $pageId, $backendUser)
        );
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $event = $this->get(EventDispatcherInterface::class)->dispatch(
            new ModifyPageLayoutContentEvent($request, $this->get(ModuleTemplateFactory::class)->create($request))
        );
        self::assertInstanceOf(ModifyPageLayoutContentEvent::class, $event);

        return $event->getHeaderContent();
    }
}
