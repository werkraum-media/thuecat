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

namespace WerkraumMedia\ThueCat\Tests\Unit\Import\Parser\Entity;

use PHPUnit\Framework\Attributes\Test;
use WerkraumMedia\ThueCat\Import\Parser\Entity\AddressEntity;
use WerkraumMedia\ThueCat\Import\Parser\Entity\OpeningHourSpecificationEntity;
use WerkraumMedia\ThueCat\Import\Parser\Entity\ParkingFacilityEntity;
use WerkraumMedia\ThueCat\Import\Parser\ParserContext;

class ParkingFacilityEntityTest extends AbstractImportTestCase
{
    #[Test]
    public function returnsCorrectTable(): void
    {
        $entity = new ParkingFacilityEntity();

        $this->assertSame('tx_thuecat_parking_facility', $entity::TABLE);
    }

    #[Test]
    public function handlesSchemaParkingFacilityType(): void
    {
        $entity = new ParkingFacilityEntity();

        $this->assertContains('schema:ParkingFacility', $entity->handlesTypes());
    }

    #[Test]
    public function extractsFlatValuesFromFixture(): void
    {
        $node = $this->nodeFromFixture('396420044896-drzt.json', 'schema:ParkingFacility');
        $this->assertNotNull($node);
        $entity = new ParkingFacilityEntity();
        $entity->parse($node, 'de', new ParserContext(0));

        $row = $entity->toArray();

        $this->assertSame('https://thuecat.org/resources/396420044896-drzt', $row['remote_id']);
        $this->assertSame('Parkhaus Domplatz', $row['title']);
        $this->assertStringStartsWith('Das Parkhaus Domplatz', (string)$row['description']);
        $this->assertSame('ZeroSanitation', $row['sanitation']);
        $this->assertSame('ZeroOtherServiceEnumMem', $row['other_service']);
        $this->assertSame('ElectricVehicleCarChargingStationEnumMem', $row['traffic_infrastructure']);
        $this->assertSame('240:MTR:CityBus', $row['distance_to_public_transport']);
    }

    #[Test]
    public function rowOmitsColumnsNotInTcaForParkingFacility(): void
    {
        // ParkingFacility TCA is narrower than TouristAttraction — no url,
        // slogan, pets_allowed, public_access, museum_service, digital_offer,
        // photography, architectural_style, available_languages,
        // is_accessible_for_free, start_of_construction, accessibility_spec.
        // The parser must not surface those columns even when the fixture
        // happens to carry equivalent JSON-LD (e.g. schema:url, schema:petsAllowed).
        $node = $this->nodeFromFixture('396420044896-drzt.json', 'schema:ParkingFacility');
        $this->assertNotNull($node);
        $entity = new ParkingFacilityEntity();
        $entity->parse($node, 'de', new ParserContext(0));

        $row = $entity->toArray();

        $this->assertArrayNotHasKey('url', $row);
        $this->assertArrayNotHasKey('slogan', $row);
        $this->assertArrayNotHasKey('pets_allowed', $row);
        $this->assertArrayNotHasKey('public_access', $row);
        $this->assertArrayNotHasKey('is_accessible_for_free', $row);
        $this->assertArrayNotHasKey('museum_service', $row);
        $this->assertArrayNotHasKey('architectural_style', $row);
        $this->assertArrayNotHasKey('digital_offer', $row);
        $this->assertArrayNotHasKey('photography', $row);
        $this->assertArrayNotHasKey('available_languages', $row);
        $this->assertArrayNotHasKey('start_of_construction', $row);
        $this->assertArrayNotHasKey('accessibility_specification', $row);
    }

    #[Test]
    public function rowOmitsResolverOwnedColumns(): void
    {
        $node = $this->nodeFromFixture('396420044896-drzt.json', 'schema:ParkingFacility');
        $this->assertNotNull($node);
        $entity = new ParkingFacilityEntity();
        $entity->parse($node, 'de', new ParserContext(0));

        $row = $entity->toArray();

        $this->assertArrayNotHasKey('town', $row);
        $this->assertArrayNotHasKey('managed_by', $row);
        $this->assertArrayNotHasKey('media', $row);
    }

    #[Test]
    public function createsChildAddressEntity(): void
    {
        $node = $this->nodeFromFixture('396420044896-drzt.json', 'schema:ParkingFacility');
        $this->assertNotNull($node);
        $entity = new ParkingFacilityEntity();
        $entity->parse($node, 'de', new ParserContext(0));

        // The legacy blob column is never written any more; the address is a
        // staged child row.
        $this->assertArrayNotHasKey('address', $entity->toArray());

        $addresses = $this->childRowsOf($entity, AddressEntity::TABLE);
        $this->assertCount(1, $addresses);
        $address = $addresses[0];

        $this->assertSame('https://thuecat.org/resources/396420044896-drzt::addr::0', $address['remote_id']);
        $this->assertSame('Bechtheimer Str. 1', $address['street']);
        $this->assertSame('99084', $address['zip']);
        $this->assertSame('Erfurt', $address['city']);
        $this->assertSame('+49 361 5640', $address['phone']);
        $this->assertSame('50.977648905044', $address['latitude']);
    }

    #[Test]
    public function capturesContainedInPlaceAsTransient(): void
    {
        // The Parkhaus fixture carries schema:containedInPlace as a single
        // {"@id"} object (not a list). recordTransient must normalise both
        // shapes into the same list<string> bucket.
        $node = $this->nodeFromFixture('396420044896-drzt.json', 'schema:ParkingFacility');
        $this->assertNotNull($node);
        $entity = new ParkingFacilityEntity();
        $entity->parse($node, 'de', new ParserContext(0));

        $transients = $entity->getTransients();

        $this->assertArrayHasKey('containedInPlace', $transients);
        $this->assertSame(['https://thuecat.org/resources/508431710173-wwne'], $transients['containedInPlace']);
    }

    #[Test]
    public function capturesManagedByFromTopLevelThuecatField(): void
    {
        // ParkingFacility uses thuecat:managedBy directly, where attractions
        // encode the same relation as thuecat:contentResponsible. The bucket
        // key is 'managedBy' in both cases so the resolver treats them the same.
        $node = $this->nodeFromFixture('396420044896-drzt.json', 'schema:ParkingFacility');
        $this->assertNotNull($node);
        $entity = new ParkingFacilityEntity();
        $entity->parse($node, 'de', new ParserContext(0));

        $transients = $entity->getTransients();

        $this->assertArrayHasKey('managedBy', $transients);
        $this->assertSame(['https://thuecat.org/resources/018132452787-ngbe'], $transients['managedBy']);
    }

    #[Test]
    public function mergesImageAndPhotoRefsIntoSingleMediaBucket(): void
    {
        // Parkhaus has schema:image and schema:photo both pointing at the same
        // dms_* resource. Entries are `{kind, id}` tuples, photo-first, so the
        // resolver can set mainImage:true on the schema:photo emission and
        // mainImage:false on the schema:image one.
        $node = $this->nodeFromFixture('396420044896-drzt.json', 'schema:ParkingFacility');
        $this->assertNotNull($node);
        $entity = new ParkingFacilityEntity();
        $entity->parse($node, 'de', new ParserContext(0));

        $transients = $entity->getTransients();

        $this->assertArrayHasKey('media', $transients);
        $this->assertSame([
            ['kind' => 'photo', 'id' => 'https://thuecat.org/resources/dms_6486108'],
            ['kind' => 'image', 'id' => 'https://thuecat.org/resources/dms_6486108'],
        ], $transients['media']);
    }

    #[Test]
    public function doesNotCaptureAccessibilitySpecificationTransient(): void
    {
        // ParkingFacility's TCA has no accessibility_specification column, and
        // the fixture carries no thuecat:accessibilitySpecification node.
        // Bucket must stay absent (not present-but-empty).
        $node = $this->nodeFromFixture('396420044896-drzt.json', 'schema:ParkingFacility');
        $this->assertNotNull($node);
        $entity = new ParkingFacilityEntity();
        $entity->parse($node, 'de', new ParserContext(0));

        $this->assertArrayNotHasKey('accessibilitySpecification', $entity->getTransients());
    }

    #[Test]
    public function doesNotCaptureParkingFacilityNearByTransient(): void
    {
        // parkingFacilityNearBy is an attraction-side relation. The parking
        // facility is the target, not the source — no reverse bucket here.
        $node = $this->nodeFromFixture('396420044896-drzt.json', 'schema:ParkingFacility');
        $this->assertNotNull($node);
        $entity = new ParkingFacilityEntity();
        $entity->parse($node, 'de', new ParserContext(0));

        $this->assertArrayNotHasKey('parkingFacilityNearBy', $entity->getTransients());
    }

    #[Test]
    public function buildsOpeningHourSpecificationChildrenOnePerDay(): void
    {
        // Two specs: 07–22 for Mon–Sat (6 days) and 09–22 for Sun + PublicHolidays
        // (2 days). Multi-day specs expand to one child row per day → 8 rows.
        $node = $this->nodeFromFixture('396420044896-drzt.json', 'schema:ParkingFacility');
        $this->assertNotNull($node);
        $entity = new ParkingFacilityEntity();
        $entity->parse($node, 'de', new ParserContext(0));

        $rows = $this->childRowsOf($entity, OpeningHourSpecificationEntity::TABLE);

        $this->assertCount(8, $rows);
        $byDay = array_column($rows, null, 'day_of_week');
        $this->assertSame('07:00:00', $byDay['Monday']['opens']);
        $this->assertSame('22:00:00', $byDay['Monday']['closes']);
        // Parent linkage rides in the remote_id prefix (the Resolver reads it
        // back to wire the inline FK); the child carries no parenttable itself.
        $this->assertStringContainsString('::oh::', (string)$byDay['Monday']['remote_id']);
        $this->assertSame('09:00:00', $byDay['Sunday']['opens']);
        $this->assertArrayHasKey('PublicHolidays', $byDay);
    }

    #[Test]
    public function encodesOffersListAsJsonBlob(): void
    {
        // Parkhaus offers carry no schema:name, so title / description fall
        // back to '' (matches the legacy golden shape for language mismatches).
        $node = $this->nodeFromFixture('396420044896-drzt.json', 'schema:ParkingFacility');
        $this->assertNotNull($node);
        $entity = new ParkingFacilityEntity();
        $entity->parse($node, 'de', new ParserContext(0));

        $decoded = $this->decodeJson($entity->toArray()['offers']);

        $this->assertCount(4, $decoded);
        // @phpstan-ignore offsetAccess.nonOffsetAccessible (this array is artificially constructed, so we trust it here)
        $this->assertSame(['ParkingFee'], $decoded[0]['types']);
        // @phpstan-ignore offsetAccess.nonOffsetAccessible (this array is artificially constructed, so we trust it here)
        $this->assertSame('', $decoded[0]['title']);
        // @phpstan-ignore offsetAccess.nonOffsetAccessible (this array is artificially constructed, so we trust it here)
        $this->assertCount(1, $decoded[0]['prices']);
        // Whole-number prices json_encode as "35" (no decimal) and decode
        // back as int — same round-trip behaviour the attraction test relies
        // on for price 8.

        $this->assertSame(
            [
                'title' => '',
                'description' => '',
                'price' => 35,
                'currency' => 'EUR',
                'rule' => 'PerCar',
            ],
            // @phpstan-ignore offsetAccess.nonOffsetAccessible, offsetAccess.nonOffsetAccessible (this array is artificially constructed, so we trust it here)
            $decoded[0]['prices'][0]
        );
    }

    #[Test]
    public function transientsAreEmptyWhenNodeLacksRelations(): void
    {
        $entity = new ParkingFacilityEntity();
        $entity->parse([
            '@id' => 'https://thuecat.org/resources/no-relations',
            '@type' => ['schema:ParkingFacility'],
        ], 'de', new ParserContext(0));

        $this->assertSame([], $entity->getTransients());
    }
}
