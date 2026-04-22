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

namespace WerkraumMedia\ThueCat\Tests\Unit\Domain\Import\Parser\Entity\TransientEntity;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WerkraumMedia\ThueCat\Domain\Import\Parser\Entity\TransientEntity\AddressEntity;

class AddressEntityTest extends TestCase
{
    #[Test]
    public function returnsCorrectTable(): void
    {
        $entity = new AddressEntity();

        self::assertSame('tx_thuecat_address', $entity->table);
    }

    #[Test]
    public function extractsAddressWithGeo(): void
    {
        $node = [
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

        ];
        $geo = [
            'schema:latitude' => [
                '@type' => 'schema:Number',
                '@value' => '50.9784118',
            ],
            'schema:longitude' => [
                '@type' => 'schema:Number',
                '@value' => '11.0298392',
            ],
        ];

        $entity = new AddressEntity();
        $entity->configure($node, $geo);
        $result = $entity->toArray();

        $expected = [
            'remote_id' => 'genid-39178cabb01c40e091809d730cb07b5a-b0',
            'street' => 'Benediktsplatz 1',
            'zip' => '99084',
            'city' => 'Erfurt',
            'email' => 'info@erfurt-tourismus.de',
            'phone' => '+49 361 66400',
            'fax' => '+49 361 6640290',
            'geo' => [
                'latitude' => 50.9784118,
                'longitude' => 11.0298392,
            ],
        ];
        self::assertSame('genid-39178cabb01c40e091809d730cb07b5a-b0', $result['remote_id']);
        self::assertSame($expected, $result);
    }

    #[Test]
    public function dataHandlerArrayHasRemoteIdNotPrefixed(): void
    {
        $node = [
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
        ];
        $geo_node = [
            'schema:latitude' => [
                '@type' => 'schema:Number',
                '@value' => '50.9784118',
            ],
            'schema:longitude' => [
                '@type' => 'schema:Number',
                '@value' => '11.0298392',
            ],
        ];

        $entity = new AddressEntity();
        $entity->configure($node, $geo_node);
        $result = $entity->toArray();

        // remote_id is a real column on the Address record; it must stay unprefixed
        // so it survives the REF→UID swap done before DataHandler.
        self::assertStringNotContainsString('REF:', $result['remote_id']);
        self::assertSame('genid-39178cabb01c40e091809d730cb07b5a-b0', $result['remote_id']);
        self::assertArrayNotHasKey('address', $result);
    }
}
