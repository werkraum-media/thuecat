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
use WerkraumMedia\ThueCat\Domain\Import\Parser\Entity\TouristAttractionEntity;

class TouristAttractionEntityTest extends AbstractImportTestCase
{
    #[Test]
    public function returnsCorrectTable(): void
    {
        $entity = new TouristAttractionEntity();

        self::assertSame('tx_thuecat_tourist_attraction', $entity->table);
    }

    #[Test]
    public function handlesSchemaTouristAttractionType(): void
    {
        // Regression guard: handlesTypes() previously returned []; the Parser
        // resolver silently skipped every TouristAttraction node.
        $entity = new TouristAttractionEntity();

        self::assertContains('schema:TouristAttraction', $entity->handlesTypes());
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

        $entity = new TouristAttractionEntity();
        $entity->configure($node, 'de');
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

        $entity = new TouristAttractionEntity();
        $entity->configure($node, 'de');
        $result = $entity->toArray();

        $expectedAddress = '{"remote_id":"genid-39178cabb01c40e091809d730cb07b5a-b0","street":"Benediktsplatz 1","zip":"99084","city":"Erfurt","email":"info@erfurt-tourismus.de","phone":"+49 361 66400","fax":"+49 361 6640290","geo":{"latitude":50.9784118,"longitude":11.0298392}}';
        self::assertSame($expectedAddress, $result['address']);
    }

    #[Test]
    public function extractsFlatEnumAndValueFields(): void
    {
        // Golden values are the sys_language_uid=0 row for 165868194223-zmqf in
        // Tests/Unit/Domain/Import/Parser/Assertions/ImportsTouristAttractionsWithRelations.php.
        $node = $this->nodeFromFixture('165868194223-zmqf.json', 'schema:TouristAttraction');
        self::assertNotNull($node);
        $entity = new TouristAttractionEntity();
        $entity->configure($node, 'de');

        $row = $entity->toArray();

        self::assertSame('Highlight', $row['slogan']);
        self::assertSame('11. Jh.', $row['start_of_construction']);
        self::assertSame('Toilets,DisabledToilets,NappyChangingArea,FamilyAndChildFriendly', $row['sanitation']);
        self::assertSame('SeatingPossibilitiesRestArea,LockBoxes,SouvenirShop,BaggageStorage', $row['other_service']);
        self::assertSame('MuseumShop', $row['museum_service']);
        self::assertSame('GothicArt', $row['architectural_style']);
        self::assertSame('ZeroSpecialTrafficInfrastructure', $row['traffic_infrastructure']);
        self::assertSame('CashPayment,EC', $row['payment_accepted']);
        self::assertSame('AudioGuide,VideoGuide', $row['digital_offer']);
        self::assertSame('ZeroPhotography', $row['photography']);
        self::assertSame('Tiere sind im Gebäude nicht gestattet, ausgenommen sind Blinden- und Blindenbegleithunde.', $row['pets_allowed']);
        self::assertSame('false', $row['is_accessible_for_free']);
        self::assertSame('true', $row['public_access']);
        self::assertSame('German,English,French', $row['available_languages']);
        self::assertSame('200:MTR:CityBus', $row['distance_to_public_transport']);
    }

    #[Test]
    public function distanceToPublicTransportJoinsMultipleMeansOfTransport(): void
    {
        // Dom fixture carries two means (Streetcar + CityBus) as a list; the
        // result is a single colon-joined string.
        $node = $this->nodeFromFixture('835224016581-dara.json', 'schema:TouristAttraction');
        self::assertNotNull($node);
        $entity = new TouristAttractionEntity();
        $entity->configure($node, 'de');

        self::assertSame('350:MTR:Streetcar:CityBus', $entity->toArray()['distance_to_public_transport']);
    }

    #[Test]
    public function rowOmitsRelationFieldsForResolverToFill(): void
    {
        // Resolver-owned columns: parser mustn't pre-fill them. The JSON-LD
        // stub only carries @id, and containedInPlace mixes several place types.
        $node = $this->nodeFromFixture('165868194223-zmqf.json', 'schema:TouristAttraction');
        self::assertNotNull($node);
        $entity = new TouristAttractionEntity();
        $entity->configure($node, 'de');

        $row = $entity->toArray();

        self::assertArrayNotHasKey('town', $row);
        self::assertArrayNotHasKey('managed_by', $row);
        self::assertArrayNotHasKey('parking_facility_near_by', $row);
        // accessibility_specification is resolver-owned too: the JSON-LD only
        // carries an @id pointing at a separate resource we can't fetch here.
        self::assertArrayNotHasKey('accessibility_specification', $row);
        // media follows the same fetch-and-shape-to-JSON path: schema:image /
        // schema:photo / schema:video all point at separate dms_* resources.
        self::assertArrayNotHasKey('media', $row);
    }

    #[Test]
    public function capturesContainedInPlaceRefsAsTransient(): void
    {
        $node = $this->nodeFromFixture('165868194223-zmqf.json', 'schema:TouristAttraction');
        self::assertNotNull($node);
        $entity = new TouristAttractionEntity();
        $entity->configure($node, 'de');

        $transients = $entity->getTransients();

        self::assertArrayHasKey('containedInPlace', $transients);
        self::assertSame([
            'https://thuecat.org/resources/043064193523-jcyt',
            'https://thuecat.org/resources/573211638937-gmqb',
            'https://thuecat.org/resources/497839263245-edbm',
        ], $transients['containedInPlace']);
    }

    #[Test]
    public function capturesContentResponsibleAsManagedByTransient(): void
    {
        // Attractions carry thuecat:contentResponsible; TouristInformation /
        // ParkingFacility use thuecat:managedBy. Same semantic target (an
        // organisation), so we normalise to a single bucket key for the resolver.
        $node = $this->nodeFromFixture('165868194223-zmqf.json', 'schema:TouristAttraction');
        self::assertNotNull($node);
        $entity = new TouristAttractionEntity();
        $entity->configure($node, 'de');

        $transients = $entity->getTransients();

        self::assertArrayHasKey('managedBy', $transients);
        self::assertSame(
            ['https://thuecat.org/resources/018132452787-ngbe'],
            $transients['managedBy']
        );
    }

    #[Test]
    public function capturesParkingFacilityNearByRefsAsTransient(): void
    {
        $node = $this->nodeFromFixture('215230952334-yyno.json', 'schema:TouristAttraction');
        self::assertNotNull($node);
        $entity = new TouristAttractionEntity();
        $entity->configure($node, 'de');

        $transients = $entity->getTransients();

        self::assertArrayHasKey('parkingFacilityNearBy', $transients);
        self::assertSame([
            'https://thuecat.org/resources/396420044896-drzt',
            'https://thuecat.org/resources/440055527204-ocar',
        ], $transients['parkingFacilityNearBy']);
    }

    #[Test]
    public function encodesOpeningHoursListAsJsonBlob(): void
    {
        // opening-hours-to-filter.json has two OpeningHoursSpecification nodes —
        // one with a list of dayOfWeek entries, one with a single object — so
        // it exercises both input shapes in one round trip.
        $node = $this->nodeFromFixture('opening-hours-to-filter.json', 'schema:TouristAttraction');
        self::assertNotNull($node);
        $entity = new TouristAttractionEntity();
        $entity->configure($node, 'de');

        $decoded = $this->decodeJson($entity->toArray()['opening_hours']);

        self::assertSame([
            [
                'opens' => '09:30:00',
                'closes' => '18:00:00',
                'daysOfWeek' => ['Wednesday'],
                'from' => ['date' => '2021-05-01'],
                'through' => ['date' => '2021-10-31'],
            ],
            [
                'opens' => '13:00:00',
                'closes' => '17:00:00',
                'daysOfWeek' => ['Sunday'],
                'from' => ['date' => '2050-11-01'],
                'through' => ['date' => '2050-04-30'],
            ],
        ], $decoded);
    }

    #[Test]
    public function acceptsSingleOpeningHoursSpecificationObject(): void
    {
        // Alte Synagoge's fixture carries schema:openingHoursSpecification as a
        // single object rather than a list — the parser still has to produce a
        // list of one entry in the JSON column.
        $node = $this->nodeFromFixture('165868194223-zmqf.json', 'schema:TouristAttraction');
        self::assertNotNull($node);
        $entity = new TouristAttractionEntity();
        $entity->configure($node, 'de');

        $decoded = $this->decodeJson($entity->toArray()['opening_hours']);

        self::assertCount(1, $decoded);
        self::assertSame('10:00:00', $decoded[0]['opens']);
        self::assertSame('18:00:00', $decoded[0]['closes']);
    }

    #[Test]
    public function encodesSpecialOpeningHoursListAsJsonBlob(): void
    {
        // special-opening-hours.json has two specialOpeningHoursSpecification
        // nodes — different JSON-LD key, identical shape, so the same transient
        // handles it; only the target column changes.
        $node = $this->nodeFromFixture('special-opening-hours.json', 'schema:TouristAttraction');
        self::assertNotNull($node);
        $entity = new TouristAttractionEntity();
        $entity->configure($node, 'de');

        $decoded = $this->decodeJson($entity->toArray()['special_opening_hours']);

        self::assertSame([
            [
                'opens' => '10:00:00',
                'closes' => '14:00:00',
                'daysOfWeek' => ['Saturday'],
                'from' => ['date' => '2050-12-31'],
                'through' => ['date' => '2050-12-31'],
            ],
            [
                'opens' => '10:00:00',
                'closes' => '14:00:00',
                'daysOfWeek' => ['Saturday'],
                'from' => ['date' => '2021-12-31'],
                'through' => ['date' => '2021-12-31'],
            ],
        ], $decoded);
    }

    #[Test]
    public function specialOpeningHoursIsAbsentWhenNodeLacksSpecification(): void
    {
        // The regular opening-hours fixture has no specialOpeningHours node; the
        // column must not appear rather than serialising an empty list.
        $node = $this->nodeFromFixture('opening-hours-to-filter.json', 'schema:TouristAttraction');
        self::assertNotNull($node);
        $entity = new TouristAttractionEntity();
        $entity->configure($node, 'de');

        self::assertArrayNotHasKey('special_opening_hours', $entity->toArray());
    }

    #[Test]
    public function openingHoursIsAbsentWhenNodeLacksSpecification(): void
    {
        $entity = new TouristAttractionEntity();
        $entity->configure([
            '@id' => 'https://thuecat.org/resources/no-hours',
            '@type' => ['schema:TouristAttraction'],
        ], 'de');

        // '' is filtered out by AbstractEntity::toArray, so the column simply
        // doesn't appear in the row.
        self::assertArrayNotHasKey('opening_hours', $entity->toArray());
    }

    #[Test]
    public function capturesAccessibilitySpecificationRefAsTransient(): void
    {
        // Unusual member of the transient flow: target column is a JSON blob,
        // not a uid. The resolver fetches the referenced resource and shapes
        // the spec into accessibility_specification.
        $node = $this->nodeFromFixture('165868194223-zmqf.json', 'schema:TouristAttraction');
        self::assertNotNull($node);
        $entity = new TouristAttractionEntity();
        $entity->configure($node, 'de');

        $transients = $entity->getTransients();

        self::assertArrayHasKey('accessibilitySpecification', $transients);
        self::assertSame(
            ['https://thuecat.org/resources/e_23bec7f80c864c358da033dd75328f27-rfa'],
            $transients['accessibilitySpecification']
        );
    }

    #[Test]
    public function capturesMediaRefsAsTransient(): void
    {
        // Alte Synagoge carries the same dms_* resource under both schema:image
        // and schema:photo. Same fetch-and-shape-to-JSON pattern as
        // accessibilitySpecification: parser records the @id list, resolver
        // fetches each dms_* resource, shapes it, and writes the media JSON
        // blob onto the column.
        //
        // The duplicate is deliberately preserved: the two JSON-LD slots may
        // encode different roles for the same underlying resource (schema:image
        // as the principal image, schema:photo as a supplementary reference),
        // and de-duping here would strip that signal before the resolver sees
        // it. The resolver can collapse equal @ids if it decides the slot
        // distinction isn't load-bearing.
        $node = $this->nodeFromFixture('165868194223-zmqf.json', 'schema:TouristAttraction');
        self::assertNotNull($node);
        $entity = new TouristAttractionEntity();
        $entity->configure($node, 'de');

        $transients = $entity->getTransients();

        self::assertArrayHasKey('media', $transients);
        self::assertSame([
            'https://thuecat.org/resources/dms_5099196',
            'https://thuecat.org/resources/dms_5099196',
        ], $transients['media']);
    }

    #[Test]
    public function mergesImageAndPhotoRefsIntoSingleMediaBucket(): void
    {
        // Dom fixture has both schema:image (list of three) and schema:photo
        // (single stub) on the same attraction node. One column, one bucket —
        // the resolver decides per-resource which ones are images vs photos
        // vs videos based on the fetched @type, not on the JSON-LD slot name.
        $node = $this->nodeFromFixture('835224016581-dara.json', 'schema:TouristAttraction');
        self::assertNotNull($node);
        $entity = new TouristAttractionEntity();
        $entity->configure($node, 'de');

        $transients = $entity->getTransients();

        self::assertArrayHasKey('media', $transients);
        self::assertSame([
            'https://thuecat.org/resources/dms_5713563',
            'https://thuecat.org/resources/dms_5159186',
            'https://thuecat.org/resources/dms_5159216',
            'https://thuecat.org/resources/dms_5159216',
        ], $transients['media']);
    }

    #[Test]
    public function transientsAreEmptyWhenNodeLacksRelations(): void
    {
        $entity = new TouristAttractionEntity();
        $entity->configure([
            '@id' => 'https://thuecat.org/resources/no-relations',
            '@type' => ['schema:TouristAttraction'],
        ], 'de');

        self::assertSame([], $entity->getTransients());
    }

    #[Test]
    public function encodesOffersListAsJsonBlob(): void
    {
        // Alte Synagoge carries two Offers — GuidedTourOffer with two prices,
        // EntryOffer with four — so one fixture covers offerType extraction,
        // nested priceSpecification list handling, and the full price shape.
        $node = $this->nodeFromFixture('165868194223-zmqf.json', 'schema:TouristAttraction');
        self::assertNotNull($node);
        $entity = new TouristAttractionEntity();
        $entity->configure($node, 'de');

        $decoded = $this->decodeJson($entity->toArray()['offers']);

        self::assertCount(2, $decoded);
        self::assertSame(['GuidedTourOffer'], $decoded[0]['types']);
        self::assertSame('Führungen', $decoded[0]['title']);
        self::assertCount(2, $decoded[0]['prices']);
        self::assertSame([
            'title' => 'Erwachsene',
            'description' => '',
            'price' => 8,
            'currency' => 'EUR',
            'rule' => 'PerPerson',
        ], $decoded[0]['prices'][0]);

        self::assertSame(['EntryOffer'], $decoded[1]['types']);
        self::assertSame('Eintritt', $decoded[1]['title']);
        self::assertCount(4, $decoded[1]['prices']);
    }

    #[Test]
    public function acceptsSingleMakesOfferObject(): void
    {
        $node = [
            '@id' => 'https://thuecat.org/resources/single-offer',
            '@type' => ['schema:TouristAttraction'],
            'schema:makesOffer' => [
                'schema:name' => ['@language' => 'de', '@value' => 'Eintritt'],
                'thuecat:offerType' => ['@type' => 'thuecat:OfferType', '@value' => 'thuecat:EntryOffer'],
                'schema:priceSpecification' => [
                    'schema:name' => ['@language' => 'de', '@value' => 'Erwachsene'],
                    'schema:price' => ['@type' => 'schema:Number', '@value' => '8'],
                    'schema:priceCurrency' => ['@type' => 'thuecat:Currency', '@value' => 'thuecat:EUR'],
                    'thuecat:calculationRule' => ['@type' => 'thuecat:CalculationRule', '@value' => 'thuecat:PerPerson'],
                ],
            ],
        ];

        $entity = new TouristAttractionEntity();
        $entity->configure($node, 'de');

        $decoded = $this->decodeJson($entity->toArray()['offers']);
        self::assertCount(1, $decoded);
        self::assertSame('Eintritt', $decoded[0]['title']);
    }

    #[Test]
    public function offersIsAbsentWhenNodeLacksMakesOffer(): void
    {
        $entity = new TouristAttractionEntity();
        $entity->configure([
            '@id' => 'https://thuecat.org/resources/no-offers',
            '@type' => ['schema:TouristAttraction'],
        ], 'de');

        // Same array_filter contract as opening_hours: '' is dropped, so the
        // column simply doesn't appear.
        self::assertArrayNotHasKey('offers', $entity->toArray());
    }
}
