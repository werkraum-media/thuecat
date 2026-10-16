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
        $request = (new InternalRequest())->withPageId(10);

        $body = (string)$this->executeFrontendSubRequest($request)->getBody();

        // pi_flexform settings.selectedRecords = 3,6,1,7,999
        self::assertStringContainsString('Goethe-Erlebnisweg', $body);
        self::assertStringContainsString('Ilmtal-Radweg', $body);
        self::assertStringNotContainsString('Lutherweg Thüringen', $body);
    }

    #[Test]
    public function preservesEditorPickedOrder(): void
    {
        $request = (new InternalRequest())->withPageId(10);

        $body = (string)$this->executeFrontendSubRequest($request)->getBody();

        // pi_flexform settings.selectedRecords = 3,6,1,7,999
        self::assertLessThan(
            mb_strpos($body, 'Goethe-Erlebnisweg'),
            mb_strpos($body, 'Ilmtal-Radweg'),
            'Selected records are not rendered in the editor-picked order.'
        );
    }

    #[Test]
    public function skipsUnresolvableSelectedRecords(): void
    {
        $body = (string)$this->executeFrontendSubRequest($this->pageRequest(10))->getBody();

        self::assertStringContainsString('Ilmtal-Radweg', $body);
        self::assertStringContainsString('Goethe-Erlebnisweg', $body);
        self::assertStringNotContainsString('Hidden Trail', $body);
        self::assertStringNotContainsString('Deleted Trail', $body);
    }

    #[Test]
    public function strictLanguageRendersTranslatedRecord(): void
    {
        $body = (string)$this->executeFrontendSubRequest($this->pageRequest(10, 1))->getBody();

        self::assertStringContainsString('Ilm Valley Cycle Path', $body);
        self::assertStringNotContainsString('Ilmtal-Radweg', $body);
    }

    #[Test]
    public function strictLanguageOmitsUntranslatedRecord(): void
    {
        $body = (string)$this->executeFrontendSubRequest($this->pageRequest(10, 1))->getBody();

        self::assertStringContainsString('Ilm Valley Cycle Path', $body);
        self::assertStringNotContainsString('Goethe-Erlebnisweg', $body);
    }

    #[Test]
    public function fallbackLanguageRendersTranslatedRecord(): void
    {
        $body = (string)$this->executeFrontendSubRequest($this->pageRequest(10, 3))->getBody();

        self::assertStringContainsString('Ruta ciclista del valle del Ilm', $body);
        self::assertStringNotContainsString('Ilmtal-Radweg', $body);
    }

    #[Test]
    public function fallbackLanguageRendersUntranslatedRecordInDefaultLanguage(): void
    {
        $body = (string)$this->executeFrontendSubRequest($this->pageRequest(10, 3))->getBody();

        self::assertStringContainsString('Goethe-Erlebnisweg', $body);
    }

    #[Test]
    public function translatedListPreservesEditorPickedOrder(): void
    {
        $body = (string)$this->executeFrontendSubRequest($this->pageRequest(10, 3))->getBody();

        self::assertStringContainsString('Ruta ciclista del valle del Ilm', $body);
        self::assertStringContainsString('Goethe-Erlebnisweg', $body);
        self::assertLessThan(
            mb_strpos($body, 'Goethe-Erlebnisweg'),
            mb_strpos($body, 'Ruta ciclista del valle del Ilm'),
            'Selected records are not rendered in the editor-picked order.'
        );
    }
}
