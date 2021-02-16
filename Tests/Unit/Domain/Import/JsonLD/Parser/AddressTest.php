<?php

namespace WerkraumMedia\ThueCat\Tests\Unit\Domain\Import\JsonLD\Parser;

/*
 * Copyright (C) 2021 Daniel Siepmann <coding@daniel-siepmann.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301, USA.
 */

use WerkraumMedia\ThueCat\Domain\Import\JsonLD\Parser\Address;
use PHPUnit\Framework\TestCase;

/**
 * @covers WerkraumMedia\ThueCat\Domain\Import\JsonLD\Parser\Address
 */
class AddressTest extends TestCase
{
    /**
     * @test
     */
    public function instanceCanBeCreated(): void
    {
        $subject = new Address(
        );

        self::assertInstanceOf(Address::class, $subject);
    }

    /**
     * @test
     */
    public function returnsEmptyArrayAsFallback(): void
    {
        $subject = new Address(
        );

        $result = $subject->get([]);

        self::assertSame([], $result);
    }

    /**
     * @test
     */
    public function returnsFullAddress(): void
    {
        $subject = new Address(
        );

        $result = $subject->get([
            'schema:address' => [
                '@id' => 'genid-28b33237f71b41e3ad54a99e1da769b9-b0',
                'schema:addressLocality' => [
                    '@language' => 'de',
                    '@value' => 'Erfurt',
                ],
                'schema:addressCountry' => [
                    '@type' => 'thuecat:AddressCountry',
                    '@value' => 'thuecat:Germany',
                ],
                'schema:postalCode' => [
                    '@language' => 'de',
                    '@value' => '99084',
                ],
                'schema:addressRegion' => [
                    '@type' => 'thuecat:AddressFederalState',
                    '@value' => 'thuecat:Thuringia',
                ],
                'schema:telephone' => [
                    '@language' => 'de',
                    '@value' => '+49 361 999999',
                ],
                'schema:email' => [
                    '@language' => 'de',
                    '@value' => 'altesynagoge@example.com',
                ],
                'schema:streetAddress' => [
                    '@language' => 'de',
                    '@value' => 'Waagegasse 8',
                ],
                'schema:faxNumber' => [
                    '@language' => 'de',
                    '@value' => '+49 361 999998',
                ],
                'thuecat:typOfAddress' => [
                    '@type' => 'thuecat:TypOfAddress',
                    '@value' => 'thuecat:HouseAddress',
                ]
            ],
        ]);

        self::assertSame([
            'street' => 'Waagegasse 8',
            'zip' => '99084',
            'city' => 'Erfurt',
            'email' => 'altesynagoge@example.com',
            'phone' => '+49 361 999999',
            'fax' => '+49 361 999998',
        ], $result);
    }
}
