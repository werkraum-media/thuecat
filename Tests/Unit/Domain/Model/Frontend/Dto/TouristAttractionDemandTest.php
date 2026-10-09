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

namespace WerkraumMedia\ThueCat\Tests\Unit\Domain\Model\Frontend\Dto;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Dto\TouristAttractionDemand;

final class TouristAttractionDemandTest extends TestCase
{
    #[Test]
    public function queryParametersNeverCarryTheSortOrder(): void
    {
        $demand = new TouristAttractionDemand();
        $demand->setSortBy('sorting');
        $demand->setTowns([1]);

        self::assertSame(['towns' => [1]], $demand->getQueryParameters());
    }
}
