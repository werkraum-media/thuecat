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

namespace WerkraumMedia\ThueCat\Tests\Functional\Routing;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractFrontendTestCase;

class DetailRoutingTest extends AbstractFrontendTestCase
{
    protected function getDataSetFileName(): string
    {
        return 'RecordsForRouting.php';
    }

    protected function getRenderingTypoScript(): string
    {
        return 'RoutingRendering.typoscript';
    }

    #[Test]
    public function listLinksToAttractionBySlugPath(): void
    {
        $body = (string)$this->executeFrontendSubRequest($this->pageRequest(40))->getBody();

        self::assertStringContainsString('href="/show/gemeindebrunnen-weimar"', $body);
    }

    #[Test]
    public function listLinksToTranslatedAttractionBySlugPath(): void
    {
        $body = (string)$this->executeFrontendSubRequest($this->pageRequest(40, 1))->getBody();

        self::assertStringContainsString('href="/en/show/community-fountain-weimar"', $body);
    }

    #[Test]
    public function listLinksToTrailBySlugPath(): void
    {
        $body = (string)$this->executeFrontendSubRequest($this->pageRequest(40))->getBody();

        self::assertStringContainsString('href="/trail/rennsteig"', $body);
    }

    #[Test]
    public function slugPathRendersAttraction(): void
    {
        $request = new InternalRequest('http://localhost/show/gemeindebrunnen-weimar');

        $result = $this->executeFrontendSubRequest($request);

        self::assertSame(200, $result->getStatusCode());
        self::assertStringContainsString('<h2>Gemeindebrunnen Weimar</h2>', (string)$result->getBody());
    }

    #[Test]
    public function translatedSlugPathRendersTranslatedAttraction(): void
    {
        $request = new InternalRequest('http://localhost/en/show/community-fountain-weimar');

        $result = $this->executeFrontendSubRequest($request);

        self::assertSame(200, $result->getStatusCode());
        self::assertStringContainsString('<h2>Community Fountain Weimar</h2>', (string)$result->getBody());
    }

    #[Test]
    public function slugPathRendersTrail(): void
    {
        $request = new InternalRequest('http://localhost/trail/rennsteig');

        $result = $this->executeFrontendSubRequest($request);

        self::assertSame(200, $result->getStatusCode());
        self::assertStringContainsString('Rennsteig', (string)$result->getBody());
    }

    #[Test]
    public function unknownSlugIsNotFound(): void
    {
        $request = new InternalRequest('http://localhost/show/gibt-es-nicht');

        self::assertSame(404, $this->executeFrontendSubRequest($request)->getStatusCode());
    }

    // Core: with uniqueInSite on the slug, PersistedAliasMapper only resolves records inside the current site.
    #[Test]
    public function foreignSiteSlugIsNotFound(): void
    {
        $request = new InternalRequest('http://localhost/show/wartburg');

        self::assertSame(404, $this->executeFrontendSubRequest($request)->getStatusCode());
    }

    // Evidence: a translated slug under another language's base. Accepted, not handled.
    #[Test]
    public function translatedSlugUnderDefaultBaseIsNotFound(): void
    {
        $request = new InternalRequest('http://localhost/show/community-fountain-weimar');

        self::assertSame(404, $this->executeFrontendSubRequest($request)->getStatusCode());
    }

    #[Test]
    public function formerQueryStringUrlKeepsRendering(): void
    {
        $request = $this->detailRequest('tx_thuecat_touristattractionshow', 'attraction', '21');

        $result = $this->executeFrontendSubRequest($request);

        self::assertSame(200, $result->getStatusCode());
        self::assertStringContainsString('<h2>Gemeindebrunnen Weimar</h2>', (string)$result->getBody());
    }

    #[Test]
    public function showPageWithoutRoleKeepsQueryStringLinks(): void
    {
        $body = (string)$this->executeFrontendSubRequest($this->pageRequest(51))->getBody();

        self::assertStringContainsString(
            'href="/show-without-role?tx_thuecat_touristattractionshow%5Baction%5D=show&amp;tx_thuecat_touristattractionshow%5Battraction%5D=24&amp;',
            $body
        );
    }

    #[Test]
    public function queryStringLinkToShowPageWithoutRoleResolves(): void
    {
        $request = $this->detailRequest('tx_thuecat_touristattractionshow', 'attraction', '24', 50);

        $result = $this->executeFrontendSubRequest($request);

        self::assertSame(200, $result->getStatusCode());
        self::assertStringContainsString('<h2>Herderkirche Weimar</h2>', (string)$result->getBody());
    }

    #[Test]
    public function listLinksToSecondPageByPathSegment(): void
    {
        $body = (string)$this->executeFrontendSubRequest($this->pageRequest(40))->getBody();

        self::assertStringContainsString('href="/list/2"', $body);
    }

    #[Test]
    public function listLinksToFirstPageWithoutSegment(): void
    {
        $request = new InternalRequest('http://localhost/list/2');

        $body = (string)$this->executeFrontendSubRequest($request)->getBody();

        self::assertStringContainsString('href="/list"', $body);
    }

    #[Test]
    public function pathSegmentRendersSecondListPage(): void
    {
        $request = new InternalRequest('http://localhost/list/2');

        $result = $this->executeFrontendSubRequest($request);

        self::assertSame(200, $result->getStatusCode());
        $body = (string)$result->getBody();
        self::assertStringContainsString('Herderkirche Weimar', $body);
        self::assertStringNotContainsString('Gemeindebrunnen Weimar', $body);
    }
}
