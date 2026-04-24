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
use WerkraumMedia\ThueCat\Domain\Import\Parser\Entity\ParkingFacilityEntity;

class ParkingFacilityEntityTest extends AbstractImportTestCase
{
    #[Test]
    public function returnsCorrectTable(): void
    {
        $entity = new ParkingFacilityEntity();

        self::assertSame('tx_thuecat_parking_facility', $entity->table);
    }

    #[Test]
    public function handlesSchemaParkingFacilityType(): void
    {
        $entity = new ParkingFacilityEntity();

        self::assertContains('schema:ParkingFacility', $entity->handlesTypes());
    }

    #[Test]
    public function extractsFlatValuesFromFixture(): void
    {
        $node = $this->nodeFromFixture('396420044896-drzt.json', 'schema:ParkingFacility');
        self::assertNotNull($node);
        $entity = new ParkingFacilityEntity();
        $entity->parse($node, 'de');

        $row = $entity->toArray();

        self::assertSame('https://thuecat.org/resources/396420044896-drzt', $row['remote_id']);
        self::assertSame('Parkhaus Domplatz', $row['title']);
        self::assertStringStartsWith('Das Parkhaus Domplatz', (string)$row['description']);
        self::assertSame('ZeroSanitation', $row['sanitation']);
        self::assertSame('ZeroOtherServiceEnumMem', $row['other_service']);
        self::assertSame('ElectricVehicleCarChargingStationEnumMem', $row['traffic_infrastructure']);
        self::assertSame('240:MTR:CityBus', $row['distance_to_public_transport']);
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
        self::assertNotNull($node);
        $entity = new ParkingFacilityEntity();
        $entity->parse($node, 'de');

        $row = $entity->toArray();

        self::assertArrayNotHasKey('url', $row);
        self::assertArrayNotHasKey('slogan', $row);
        self::assertArrayNotHasKey('pets_allowed', $row);
        self::assertArrayNotHasKey('public_access', $row);
        self::assertArrayNotHasKey('is_accessible_for_free', $row);
        self::assertArrayNotHasKey('museum_service', $row);
        self::assertArrayNotHasKey('architectural_style', $row);
        self::assertArrayNotHasKey('digital_offer', $row);
        self::assertArrayNotHasKey('photography', $row);
        self::assertArrayNotHasKey('available_languages', $row);
        self::assertArrayNotHasKey('start_of_construction', $row);
        self::assertArrayNotHasKey('accessibility_specification', $row);
    }

    #[Test]
    public function rowOmitsResolverOwnedColumns(): void
    {
        $node = $this->nodeFromFixture('396420044896-drzt.json', 'schema:ParkingFacility');
        self::assertNotNull($node);
        $entity = new ParkingFacilityEntity();
        $entity->parse($node, 'de');

        $row = $entity->toArray();

        self::assertArrayNotHasKey('town', $row);
        self::assertArrayNotHasKey('managed_by', $row);
        self::assertArrayNotHasKey('media', $row);
    }

    #[Test]
    public function createsChildAddressEntityAndJsonEncodes(): void
    {
        $node = $this->nodeFromFixture('396420044896-drzt.json', 'schema:ParkingFacility');
        self::assertNotNull($node);
        $entity = new ParkingFacilityEntity();
        $entity->parse($node, 'de');

        $address = $this->decodeJson($entity->toArray()['address']);

        self::assertSame('Bechtheimer Str. 1', $address['street']);
        self::assertSame('99084', $address['zip']);
        self::assertSame('Erfurt', $address['city']);
        self::assertSame('+49 361 5640', $address['phone']);
        self::assertSame(50.977648905044, $address['geo']['latitude']);
    }

    #[Test]
    public function capturesContainedInPlaceAsTransient(): void
    {
        // The Parkhaus fixture carries schema:containedInPlace as a single
        // {"@id"} object (not a list). recordTransient must normalise both
        // shapes into the same list<string> bucket.
        $node = $this->nodeFromFixture('396420044896-drzt.json', 'schema:ParkingFacility');
        self::assertNotNull($node);
        $entity = new ParkingFacilityEntity();
        $entity->parse($node, 'de');

        $transients = $entity->getTransients();

        self::assertArrayHasKey('containedInPlace', $transients);
        self::assertSame(
            ['https://thuecat.org/resources/508431710173-wwne'],
            $transients['containedInPlace']
        );
    }

    #[Test]
    public function capturesManagedByFromTopLevelThuecatField(): void
    {
        // ParkingFacility uses thuecat:managedBy directly, where attractions
        // encode the same relation as thuecat:contentResponsible. The bucket
        // key is 'managedBy' in both cases so the resolver treats them the same.
        $node = $this->nodeFromFixture('396420044896-drzt.json', 'schema:ParkingFacility');
        self::assertNotNull($node);
        $entity = new ParkingFacilityEntity();
        $entity->parse($node, 'de');

        $transients = $entity->getTransients();

        self::assertArrayHasKey('managedBy', $transients);
        self::assertSame(
            ['https://thuecat.org/resources/570107928040-rfze'],
            $transients['managedBy']
        );
    }

    #[Test]
    public function mergesImageAndPhotoRefsIntoSingleMediaBucket(): void
    {
        // Parkhaus has schema:image and schema:photo both pointing at the same
        // dms_* resource. Entries are `{kind, id}` tuples, photo-first, so the
        // resolver can set mainImage:true on the schema:photo emission and
        // mainImage:false on the schema:image one.
        $node = $this->nodeFromFixture('396420044896-drzt.json', 'schema:ParkingFacility');
        self::assertNotNull($node);
        $entity = new ParkingFacilityEntity();
        $entity->parse($node, 'de');

        $transients = $entity->getTransients();

        self::assertArrayHasKey('media', $transients);
        self::assertSame([
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
        self::assertNotNull($node);
        $entity = new ParkingFacilityEntity();
        $entity->parse($node, 'de');

        self::assertArrayNotHasKey('accessibilitySpecification', $entity->getTransients());
    }

    #[Test]
    public function doesNotCaptureParkingFacilityNearByTransient(): void
    {
        // parkingFacilityNearBy is an attraction-side relation. The parking
        // facility is the target, not the source — no reverse bucket here.
        $node = $this->nodeFromFixture('396420044896-drzt.json', 'schema:ParkingFacility');
        self::assertNotNull($node);
        $entity = new ParkingFacilityEntity();
        $entity->parse($node, 'de');

        self::assertArrayNotHasKey('parkingFacilityNearBy', $entity->getTransients());
    }

    #[Test]
    public function encodesOpeningHoursListAsJsonBlob(): void
    {
        $node = $this->nodeFromFixture('396420044896-drzt.json', 'schema:ParkingFacility');
        self::assertNotNull($node);
        $entity = new ParkingFacilityEntity();
        $entity->parse($node, 'de');

        $decoded = $this->decodeJson($entity->toArray()['opening_hours']);

        self::assertCount(2, $decoded);
        self::assertSame('07:00:00', $decoded[0]['opens']);
        self::assertSame('22:00:00', $decoded[0]['closes']);
        self::assertContains('Monday', $decoded[0]['daysOfWeek']);
    }

    #[Test]
    public function encodesOffersListAsJsonBlob(): void
    {
        // Parkhaus offers carry no schema:name, so title / description fall
        // back to '' (matches the legacy golden shape for language mismatches).
        $node = $this->nodeFromFixture('396420044896-drzt.json', 'schema:ParkingFacility');
        self::assertNotNull($node);
        $entity = new ParkingFacilityEntity();
        $entity->parse($node, 'de');

        $decoded = $this->decodeJson($entity->toArray()['offers']);

        self::assertCount(4, $decoded);
        self::assertSame(['ParkingFee'], $decoded[0]['types']);
        self::assertSame('', $decoded[0]['title']);
        self::assertCount(1, $decoded[0]['prices']);
        // Whole-number prices json_encode as "35" (no decimal) and decode
        // back as int — same round-trip behaviour the attraction test relies
        // on for price 8.
        self::assertSame([
            'title' => '',
            'description' => '',
            'price' => 35,
            'currency' => 'EUR',
            'rule' => 'PerCar',
        ], $decoded[0]['prices'][0]);
    }

    #[Test]
    public function transientsAreEmptyWhenNodeLacksRelations(): void
    {
        $entity = new ParkingFacilityEntity();
        $entity->parse([
            '@id' => 'https://thuecat.org/resources/no-relations',
            '@type' => ['schema:ParkingFacility'],
        ], 'de');

        self::assertSame([], $entity->getTransients());
    }
}
