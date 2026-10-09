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

        // pi_flexform settings.selectedRecords = 3,1 -> Goethehaus (3) and Stadtmuseum (1)
        self::assertStringContainsString('Stadtmuseum Erfurt', $body);
        self::assertStringContainsString('Goethehaus Weimar', $body);
        self::assertStringNotContainsString('Domberg Erfurt', $body);
    }

    #[Test]
    public function preservesEditorPickedOrder(): void
    {
        $request = (new InternalRequest())->withPageId(10);

        $body = (string)$this->executeFrontendSubRequest($request)->getBody();

        // pi_flexform settings.selectedRecords = 3,1 -> Goethehaus must appear before Stadtmuseum
        self::assertLessThan(
            mb_strpos($body, 'Stadtmuseum Erfurt'),
            mb_strpos($body, 'Goethehaus Weimar'),
            'Selected records are not rendered in the editor-picked order.'
        );
    }
}
