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

use PHPUnit\Framework\TestCase;
use WerkraumMedia\ThueCat\Domain\Import\JsonLD\Parser\Address;

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
    public function returnsFallback(): void
    {
        $subject = new Address(
        );

        $result = $subject->get([]);

        self::assertSame([
            'street' => '',
            'zip' => '',
            'city' => '',
            'email' => '',
            'phone' => '',
            'fax' => '',
            'geo' => [
                'latitude' => 0.0,
                'longitude' => 0.0,
            ],
        ], $result);
    }

    /**
     * @test
     */
    public function returnsAddress(): void
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
                ],
            ],
        ]);

        self::assertSame([
            'street' => 'Waagegasse 8',
            'zip' => '99084',
            'city' => 'Erfurt',
            'email' => 'altesynagoge@example.com',
            'phone' => '+49 361 999999',
            'fax' => '+49 361 999998',
            'geo' => [
            'latitude' => 0.0,
            'longitude' => 0.0,
            ],
        ], $result);
    }

    /**
     * @test
     */
    public function returnsGeo(): void
    {
        $subject = new Address(
        );

        $result = $subject->get([
            'schema:geo' => [
                '@id' => 'genid-28b33237f71b41e3ad54a99e1da769b9-b4',
                'schema:longitude' => [
                    '@type' => 'schema:Number',
                    '@value' => '11.029133',
                ],
                'schema:latitude' => [
                    '@type' => 'schema:Number',
                    '@value' => '50.978765',
                ],
            ],
        ]);

        self::assertSame([
            'street' => '',
            'zip' => '',
            'city' => '',
            'email' => '',
            'phone' => '',
            'fax' => '',
            'geo' => [
                'latitude' => 50.978765,
                'longitude' => 11.029133,
            ],
        ], $result);
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
                ],
            ],
            'schema:geo' => [
                '@id' => 'genid-28b33237f71b41e3ad54a99e1da769b9-b4',
                'schema:longitude' => [
                    '@type' => 'schema:Number',
                    '@value' => '11.029133',
                ],
                'schema:latitude' => [
                    '@type' => 'schema:Number',
                    '@value' => '50.978765',
                ],
            ],
        ]);

        self::assertSame([
            'street' => 'Waagegasse 8',
            'zip' => '99084',
            'city' => 'Erfurt',
            'email' => 'altesynagoge@example.com',
            'phone' => '+49 361 999999',
            'fax' => '+49 361 999998',
            'geo' => [
                'latitude' => 50.978765,
                'longitude' => 11.029133,
            ],
        ], $result);
    }
}
