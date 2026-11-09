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

final class TouristAttractionSelectedTest extends AbstractFrontendTestCase
{
    protected function getDataSetFileName(): string
    {
        return 'TouristAttractionsForSelected.php';
    }

    #[Test]
    public function showsOnlyEditorSelectedRecords(): void
    {
        $request = new InternalRequest()->withPageId(10);

        $body = $this->executeFrontendSubRequestAndReturnBody($request);

        // pi_flexform settings.selectedRecords = 3,6,1,7,999 -> Goethehaus (3) and Stadtmuseum (1)
        $this->assertStringContainsString('Stadtmuseum Erfurt', $body);
        $this->assertStringContainsString('Goethehaus Weimar', $body);
        $this->assertStringNotContainsString('Domberg Erfurt', $body);
    }

    #[Test]
    public function preservesEditorPickedOrder(): void
    {
        $request = new InternalRequest()->withPageId(10);

        $body = $this->executeFrontendSubRequestAndReturnBody($request);

        // pi_flexform settings.selectedRecords = 3,6,1,7,999 -> Goethehaus must appear before Stadtmuseum
        self::assertAppearInOrder($body, 'Goethehaus Weimar', 'Stadtmuseum Erfurt');
    }

    #[Test]
    public function skipsUnresolvableSelectedRecords(): void
    {
        $body = $this->executeFrontendSubRequestAndReturnBody($this->pageRequest(10));

        $this->assertStringContainsString('Goethehaus Weimar', $body);
        $this->assertStringContainsString('Stadtmuseum Erfurt', $body);
        $this->assertStringNotContainsString('Hidden Attraction', $body);
        $this->assertStringNotContainsString('Deleted Attraction', $body);
    }

    #[Test]
    public function strictLanguageRendersTranslatedRecord(): void
    {
        $body = $this->executeFrontendSubRequestAndReturnBody($this->pageRequest(10, 1));

        $this->assertStringContainsString('Goethe House Weimar', $body);
        $this->assertStringNotContainsString('Goethehaus Weimar', $body);
    }

    #[Test]
    public function strictLanguageOmitsUntranslatedRecord(): void
    {
        $body = $this->executeFrontendSubRequestAndReturnBody($this->pageRequest(10, 1));

        $this->assertStringContainsString('Goethe House Weimar', $body);
        $this->assertStringNotContainsString('Stadtmuseum Erfurt', $body);
    }

    #[Test]
    public function fallbackLanguageRendersTranslatedRecord(): void
    {
        $body = $this->executeFrontendSubRequestAndReturnBody($this->pageRequest(10, 3));

        $this->assertStringContainsString('Casa de Goethe Weimar', $body);
        $this->assertStringNotContainsString('Goethehaus Weimar', $body);
    }

    #[Test]
    public function fallbackLanguageRendersUntranslatedRecordInDefaultLanguage(): void
    {
        $body = $this->executeFrontendSubRequestAndReturnBody($this->pageRequest(10, 3));

        $this->assertStringContainsString('Stadtmuseum Erfurt', $body);
    }

    #[Test]
    public function translatedListPreservesEditorPickedOrder(): void
    {
        $body = $this->executeFrontendSubRequestAndReturnBody($this->pageRequest(10, 3));

        self::assertAppearInOrder($body, 'Casa de Goethe Weimar', 'Stadtmuseum Erfurt');
    }
}
