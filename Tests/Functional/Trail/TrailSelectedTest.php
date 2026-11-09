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

namespace WerkraumMedia\ThueCat\Tests\Functional\Trail;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractFrontendTestCase;

class TrailSelectedTest extends AbstractFrontendTestCase
{
    protected function getDataSetFileName(): string
    {
        return 'TrailsForSelected.php';
    }

    #[Test]
    public function showsOnlyEditorSelectedRecords(): void
    {
        $request = new InternalRequest()->withPageId(10);

        $body = $this->executeFrontendSubRequestAndReturnBody($request);

        // pi_flexform settings.selectedRecords = 3,6,1,7,999
        $this->assertStringContainsString('Goethe-Erlebnisweg', $body);
        $this->assertStringContainsString('Ilmtal-Radweg', $body);
        $this->assertStringNotContainsString('Lutherweg Thüringen', $body);
    }

    #[Test]
    public function preservesEditorPickedOrder(): void
    {
        $request = new InternalRequest()->withPageId(10);

        $body = $this->executeFrontendSubRequestAndReturnBody($request);

        // pi_flexform settings.selectedRecords = 3,6,1,7,999
        self::assertAppearInOrder($body, 'Ilmtal-Radweg', 'Goethe-Erlebnisweg');
    }

    #[Test]
    public function skipsUnresolvableSelectedRecords(): void
    {
        $body = $this->executeFrontendSubRequestAndReturnBody($this->pageRequest(10));

        $this->assertStringContainsString('Ilmtal-Radweg', $body);
        $this->assertStringContainsString('Goethe-Erlebnisweg', $body);
        $this->assertStringNotContainsString('Hidden Trail', $body);
        $this->assertStringNotContainsString('Deleted Trail', $body);
    }

    #[Test]
    public function strictLanguageRendersTranslatedRecord(): void
    {
        $body = $this->executeFrontendSubRequestAndReturnBody($this->pageRequest(10, 1));

        $this->assertStringContainsString('Ilm Valley Cycle Path', $body);
        $this->assertStringNotContainsString('Ilmtal-Radweg', $body);
    }

    #[Test]
    public function strictLanguageOmitsUntranslatedRecord(): void
    {
        $body = $this->executeFrontendSubRequestAndReturnBody($this->pageRequest(10, 1));

        $this->assertStringContainsString('Ilm Valley Cycle Path', $body);
        $this->assertStringNotContainsString('Goethe-Erlebnisweg', $body);
    }

    #[Test]
    public function fallbackLanguageRendersTranslatedRecord(): void
    {
        $body = $this->executeFrontendSubRequestAndReturnBody($this->pageRequest(10, 3));

        $this->assertStringContainsString('Ruta ciclista del valle del Ilm', $body);
        $this->assertStringNotContainsString('Ilmtal-Radweg', $body);
    }

    #[Test]
    public function fallbackLanguageRendersUntranslatedRecordInDefaultLanguage(): void
    {
        $body = $this->executeFrontendSubRequestAndReturnBody($this->pageRequest(10, 3));

        $this->assertStringContainsString('Goethe-Erlebnisweg', $body);
    }

    #[Test]
    public function translatedListPreservesEditorPickedOrder(): void
    {
        $body = $this->executeFrontendSubRequestAndReturnBody($this->pageRequest(10, 3));

        self::assertAppearInOrder($body, 'Ruta ciclista del valle del Ilm', 'Goethe-Erlebnisweg');
    }
}
