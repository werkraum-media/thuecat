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

namespace WerkraumMedia\ThueCat\Tests\Unit\Import\Parser\Entity;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WerkraumMedia\ThueCat\Import\Parser\Entity\AddressEntity;

class AddressEntityTest extends TestCase
{
    #[Test]
    public function returnsCorrectTable(): void
    {
        $entity = new AddressEntity();

        self::assertSame('tx_thuecat_address', $entity::TABLE);
    }

    #[Test]
    public function extractsAddressFromListOfLanguageVariants(): void
    {
        // No @id: identity is derived from the parent, not the source node.
        $node = [
            'schema:streetAddress' => [
                ['@language' => 'de', '@value' => 'Beispielweg 5'],
                ['@language' => 'en', '@value' => 'Example Lane 5'],
            ],
            'schema:postalCode' => [
                ['@language' => 'de', '@value' => '99425'],
                ['@language' => 'en', '@value' => '99423'],
            ],
            'schema:addressLocality' => [
                ['@language' => 'de', '@value' => 'Beispielstadt'],
                ['@language' => 'en', '@value' => 'Beispielstadt'],
            ],
            'schema:email' => [
                ['@language' => 'de', '@value' => 'info@example.com'],
            ],
            'schema:telephone' => [
                ['@language' => 'de', '@value' => '+49 3643 545400'],
            ],
            'schema:faxNumber' => [
                ['@language' => 'de', '@value' => '+49 3643 545401'],
            ],
        ];

        $entity = new AddressEntity();
        $entity->configure($node, 'de');
        $result = $entity->toArray();

        self::assertSame('Beispielweg 5', $result['street'] ?? '');
        self::assertSame('99425', $result['zip'] ?? '');
        self::assertSame('Beispielstadt', $result['city'] ?? '');
        self::assertSame('info@example.com', $result['email'] ?? '');
        self::assertSame('+49 3643 545400', $result['phone'] ?? '');
        self::assertSame('+49 3643 545401', $result['fax'] ?? '');
    }

    #[Test]
    public function extractsAddressForRequestedLanguageFromList(): void
    {
        $node = [
            'schema:streetAddress' => [
                ['@language' => 'de', '@value' => 'Beispielweg 5'],
                ['@language' => 'en', '@value' => 'Example Lane 5'],
            ],
            'schema:postalCode' => [
                ['@language' => 'de', '@value' => '99425'],
                ['@language' => 'en', '@value' => '99423'],
            ],
        ];

        $entity = new AddressEntity();
        $entity->configure($node, 'en');
        $result = $entity->toArray();

        self::assertSame('Example Lane 5', $result['street'] ?? '');
        self::assertSame('99423', $result['zip'] ?? '');
    }

    /**
     * The source node's @id is a blank node: a serialisation artefact, not an
     * upstream key, so it cannot match a child to its existing row.
     */
    #[Test]
    public function derivesRemoteIdFromParentNotFromSourceId(): void
    {
        $entity = new AddressEntity();
        $entity->configure(
            ['@id' => 'genid-unstable-blank-node'],
            'de',
            [],
            'https://thuecat.org/resources/043064193523-jkgh'
        );

        self::assertSame(
            'https://thuecat.org/resources/043064193523-jkgh::addr::0',
            $entity->toArray()['remote_id'] ?? ''
        );
    }

    /**
     * Translation status is keyed by remote_id without the table, so a child
     * sharing its parent's id would short-circuit the other's handling.
     */
    #[Test]
    public function derivedRemoteIdCannotCollideWithParent(): void
    {
        $parentRemoteId = 'https://thuecat.org/resources/043064193523-jkgh';

        $entity = new AddressEntity();
        $entity->configure([], 'de', [], $parentRemoteId);
        $childRemoteId = (string)($entity->toArray()['remote_id'] ?? '');

        self::assertNotSame($parentRemoteId, $childRemoteId);
        self::assertStringStartsWith($parentRemoteId . '::addr::0', $childRemoteId);
    }

    /**
     * The ordinal keeps each child's identity stable across re-imports, so
     * several addresses update in place rather than stacking.
     */
    #[Test]
    public function ordinalDistinguishesSeveralAddressesOfOneParent(): void
    {
        $first = new AddressEntity();
        $first->configure([], 'de', [], 'https://thuecat.org/resources/x', 0);
        $second = new AddressEntity();
        $second->configure([], 'de', [], 'https://thuecat.org/resources/x', 1);

        self::assertSame('https://thuecat.org/resources/x::addr::0', $first->toArray()['remote_id']);
        self::assertSame('https://thuecat.org/resources/x::addr::1', $second->toArray()['remote_id']);
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
        $entity->configure($node, 'de', $geo, 'https://thuecat.org/resources/333039283321-xxwg');
        $result = $entity->toArray();

        // Coordinates are their own columns, kept as delivered.
        $expected = [
            'remote_id' => 'https://thuecat.org/resources/333039283321-xxwg::addr::0',
            'street' => 'Benediktsplatz 1',
            'zip' => '99084',
            'city' => 'Erfurt',
            'email' => 'info@erfurt-tourismus.de',
            'phone' => '+49 361 66400',
            'fax' => '+49 361 6640290',
            'latitude' => '50.9784118',
            'longitude' => '11.0298392',
        ];
        self::assertSame($expected, $result);
    }

    #[Test]
    public function dropsPartialCoordinatePair(): void
    {
        $entity = new AddressEntity();
        $entity->configure(
            [],
            'de',
            ['schema:latitude' => ['@type' => 'schema:Number', '@value' => '50.9784118']],
            'https://thuecat.org/resources/333039283321-xxwg'
        );
        $result = $entity->toArray();

        self::assertArrayNotHasKey('latitude', $result);
        self::assertArrayNotHasKey('longitude', $result);
    }

    #[Test]
    public function recordsTranslationsForTextFieldsOnly(): void
    {
        $node = [
            'schema:streetAddress' => [
                ['@language' => 'de', '@value' => 'Beispielweg 5'],
                ['@language' => 'en', '@value' => 'Example Lane 5'],
            ],
            'schema:postalCode' => [
                ['@language' => 'de', '@value' => '99425'],
                ['@language' => 'en', '@value' => '99423'],
            ],
        ];

        $entity = new AddressEntity();
        $entity->configure($node, 'de', [], 'https://thuecat.org/resources/043064193523-jkgh');
        $entity->configureTranslation($node, 'en', 1);

        self::assertSame(
            [1 => ['street' => 'Example Lane 5', 'zip' => '99423']],
            $entity->getTranslations()
        );
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
        $entity->configure($node, 'de', $geo_node, 'https://thuecat.org/resources/333039283321-xxwg');
        $result = $entity->toArray();

        // remote_id is a real column on the Address record; it must stay unprefixed
        // so it survives the REF→UID swap done before DataHandler.
        self::assertStringNotContainsString('REF:', (string)$result['remote_id']);
        self::assertSame('https://thuecat.org/resources/333039283321-xxwg::addr::0', $result['remote_id']);
        self::assertArrayNotHasKey('address', $result);
    }
}
