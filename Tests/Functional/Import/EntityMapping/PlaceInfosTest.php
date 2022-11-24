<?php

declare(strict_types=1);

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

namespace WerkraumMedia\ThueCat\Tests\Functional\ObjectMapping;

use PHPUnit\Framework\TestCase;
use WerkraumMedia\ThueCat\Domain\Import\EntityMapper;
use WerkraumMedia\ThueCat\Domain\Import\EntityMapper\JsonDecode;
use WerkraumMedia\ThueCat\Domain\Import\Entity\Place;
use WerkraumMedia\ThueCat\Domain\Import\Entity\Properties\Address;

/**
 * @covers \WerkraumMedia\ThueCat\Domain\Import\EntityMapper
 * @covers \WerkraumMedia\ThueCat\Domain\Import\EntityMapper\JsonDecode
 * @uses \WerkraumMedia\ThueCat\Domain\Import\Entity\Place
 * @uses \WerkraumMedia\ThueCat\Domain\Import\Entity\Properties\Address
 */
class PlaceInfosTest extends TestCase
{
    /**
     * @test
     */
    public function instanceOfPlaceIsReturnedIfRequestes(): void
    {
        $subject = new EntityMapper();

        $result = $subject->mapDataToEntity([
        ], Place::class, [
            JsonDecode::ACTIVE_LANGUAGE => 'de',
        ]);

        self::assertInstanceOf(Place::class, $result);
    }

    /**
     * @test
     */
    public function returnsDefaultValuesIfNotProvidedForMapping(): void
    {
        $subject = new EntityMapper();

        $result = $subject->mapDataToEntity([
        ], Place::class, [
            JsonDecode::ACTIVE_LANGUAGE => 'de',
        ]);

        self::assertInstanceOf(Place::class, $result);
        self::assertSame([], $result->getOpeningHoursSpecification());
    }

    /**
     * @test
     */
    public function mapsIncomingAddress(): void
    {
        $subject = new EntityMapper();

        $result = $subject->mapDataToEntity([
            'schema:address' => [
                'schema:addressLocality' => [
                    '@language' => 'de',
                    '@value' => 'Erfurt',
                ],
                'schema:postalCode' => [
                    0 => [
                        '@language' => 'de',
                        '@value' => '99084',
                    ],
                    1 => [
                        '@language' => 'en',
                        '@value' => '99084',
                    ],
                ],
                'schema:telephone' => [
                    0 => [
                        '@language' => 'de',
                        '@value' => '+49 361 6461265',
                    ],
                    1 => [
                        '@language' => 'en',
                        '@value' => '+49 361 6461265',
                    ],
                ],
                'schema:email' => [
                    0 => [
                        '@language' => 'de',
                        '@value' => 'dominformation@domberg-erfurt.de',
                    ],
                    1 => [
                        '@language' => 'en',
                        '@value' => 'dominformation@domberg-erfurt.de',
                    ],
                ],
                'schema:streetAddress' => [
                    0 => [
                        '@language' => 'de',
                        '@value' => 'Domstufen 1',
                    ],
                    1 => [
                        '@language' => 'en',
                        '@value' => 'Domstufen 1',
                    ],
                ],
            ],
        ], Place::class, [
            JsonDecode::ACTIVE_LANGUAGE => 'de',
        ]);

        self::assertInstanceOf(Place::class, $result);
        self::assertInstanceOf(Address::class, $result->getAddress());
        self::assertSame('Domstufen 1', $result->getAddress()->getStreetAddress());
        self::assertSame('Erfurt', $result->getAddress()->getAddressLocality());
        self::assertSame('99084', $result->getAddress()->getPostalCode());
        self::assertSame('+49 361 6461265', $result->getAddress()->getTelephone());
        self::assertSame('', $result->getAddress()->getFaxNumber());
        self::assertSame('dominformation@domberg-erfurt.de', $result->getAddress()->getEmail());
    }

    /**
     * @test
     */
    public function mapsIncomingMultipleUrls(): void
    {
        $subject = new EntityMapper();

        $result = $subject->mapDataToEntity([
            'schema:url' => [
                0 => [
                    '@type' => 'schema:URL',
                    '@value' => 'https://example.com/1',
                ],
                1 => [
                    '@type' => 'schema:URL',
                    '@value' => 'https://example.com/2',
                ],
            ],
        ], Place::class, [
            JsonDecode::ACTIVE_LANGUAGE => 'de',
        ]);

        self::assertInstanceOf(Place::class, $result);
        self::assertSame([
            'https://example.com/1',
            'https://example.com/2',
        ], $result->getUrls());
    }

    /**
     * @test
     */
    public function mapsIncomingSingleUrl(): void
    {
        $subject = new EntityMapper();

        $result = $subject->mapDataToEntity([
            'schema:url' => [
                '@type' => 'schema:URL',
                '@value' => 'https://example.com/1',
            ],
        ], Place::class, [
            JsonDecode::ACTIVE_LANGUAGE => 'de',
        ]);

        self::assertInstanceOf(Place::class, $result);
        self::assertSame([
            'https://example.com/1',
        ], $result->getUrls());
    }
}
