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

namespace WerkraumMedia\ThueCat\Tests\Functional\Removal;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use WerkraumMedia\ThueCat\Controller\Backend\RemovalController;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractBackendTestCase;

final class RemovalControllerTest extends AbstractBackendTestCase
{
    private ServerRequest $request;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var BackendUserAuthentication $backendUser set up by the base class */
        $backendUser = $GLOBALS['BE_USER'];
        $this->request = new ServerRequest('https://example.com/typo3/module/thuecat/removal')
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('normalizedParams', NormalizedParams::createFromRequest(new ServerRequest()))
            ->withAttribute('route', $this->get(Router::class)->getRoute('thuecat_removal'))
            ->withAttribute('module', $this->get(ModuleProvider::class)->getModule('thuecat_removal', $backendUser))
        ;
        $GLOBALS['TYPO3_REQUEST'] = $this->request;
    }

    #[Test]
    public function indexListsThePagesHoldingRootsPerSiteAndTheOriginsOfEachSite(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Roots.csv');

        $html = (string)$this->get(RemovalController::class)->indexAction($this->request)->getBody();

        $this->assertStringContainsString('Example site', $html);
        $this->assertStringContainsString('Folder A', $html);
        $this->assertStringContainsString('Folder B', $html);
        $this->assertStringContainsString('First site', $html);
        $this->assertStringContainsString('Folder C', $html);
        $this->assertStringContainsString('Pages outside any site', $html);
        $this->assertStringContainsString('Folder D', $html);
        $this->assertStringContainsString('Erfurt', $html);
        $this->assertStringContainsString('Weimar', $html);
        $this->assertStringNotContainsString('Folder without roots', $html);
    }

    #[Test]
    public function indexSaysSoWhenNoPageHoldsRoots(): void
    {
        $html = (string)$this->get(RemovalController::class)->indexAction($this->request)->getBody();

        $this->assertStringContainsString('No page holds POIs, places, trails or events.', $html);
    }
}
