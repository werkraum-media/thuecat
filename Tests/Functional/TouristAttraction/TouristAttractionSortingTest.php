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

final class TouristAttractionSortingTest extends AbstractFrontendTestCase
{
    protected function getDataSetFileName(): string
    {
        return 'TouristAttractionsForSorting.php';
    }

    #[Test]
    public function aListWithoutSortSettingIsOrderedByTitle(): void
    {
        $body = $this->executeFrontendSubRequestAndReturnBody((new InternalRequest())->withPageId(10));

        self::assertAppearInOrder($body, 'Alpha Museum', 'Beta Park', 'Gamma Garten');
    }

    #[Test]
    public function aListInBackendOrderIsOrderedBySorting(): void
    {
        $body = $this->executeFrontendSubRequestAndReturnBody((new InternalRequest())->withPageId(20));

        self::assertAppearInOrder($body, 'Beta Park', 'Gamma Garten', 'Alpha Museum');
    }

    #[Test]
    public function aFilteredListInBackendOrderIsOrderedBySorting(): void
    {
        $body = $this->executeFrontendSubRequestAndReturnBody((new InternalRequest())->withPageId(40));

        self::assertAppearInOrder($body, 'Beta Park', 'Gamma Garten', 'Alpha Museum');
    }

    #[Test]
    public function anUnknownSortSettingFallsBackToTitle(): void
    {
        $body = $this->executeFrontendSubRequestAndReturnBody((new InternalRequest())->withPageId(30));

        self::assertAppearInOrder($body, 'Alpha Museum', 'Beta Park', 'Gamma Garten');
    }

    #[Test]
    public function aRequestCannotPickTheOrder(): void
    {
        $request = (new InternalRequest())
            ->withPageId(10)
            ->withQueryParams(['tx_thuecat_touristattractionlist' => ['demand' => ['sortBy' => 'sorting']]])
        ;

        $body = $this->executeFrontendSubRequestAndReturnBody($request);

        self::assertAppearInOrder($body, 'Alpha Museum', 'Beta Park', 'Gamma Garten');
    }

    #[Test]
    public function tiedSortingFallsBackToTitle(): void
    {
        $body = $this->executeFrontendSubRequestAndReturnBody((new InternalRequest())->withPageId(50));

        self::assertAppearInOrder($body, 'Eta Brücke', 'Theta Tor');
    }

    #[Test]
    public function tiedSortingPutsEveryRecordOnExactlyOnePage(): void
    {
        $first = $this->executeFrontendSubRequestAndReturnBody((new InternalRequest())->withPageId(50));
        $second = $this->executeFrontendSubRequestAndReturnBody(
            (new InternalRequest())
                ->withPageId(50)
                ->withQueryParams(['tx_thuecat_touristattractionlist' => ['currentPage' => '2']])
        );

        self::assertStringContainsString('Eta Brücke', $first);
        self::assertStringContainsString('Theta Tor', $first);
        self::assertStringNotContainsString('Zeta Turm', $first);
        self::assertStringContainsString('Zeta Turm', $second);
        self::assertStringNotContainsString('Eta Brücke', $second);
        self::assertStringNotContainsString('Theta Tor', $second);
    }

    #[Test]
    public function paginationLinksCarryNoSortOrder(): void
    {
        $body = $this->executeFrontendSubRequestAndReturnBody((new InternalRequest())->withPageId(50));

        self::assertStringContainsString('tx_thuecat_touristattractionlist%5BcurrentPage%5D=2', $body);
        self::assertStringNotContainsString('sortBy', $body);
    }

    #[Test]
    public function filterRedirectCarriesNoSortOrder(): void
    {
        $request = (new InternalRequest())
            ->withPageId(20)
            ->withMethod('POST')
            ->withBody((new StreamFactory())->createStream(http_build_query([
                'tx_thuecat_touristattractionlist' => ['demand' => ['searchword' => 'Park']],
            ])))
        ;

        $response = $this->executeFrontendSubRequest($request);

        self::assertSame(303, $response->getStatusCode());
        $location = $response->getHeaderLine('location');
        self::assertStringEndsWith('?q=Park', $location);
        self::assertStringNotContainsString('sortBy', $location);
    }
}
