<?php

declare(strict_types=1);

/*
 * Copyright (C) 2024 werkraum-media
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

namespace WerkraumMedia\ThueCat\Tests\Unit\Domain\Import\Parser\Entity;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WerkraumMedia\ThueCat\Domain\Import\Parser\DataHandlerPayload;
use WerkraumMedia\ThueCat\Domain\Import\Parser\Entity\TouristAttractionEntity;

class TouristAttractionEntityTest extends TestCase
{
    #[Test]
    public function returnsCorrectTable(): void
    {
        $entity = new TouristAttractionEntity(['@id' => 'https://thuecat.org/resources/333039283321-xxwg'], new DataHandlerPayload());
        self::assertSame('tx_thuecat_tourist_attraction', $entity->table);
    }

    #[Test]
    public function extractsFlatValues(): void
    {
        $node = [
            '@id' => 'https://thuecat.org/resources/333039283321-xxwg',
            'schema:name' => [
                '@language' => 'de',
                '@value' => 'Erfurt Tourist Information',
            ],
            'schema:description' => [
                '@language' => 'de',
                '@value' => 'Direkt an der Krämerbrücke liegt die Erfurter Tourist Information.',
            ],
            'schema:url' => [
                '@type' => 'schema:URL',
                '@value' => 'https://www.erfurt-tourismus.de',
            ],
        ];

        $entity = new TouristAttractionEntity($node, new DataHandlerPayload());
        $result = $entity->toArray();

        self::assertSame('https://thuecat.org/resources/333039283321-xxwg', $result['remote_id']);
        self::assertSame('Erfurt Tourist Information', $result['title']);
        self::assertSame('Direkt an der Krämerbrücke liegt die Erfurter Tourist Information.', $result['description']);
        self::assertSame('https://www.erfurt-tourismus.de', $result['url']);
        self::assertArrayNotHasKey('address', $result);
    }

    #[Test]
    public function createsChildAddressEntityAndJsonEncodes(): void
    {
        $node = [
            '@id' => 'https://thuecat.org/resources/333039283321-xxwg',
            'schema:name' => [
                '@language' => 'de',
                '@value' => 'Erfurt Tourist Information',
            ],
            'schema:address' => [
                '@id' => 'genid-39178cabb01c40e091809d730cb07b5a-b0',
                'schema:streetAddress' => [
                    '@language' => 'de',
                    '@value' => 'Benediktsplatz 1',
                ],
                'schema:postalCode' => [
                    '@language' => 'de',
                    '@value' => '99084',
                ],
                'schema:addressLocality' => [
                    '@language' => 'de',
                    '@value' => 'Erfurt',
                ],
                'schema:email' => [
                    '@language' => 'de',
                    '@value' => 'info@erfurt-tourismus.de',
                ],
                'schema:telephone' => [
                    '@language' => 'de',
                    '@value' => '+49 361 66400',
                ],
                'schema:faxNumber' => [
                    '@language' => 'de',
                    '@value' => '+49 361 6640290',
                ],

            ],
            'schema:geo' => [
                '@id' => 'genid-39178cabb01c40e091809d730cb07b5a-b2',
                'schema:latitude' => [
                    '@type' => 'schema:Number',
                    '@value' => '50.9784118',
                ],
                'schema:longitude' => [
                    '@type' => 'schema:Number',
                    '@value' => '11.0298392',
                ],
            ],
        ];

        $entity = new TouristAttractionEntity($node, new DataHandlerPayload());
        $result = $entity->toArray();

        $expectedAddress = '{"remote_id":"genid-39178cabb01c40e091809d730cb07b5a-b0","street":"Benediktsplatz 1","zip":"99084","city":"Erfurt","email":"info@erfurt-tourismus.de","phone":"+49 361 66400","fax":"+49 361 6640290","geo":{"latitude":50.9784118,"longitude":11.0298392}}';
        self::assertSame($expectedAddress, $result['address']);
    }

}
