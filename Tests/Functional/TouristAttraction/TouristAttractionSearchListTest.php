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
use TYPO3\CMS\Core\Http\StreamFactory;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractFrontendTestCase;

final class TouristAttractionSearchListTest extends AbstractFrontendTestCase
{
    protected function getDataSetFileName(): string
    {
        return 'TouristAttractionsForSearchList.php';
    }

    protected function getRenderingTypoScript(): string
    {
        return 'SearchListRendering.typoscript';
    }

    #[Test]
    public function combinedPageRendersFormAndList(): void
    {
        $request = new InternalRequest()->withPageId(10);

        $result = $this->executeFrontendSubRequest($request);
        $body = (string)$result->getBody();

        $this->assertSame(200, $result->getStatusCode());
        $this->assertStringContainsString('name="tx_thuecat_touristattractionlist[demand][searchword]"', $body);
        $this->assertStringContainsString('Erfurt Frei 01', $body);
    }

    #[Test]
    public function searchPluginDoesNotRenderListAndListPluginDoesNotRenderForm(): void
    {
        $request = new InternalRequest()->withPageId(10);

        $body = $this->executeFrontendSubRequestAndReturnBody($request);

        // Exactly one form (search plugin), and no list articles appear before it.
        $this->assertSame(1, substr_count($body, '<form'));
        $beforeForm = substr($body, 0, (int)strpos($body, '<form'));
        $this->assertStringNotContainsString('Erfurt Frei', $beforeForm);
    }

    #[Test]
    public function getFilterByTownAndFreeNarrowsTheList(): void
    {
        $request = new InternalRequest()
            ->withPageId(10)
            ->withQueryParams(['tx_thuecat_touristattractionlist' => ['demand' => [
                'towns' => [1],
                'isAccessibleForFree' => '1',
            ]]])
        ;

        $body = $this->executeFrontendSubRequestAndReturnBody($request);

        $this->assertStringContainsString('Erfurt Frei 01', $body);
        $this->assertStringNotContainsString('Erfurt Kostenpflichtig', $body);
        $this->assertStringNotContainsString('Weimar Frei', $body);
    }

    #[Test]
    public function formRepopulatesSubmittedFilterState(): void
    {
        $request = new InternalRequest()
            ->withPageId(10)
            ->withQueryParams(['tx_thuecat_touristattractionlist' => ['demand' => [
                'towns' => [1],
                'isAccessibleForFree' => '1',
            ]]])
        ;

        $body = $this->executeFrontendSubRequestAndReturnBody($request);

        $this->assertMatchesRegularExpression('#<option value="1"[^>]*selected#', $body);
        $this->assertMatchesRegularExpression('#name="tx_thuecat_touristattractionlist\[demand\]\[isAccessibleForFree\]"[^>]*checked#', $body);
    }

    #[Test]
    public function paginationLinksCarryTheDemand(): void
    {
        $request = new InternalRequest()
            ->withPageId(10)
            ->withQueryParams(['tx_thuecat_touristattractionlist' => ['demand' => [
                'towns' => [1],
                'isAccessibleForFree' => '1',
            ]]])
        ;

        $body = $this->executeFrontendSubRequestAndReturnBody($request);

        // 15 free Erfurt records, itemsPerPage 10 -> a second page exists, and its
        // link must keep the active filter so paging does not reset the search.
        $this->assertStringContainsString('tx_thuecat_touristattractionlist%5BcurrentPage%5D=2', $body);
        $this->assertStringContainsString('tx_thuecat_touristattractionlist%5Bdemand%5D%5Btowns%5D=1', $body);
        $this->assertStringContainsString('tx_thuecat_touristattractionlist%5Bdemand%5D%5BisAccessibleForFree%5D=1', $body);
    }

    #[Test]
    public function postRedirectsToCleanGetUrlWithDemand(): void
    {
        $request = new InternalRequest()
            ->withPageId(10)
            ->withMethod('POST')
            ->withBody(new StreamFactory()->createStream(http_build_query([
                'tx_thuecat_touristattractionlist' => ['demand' => ['searchword' => 'Erfurt Frei 01']],
            ])))
        ;

        $response = $this->executeFrontendSubRequest($request);

        $this->assertSame(303, $response->getStatusCode());
        $location = $response->getHeaderLine('location');
        $this->assertStringEndsWith('?q=Erfurt%20Frei%2001', $location);
        $this->assertStringNotContainsString('__referrer', $location);
        $this->assertStringNotContainsString('__trustedProperties', $location);
    }
}
