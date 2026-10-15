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
use TYPO3\CMS\Core\Http\StreamFactory;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractFrontendTestCase;

class FilterRoutingTest extends AbstractFrontendTestCase
{
    protected function getDataSetFileName(): string
    {
        return 'RecordsForFilterRouting.php';
    }

    protected function getRenderingTypoScript(): string
    {
        return 'FilterRoutingRendering.typoscript';
    }

    #[Test]
    public function townFilterLinksBySlugSegment(): void
    {
        $body = $this->filteredListBody(10, ['towns' => ['1']]);

        self::assertStringContainsString('href="/liste/ort/erfurt/2"', $body);
        self::assertStringNotContainsString('tx_thuecat_touristattractionlist', $body);
    }

    #[Test]
    public function townSegmentRendersTownResults(): void
    {
        $body = $this->routedBody('/liste/ort/weimar');

        self::assertStringContainsString('Goethehaus Weimar', $body);
        self::assertStringNotContainsString('Erfurt', $body);
    }

    #[Test]
    public function multipleTownsLinkAsOneSortedSegment(): void
    {
        $body = $this->filteredListBody(10, ['towns' => ['2', '1']]);

        self::assertStringContainsString('href="/liste/ort/erfurt+weimar/2"', $body);
    }

    // Four attractions in Erfurt, one in Weimar, one per page.
    #[Test]
    public function multipleTownSegmentWithPageRendersResultsOfAllTowns(): void
    {
        $body = $this->routedBody('/liste/ort/erfurt+weimar/4');

        self::assertStringContainsString('href="/liste/ort/erfurt+weimar/5"', $body);
        self::assertStringNotContainsString('href="/liste/ort/erfurt+weimar/6"', $body);
    }

    #[Test]
    public function townAndCategorySegmentsRenderTheirIntersection(): void
    {
        $body = $this->routedBody('/liste/ort/erfurt/kategorie/kirche');

        self::assertStringContainsString('Domberg Erfurt', $body);
        self::assertStringContainsString('href="/liste/ort/erfurt/kategorie/kirche/2"', $body);
        self::assertStringNotContainsString('museum', $body);
    }

    #[Test]
    public function englishListLinksByEnglishLabelAndSlug(): void
    {
        $body = $this->filteredListBody(10, ['categories' => ['11']], 1);

        self::assertStringContainsString('href="/en/list/category/church/2"', $body);
    }

    #[Test]
    public function englishCategorySegmentRendersTranslatedResults(): void
    {
        $body = $this->routedBody('/en/list/category/church');

        self::assertStringContainsString('Erfurt Cathedral', $body);
        self::assertStringNotContainsString('Stadtmuseum', $body);
    }

    #[Test]
    public function unknownSlugIsNotFound(): void
    {
        $request = new InternalRequest('http://localhost/liste/ort/gibt-es-nicht');

        self::assertSame(404, $this->executeFrontendSubRequest($request)->getStatusCode());
    }

    #[Test]
    public function lockedTownStaysOutOfTheUrl(): void
    {
        $body = $this->filteredListBody(12, ['categories' => ['10']]);

        self::assertStringContainsString('href="/erfurt-liste/kategorie/museum/2"', $body);
        self::assertStringNotContainsString('Goethehaus Weimar', $body);
    }

    #[Test]
    public function petsAllowedLinksByFixedSegment(): void
    {
        $body = $this->filteredListBody(10, ['petsAllowed' => '1']);

        self::assertStringContainsString('href="/liste/haustiere-erlaubt/2"', $body);
    }

    #[Test]
    public function petsAllowedSegmentRendersOnlyMatchingResults(): void
    {
        $body = $this->routedBody('/liste/haustiere-erlaubt');

        self::assertStringContainsString('href="/liste/haustiere-erlaubt/3"', $body);
        self::assertStringNotContainsString('href="/liste/haustiere-erlaubt/4"', $body);
    }

    #[Test]
    public function englishPetsAllowedLinksByEnglishSegment(): void
    {
        $body = $this->filteredListBody(10, ['petsAllowed' => '1'], 1);

        self::assertStringContainsString('href="/en/list/pets-allowed/2"', $body);
    }

    #[Test]
    public function booleanSegmentsFollowTheRelationSegments(): void
    {
        $body = $this->filteredListBody(10, [
            'towns' => ['1'],
            'publicAccess' => '1',
            'isAccessibleForFree' => '1',
            'petsAllowed' => '1',
        ]);

        self::assertStringContainsString(
            'href="/liste/ort/erfurt/haustiere-erlaubt/eintritt-frei/oeffentlich-zugaenglich/2"',
            $body
        );
    }

    #[Test]
    public function inactiveBooleanFiltersAreAbsent(): void
    {
        $body = $this->filteredListBody(10, ['towns' => ['1']]);

        self::assertStringContainsString('href="/liste/ort/erfurt/2"', $body);
        self::assertStringNotContainsString('haustiere-erlaubt', $body);
    }

    #[Test]
    public function searchRedirectCarriesFiltersInPathAndSearchwordAsQ(): void
    {
        $request = $this->pageRequest(10)
            ->withMethod('POST')
            ->withBody((new StreamFactory())->createStream(http_build_query([
                'tx_thuecat_touristattractionlist' => ['demand' => ['searchword' => 'Domberg Erfurt', 'towns' => ['1']]],
            ])))
        ;

        $response = $this->executeFrontendSubRequest($request);

        self::assertSame(303, $response->getStatusCode());
        self::assertStringEndsWith('/liste/ort/erfurt?q=Domberg%20Erfurt', $response->getHeaderLine('location'));
    }

    #[Test]
    public function listReadsSearchwordFromQ(): void
    {
        $body = $this->routedBody('/liste?q=Domberg');

        self::assertStringContainsString('Domberg Erfurt', $body);
        self::assertStringNotContainsString('Stadtmuseum Erfurt', $body);
    }

    #[Test]
    public function paginationKeepsQ(): void
    {
        $body = $this->routedBody('/liste/ort/erfurt?q=Erfurt');

        self::assertStringContainsString('href="/liste/ort/erfurt/2?q=Erfurt"', $body);
        self::assertStringNotContainsString('tx_thuecat_touristattractionlist', $body);
    }

    /**
     * A list filtered the way a query-string URL from before routing does it.
     *
     * @param array<string, string|list<string>> $demand
     */
    protected function filteredListBody(int $pageId, array $demand, int $languageId = 0): string
    {
        $request = $this->pageRequest($pageId, $languageId)
            ->withQueryParams(['tx_thuecat_touristattractionlist' => ['demand' => $demand]])
        ;

        return (string)$this->executeFrontendSubRequest($request)->getBody();
    }

    protected function routedBody(string $path): string
    {
        $response = $this->executeFrontendSubRequest(new InternalRequest('http://localhost' . $path));
        self::assertSame(200, $response->getStatusCode());

        return (string)$response->getBody();
    }
}
