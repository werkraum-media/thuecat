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

namespace WerkraumMedia\ThueCat\Tests\Functional\Import;

use PHPUnit\Framework\Attributes\Test;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractImportTestCase;

final class TownSlugImportTest extends AbstractImportTestCase
{
    #[Test]
    public function newTownGetsSlugFromItsTitle(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/TwoSitesPreState.php');
        $this->expectFetch('043064193523-jcyt.json');
        $this->expectFetch('018132452787-ngbe.json');

        $this->importConfiguration(1);

        self::assertSame(
            'erfurt',
            $this->fetchRowByRemoteId('tx_thuecat_town', 'https://thuecat.org/resources/043064193523-jcyt')['slug']
        );
    }

    #[Test]
    public function sameTownInTwoSitesGetsTheSameSlugInEach(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/TwoSitesPreState.php');
        $this->expectFetch('043064193523-jcyt.json');
        $this->expectFetch('018132452787-ngbe.json');

        $this->importConfiguration(1);
        $this->importConfiguration(2);

        self::assertSame(['erfurt', 'erfurt'], array_column($this->fetchChildren('tx_thuecat_town'), 'slug'));
    }
}
