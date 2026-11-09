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

namespace WerkraumMedia\ThueCat\Tests\Functional\TouristAttraction;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractFrontendTestCase;

class TouristAttractionPaginationTest extends AbstractFrontendTestCase
{
    protected function getDataSetFileName(): string
    {
        return 'TouristAttractionsForPagination.php';
    }

    protected function getRenderingTypoScript(): string
    {
        return 'PaginationRendering.typoscript';
    }

    #[Test]
    public function firstPageShowsOnlyConfiguredNumberOfItems(): void
    {
        $request = new InternalRequest()->withPageId(10);

        $body = $this->executeFrontendSubRequestAndReturnBody($request);

        $this->assertStringContainsString('Attraction 01', $body);
        $this->assertStringContainsString('Attraction 10', $body);
        $this->assertStringNotContainsString('Attraction 11', $body);
    }

    #[Test]
    public function secondPageShowsNextItems(): void
    {
        $request = new InternalRequest()
            ->withPageId(10)
            ->withQueryParams(['tx_thuecat_touristattractionlist' => ['currentPage' => '2']])
        ;

        $body = $this->executeFrontendSubRequestAndReturnBody($request);

        $this->assertStringContainsString('Attraction 11', $body);
        $this->assertStringContainsString('Attraction 20', $body);
        $this->assertStringNotContainsString('Attraction 01<', $body);
        $this->assertStringNotContainsString('Attraction 21', $body);
    }

    #[Test]
    public function rendersAPaginationLinkPerPage(): void
    {
        $request = new InternalRequest()->withPageId(10);

        $body = $this->executeFrontendSubRequestAndReturnBody($request);

        $this->assertStringContainsString('tx_thuecat_touristattractionlist%5BcurrentPage%5D=2', $body);
        $this->assertStringContainsString('tx_thuecat_touristattractionlist%5BcurrentPage%5D=3', $body);
    }

    #[Test]
    public function rendersPaginationSummaryWithItemsPerPageAndPageCount(): void
    {
        $request = new InternalRequest()->withPageId(10);

        $body = $this->executeFrontendSubRequestAndReturnBody($request);

        // 25 fixtures, itemsPerPage = 10 => 3 pages
        $this->assertStringContainsString('10 Einträge pro Seite, 3 Seiten insgesamt', $body);
    }

    // 3.5: a keyword-filtered list must keep its selection across pages, both in
    // the links it renders and in the result set the next page returns.
    #[Test]
    public function keywordSelectionIsCarriedIntoPaginationLinks(): void
    {
        $request = new InternalRequest()
            ->withPageId(10)
            ->withQueryParams(['tx_thuecat_touristattractionlist' => ['demand' => ['keywords' => ['501']]]])
        ;

        $body = $this->executeFrontendSubRequestAndReturnBody($request);

        // 13 odd-numbered attractions carry the keyword => 2 pages.
        $this->assertStringContainsString('10 Einträge pro Seite, 2 Seiten insgesamt', $body);
        $this->assertStringContainsString('tx_thuecat_touristattractionlist%5Bdemand%5D%5Bkeywords%5D=501', $body);
    }

    #[Test]
    public function keywordFilterStillAppliesOnTheSecondPage(): void
    {
        $request = new InternalRequest()
            ->withPageId(10)
            ->withQueryParams(['tx_thuecat_touristattractionlist' => [
                'demand' => ['keywords' => ['501']],
                'currentPage' => '2',
            ]])
        ;

        $body = $this->executeFrontendSubRequestAndReturnBody($request);

        // Page 1 holds the first ten odd ones (01-19); page 2 the rest (21-25).
        $this->assertStringContainsString('Attraction 21', $body);
        $this->assertStringContainsString('Attraction 25', $body);
        // An even-numbered attraction would mean the filter was dropped.
        $this->assertStringNotContainsString('Attraction 22', $body);
    }
}
