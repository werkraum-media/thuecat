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

class TouristAttractionSearchTest extends AbstractFrontendTestCase
{
    protected function getDataSetFileName(): string
    {
        return 'TouristAttractionsForList.php';
    }

    #[Test]
    public function searchFormIsRenderedOnSearchPage(): void
    {
        $request = (new InternalRequest())->withPageId(12);

        $result = $this->executeFrontendSubRequest($request);
        $body = (string)$result->getBody();

        self::assertSame(200, $result->getStatusCode());
        self::assertStringContainsString('<form', $body);
        self::assertStringContainsString('name="tx_thuecat_touristattractionlist[demand][searchword]"', $body);
    }

    #[Test]
    public function searchFormActionTargetsListPage(): void
    {
        $request = (new InternalRequest())->withPageId(12);

        $body = (string)$this->executeFrontendSubRequest($request)->getBody();

        // form action attribute should point at the list page (uid 10, slug /list/)
        self::assertMatchesRegularExpression('#<form[^>]+action="[^"]*/list/[^"]*"#', $body);
    }

    #[Test]
    public function searchFormDoesNotRenderResultList(): void
    {
        $request = (new InternalRequest())->withPageId(12);

        $body = (string)$this->executeFrontendSubRequest($request)->getBody();

        self::assertStringNotContainsString('Stadtmuseum Erfurt', $body);
        self::assertStringNotContainsString('Domberg Erfurt', $body);
    }

    #[Test]
    public function searchFormPrefillsValueFromUrl(): void
    {
        $request = (new InternalRequest())
            ->withPageId(12)
            ->withQueryParams(['tx_thuecat_touristattractionlist' => ['demand' => ['searchword' => 'Stadtmuseum']]])
        ;

        $body = (string)$this->executeFrontendSubRequest($request)->getBody();

        self::assertMatchesRegularExpression('#<input[^>]+name="tx_thuecat_touristattractionlist\[demand\]\[searchword\]"[^>]+value="Stadtmuseum"#', $body);
    }

    // 4.1: the mask offers the keyword tree, grouped by parent — the nesting is
    // what the grouping is, since a set renders as a checkbox owning its terms.
    #[Test]
    public function searchFormOffersTheKeywordTreeGroupedByParent(): void
    {
        $request = (new InternalRequest())->withPageId(12);

        $body = (string)$this->executeFrontendSubRequest($request)->getBody();

        self::assertStringContainsString('name="tx_thuecat_touristattractionlist[demand][keywords][]"', $body);
        self::assertStringContainsString('Ambiente', $body);
        self::assertStringContainsString('romantisch', $body);
        // The anchor itself is a container, never an option.
        self::assertStringNotContainsString('>Keywords<', $body);
    }

    // 4.2: a keyword already chosen comes back checked, so the visitor sees what
    // is filtering their result.
    #[Test]
    public function searchFormReflectsTheSelectedKeyword(): void
    {
        $request = (new InternalRequest())
            ->withPageId(12)
            ->withQueryParams(['tx_thuecat_touristattractionlist' => ['demand' => ['keywords' => ['501']]]])
        ;

        $body = (string)$this->executeFrontendSubRequest($request)->getBody();

        self::assertMatchesRegularExpression(
            '#<input[^>]+value="501"[^>]+checked="checked"#',
            $body
        );
    }
}
