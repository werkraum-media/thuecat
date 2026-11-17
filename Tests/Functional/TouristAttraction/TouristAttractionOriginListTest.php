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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractFrontendTestCase;

/**
 * Page 10 selects no origin, page 20 "Weimar POIs", page 30 "Weimar POIs" and "Erfurt POIs". Page 40 holds a search
 * plugin next to a list limited to "Weimar POIs".
 */
final class TouristAttractionOriginListTest extends AbstractFrontendTestCase
{
    protected function getDataSetFileName(): string
    {
        return 'TouristAttractionsForOrigins.php';
    }

    /**
     * @return iterable<string, array{int, list<string>, list<string>}>
     */
    public static function originSelections(): iterable
    {
        yield 'one origin' => [20, ['Museum Weimar'], ['Museum Erfurt', 'Museum ohne Herkunft']];
        yield 'two origins' => [30, ['Museum Weimar', 'Museum Erfurt'], ['Museum ohne Herkunft']];
        yield 'no origin' => [10, ['Museum Weimar', 'Museum Erfurt', 'Museum ohne Herkunft'], []];
    }

    /**
     * @param list<string> $shownTitles
     * @param list<string> $hiddenTitles
     */
    #[Test]
    #[DataProvider('originSelections')]
    public function listsOnlyAttractionsOfTheSelectedOrigins(int $pageUid, array $shownTitles, array $hiddenTitles): void
    {
        $body = $this->executeFrontendSubRequestAndReturnBody($this->pageRequest($pageUid));

        foreach ($shownTitles as $title) {
            $this->assertStringContainsString($title, $body);
        }

        foreach ($hiddenTitles as $title) {
            $this->assertStringNotContainsString($title, $body);
        }
    }

    #[Test]
    public function requestParametersCannotWidenTheSelectedOrigins(): void
    {
        $request = $this->listDemandRequest(20, ['origins' => 'Weimar POIs,Erfurt POIs']);

        $body = $this->executeFrontendSubRequestAndReturnBody($request);

        $this->assertStringContainsString('Museum Weimar', $body);
        $this->assertStringNotContainsString('Museum Erfurt', $body);
    }

    #[Test]
    public function aSearchNextToAListLimitedToAnOriginIsLimitedToIt(): void
    {
        $request = $this->listDemandRequest(40, ['searchword' => 'Museum']);

        $body = $this->executeFrontendSubRequestAndReturnBody($request);

        $this->assertStringContainsString('Museum Weimar', $body);
        $this->assertStringNotContainsString('Museum Erfurt', $body);
        $this->assertStringNotContainsString('Museum ohne Herkunft', $body);
    }
}
