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

namespace WerkraumMedia\ThueCat\Tests\Unit\Import\Parser\Entity\Support;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WerkraumMedia\ThueCat\Import\Parser\Entity\Support\CoordinateNormaliser;

final class CoordinateNormaliserTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function coordinates(): iterable
    {
        yield 'seven decimals stay as delivered' => ['50.9776017', '50.9776017'];
        yield 'fewer decimals are not padded' => ['50.97', '50.97'];
        yield 'an integer stays an integer' => ['50', '50'];
        yield 'excess decimals are rounded' => ['50.819143747641841044655229327227', '50.8191437'];
        yield 'rounding up carries over' => ['10.99999999', '11'];
        yield 'E notation becomes a plain decimal' => ['5.074781E1', '50.74781'];
        yield 'E notation with float noise' => ['9.879951999999999E0', '9.879952'];
        yield 'a negative value' => ['-12.123456789', '-12.1234568'];
        yield 'a value that is no number' => ['n/a', ''];
        yield 'an empty value' => ['', ''];
    }

    #[Test]
    #[DataProvider('coordinates')]
    public function writesAPlainDecimalOfAtMostSevenDecimals(string $delivered, string $expected): void
    {
        $this->assertSame($expected, new CoordinateNormaliser()->normalise($delivered));
    }
}
