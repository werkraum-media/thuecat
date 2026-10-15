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

namespace WerkraumMedia\ThueCat\Tests\Functional\Caching;

use PHPUnit\Framework\Attributes\Test;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractCachingTestCase;

class SitemapInvalidationTest extends AbstractCachingTestCase
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
    public function createdAttractionAppearsOnNextRequest(): void
    {
        $this->setUpBackendUserForDataHandler();
        $this->executeFrontendSubRequest($this->sitemapRequest('attraction'));

        $this->createRecord(11, ['title' => 'Schillerhaus Weimar']);

        $sitemap = (string)$this->executeFrontendSubRequest($this->sitemapRequest('attraction'))->getBody();
        self::assertStringContainsString('<loc>/show/schillerhaus-weimar</loc>', $sitemap);
    }

    #[Test]
    public function deletedAttractionDisappearsOnNextRequest(): void
    {
        $this->setUpBackendUserForDataHandler();
        $this->executeFrontendSubRequest($this->sitemapRequest('attraction'));

        $this->deleteRecord(21);

        $sitemap = (string)$this->executeFrontendSubRequest($this->sitemapRequest('attraction'))->getBody();
        self::assertStringContainsString('<loc>/show/goethehaus-weimar</loc>', $sitemap);
        self::assertStringNotContainsString('gemeindebrunnen-weimar', $sitemap);
    }
}
