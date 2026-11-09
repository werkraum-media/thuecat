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
use WerkraumMedia\ThueCat\Import\Parser\Entity\Support\QuantityNormaliser;

final class QuantityNormaliserTest extends TestCase
{
    private const array UNKNOWN_UNIT = [
        'schema:unitCode' => ['@type' => 'thuecat:Unit', '@value' => 'test:UnknownUnit'],
        'schema:value' => ['@type' => 'schema:Number', '@value' => '3'],
    ];

    /**
     * @return iterable<string, array{array<string, mixed>, float}>
     */
    public static function knownUnits(): iterable
    {
        yield 'kilometres' => [[
            'schema:unitCode' => ['@type' => 'thuecat:Unit', '@value' => 'thuecat:KTM'],
            'schema:value' => ['@type' => 'schema:Number', '@value' => '4.5'],
        ], 4500.0];
        yield 'metres' => [[
            'schema:unitCode' => ['@type' => 'thuecat:Unit', '@value' => 'thuecat:MTR'],
            'schema:value' => ['@type' => 'schema:Number', '@value' => '900'],
        ], 900.0];
        yield 'room height in metres' => [[
            'schema:unitCode' => ['@type' => 'thuecat:Unit', '@value' => 'thuecat:MTR'],
            'schema:value' => ['@type' => 'schema:Number', '@value' => '11.5'],
        ], 11.5];
        yield 'square metres' => [[
            'schema:unitCode' => ['@type' => 'thuecat:Unit', '@value' => 'thuecat:MTK'],
            'schema:value' => ['@type' => 'schema:Number', '@value' => '110'],
        ], 110.0];
        yield 'hectares' => [[
            'schema:unitCode' => ['@type' => 'thuecat:Unit', '@value' => 'thuecat:HAR'],
            'schema:value' => ['@type' => 'schema:Integer', '@value' => '36'],
        ], 360000.0];
    }

    /**
     * @param array<string, mixed> $quantity
     */
    #[Test]
    #[DataProvider('knownUnits')]
    public function convertsKnownUnitToMetresOrSquareMetres(array $quantity, float $expected): void
    {
        $this->assertSame($expected, new QuantityNormaliser()->normalise($quantity));
    }

    #[Test]
    public function dropsValueOfUnknownUnit(): void
    {
        $this->assertNull(new QuantityNormaliser()->normalise(self::UNKNOWN_UNIT));
    }

    #[Test]
    public function namesTheUnitOfAQuantity(): void
    {
        $this->assertSame('test:UnknownUnit', new QuantityNormaliser()->unitOf(self::UNKNOWN_UNIT));
    }

    #[Test]
    public function dropsQuantityWithoutValue(): void
    {
        $this->assertNull(new QuantityNormaliser()->normalise([
            'schema:unitCode' => ['@type' => 'thuecat:Unit', '@value' => 'thuecat:KTM'],
        ]));
    }
}
