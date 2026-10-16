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

class TouristAttractionSelectedTest extends AbstractFrontendTestCase
{
    protected function getDataSetFileName(): string
    {
        return 'TouristAttractionsForSelected.php';
    }

    #[Test]
    public function showsOnlyEditorSelectedRecords(): void
    {
        $request = (new InternalRequest())->withPageId(10);

        $body = (string)$this->executeFrontendSubRequest($request)->getBody();

        // pi_flexform settings.selectedRecords = 3,6,1,7,999 -> Goethehaus (3) and Stadtmuseum (1)
        self::assertStringContainsString('Stadtmuseum Erfurt', $body);
        self::assertStringContainsString('Goethehaus Weimar', $body);
        self::assertStringNotContainsString('Domberg Erfurt', $body);
    }

    #[Test]
    public function preservesEditorPickedOrder(): void
    {
        $request = (new InternalRequest())->withPageId(10);

        $body = (string)$this->executeFrontendSubRequest($request)->getBody();

        // pi_flexform settings.selectedRecords = 3,6,1,7,999 -> Goethehaus must appear before Stadtmuseum
        self::assertLessThan(
            mb_strpos($body, 'Stadtmuseum Erfurt'),
            mb_strpos($body, 'Goethehaus Weimar'),
            'Selected records are not rendered in the editor-picked order.'
        );
    }

    #[Test]
    public function skipsUnresolvableSelectedRecords(): void
    {
        $body = (string)$this->executeFrontendSubRequest($this->pageRequest(10))->getBody();

        self::assertStringContainsString('Goethehaus Weimar', $body);
        self::assertStringContainsString('Stadtmuseum Erfurt', $body);
        self::assertStringNotContainsString('Hidden Attraction', $body);
        self::assertStringNotContainsString('Deleted Attraction', $body);
    }

    #[Test]
    public function strictLanguageRendersTranslatedRecord(): void
    {
        $body = (string)$this->executeFrontendSubRequest($this->pageRequest(10, 1))->getBody();

        self::assertStringContainsString('Goethe House Weimar', $body);
        self::assertStringNotContainsString('Goethehaus Weimar', $body);
    }

    #[Test]
    public function strictLanguageOmitsUntranslatedRecord(): void
    {
        $body = (string)$this->executeFrontendSubRequest($this->pageRequest(10, 1))->getBody();

        self::assertStringContainsString('Goethe House Weimar', $body);
        self::assertStringNotContainsString('Stadtmuseum Erfurt', $body);
    }

    #[Test]
    public function fallbackLanguageRendersTranslatedRecord(): void
    {
        $body = (string)$this->executeFrontendSubRequest($this->pageRequest(10, 3))->getBody();

        self::assertStringContainsString('Casa de Goethe Weimar', $body);
        self::assertStringNotContainsString('Goethehaus Weimar', $body);
    }

    #[Test]
    public function fallbackLanguageRendersUntranslatedRecordInDefaultLanguage(): void
    {
        $body = (string)$this->executeFrontendSubRequest($this->pageRequest(10, 3))->getBody();

        self::assertStringContainsString('Stadtmuseum Erfurt', $body);
    }

    #[Test]
    public function translatedListPreservesEditorPickedOrder(): void
    {
        $body = (string)$this->executeFrontendSubRequest($this->pageRequest(10, 3))->getBody();

        self::assertStringContainsString('Casa de Goethe Weimar', $body);
        self::assertStringContainsString('Stadtmuseum Erfurt', $body);
        self::assertLessThan(
            mb_strpos($body, 'Stadtmuseum Erfurt'),
            mb_strpos($body, 'Casa de Goethe Weimar'),
            'Selected records are not rendered in the editor-picked order.'
        );
    }
}
