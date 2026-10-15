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

namespace WerkraumMedia\ThueCat\Tests\Functional\KeywordImport;

use PHPUnit\Framework\Attributes\Test;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractImportTestCase;

final class KeywordSlugImportTest extends AbstractImportTestCase
{
    protected string $fixtureGuzzleBase = __DIR__ . '/Fixtures/Guzzle';

    #[Test]
    public function newKeywordCategoryGetsSlugFromItsTitle(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/KeywordImportPreState.php');
        $this->expectFetch('126981310364-xwgt.json');
        $this->expectFetch('475728955106-qdcc.json');
        $this->expectFetch('155933862969-mofh.json');

        $this->importConfiguration(1);

        self::assertSame(
            'landeshauptstadt-erfurt',
            $this->fetchRowByRemoteId('sys_category', 'keyword:https://thuecat.org/resources/475728955106-qdcc')['slug']
        );
    }
}
