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

namespace WerkraumMedia\ThueCat\Tests\Functional\Sitemap;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractFrontendTestCase;

/**
 * The fixture site's base carries no host, so locations are rendered as paths.
 */
class DetailRecordSitemapTest extends AbstractFrontendTestCase
{
    protected function getDataSetFileName(): string
    {
        return 'RecordsForSitemap.php';
    }

    protected function getRenderingTypoScript(): string
    {
        return 'SitemapRendering.typoscript';
    }

    #[Test]
    public function listsAttractionsOfCurrentSiteOnly(): void
    {
        $sitemap = (string)$this->executeFrontendSubRequest($this->sitemapRequest('attraction'))->getBody();

        self::assertStringContainsString('<loc>/show/gemeindebrunnen-weimar</loc>', $sitemap);
        self::assertStringContainsString('<loc>/show/goethehaus-weimar</loc>', $sitemap);
        self::assertStringNotContainsString('wartburg', $sitemap);
    }

    #[Test]
    public function listsTranslationsUnderTheLanguageBase(): void
    {
        $sitemap = (string)$this->executeFrontendSubRequest($this->sitemapRequest('attraction', 1))->getBody();

        self::assertStringContainsString('<loc>/en/show/community-fountain-weimar</loc>', $sitemap);
        self::assertStringNotContainsString('goethehaus-weimar', $sitemap);
        self::assertStringNotContainsString('gemeindebrunnen-weimar', $sitemap);
    }

    #[Test]
    public function excludesHiddenAttractions(): void
    {
        $sitemap = (string)$this->executeFrontendSubRequest($this->sitemapRequest('attraction'))->getBody();

        self::assertStringContainsString('<loc>/show/gemeindebrunnen-weimar</loc>', $sitemap);
        self::assertStringNotContainsString('herderkirche-weimar', $sitemap);
    }

    #[Test]
    public function isEmptyWithoutShowPage(): void
    {
        $sitemap = (string)$this->executeFrontendSubRequest($this->sitemapRequest('trail'))->getBody();

        self::assertStringNotContainsString('<url>', $sitemap);
    }

    #[Test]
    public function translatedLocationRendersTranslatedAttraction(): void
    {
        $sitemap = (string)$this->executeFrontendSubRequest($this->sitemapRequest('attraction', 1))->getBody();
        self::assertSame(1, preg_match('#<loc>(/en/show/[^<]+)</loc>#', $sitemap, $matches));

        $result = $this->executeFrontendSubRequest(new InternalRequest('http://localhost' . $matches[1]));

        self::assertSame(200, $result->getStatusCode());
        self::assertStringContainsString('<h2>Community Fountain Weimar</h2>', (string)$result->getBody());
    }
}
