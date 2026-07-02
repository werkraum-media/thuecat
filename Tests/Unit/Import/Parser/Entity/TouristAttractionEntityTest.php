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
use WerkraumMedia\ThueCat\Import\Parser\Entity\TouristAttractionEntity;
use WerkraumMedia\ThueCat\Import\Parser\ParserContext;

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
        $entity->parse($node, 'de', new ParserContext(0));
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
        $entity->parse($node, 'de', new ParserContext(0));
        $result = $entity->toArray();

        $expectedAddress = '{"remote_id":"genid-39178cabb01c40e091809d730cb07b5a-b0","street":"Benediktsplatz 1","zip":"99084","city":"Erfurt","email":"info@erfurt-tourismus.de","phone":"+49 361 66400","fax":"+49 361 6640290","geo":{"latitude":50.9784118,"longitude":11.0298392}}';
        self::assertSame($expectedAddress, $result['address']);
    }

    #[Test]
    public function extractsFlatEnumAndValueFields(): void
    {
        // Golden values are the sys_language_uid=0 row for 165868194223-zmqf in
        // Tests/Unit/Import/Parser/Assertions/ImportsTouristAttractionsWithRelations.php.
        $node = $this->nodeFromFixture('165868194223-zmqf.json', 'schema:TouristAttraction');
        self::assertNotNull($node);
        $entity = new TouristAttractionEntity();
        $entity->parse($node, 'de', new ParserContext(0));

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
        $entity->parse($node, 'de', new ParserContext(0));

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
        $entity->parse($node, 'de', new ParserContext(0));

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
        $entity->parse($node, 'de', new ParserContext(0));

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
        $entity->parse($node, 'de', new ParserContext(0));

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
        $entity->parse($node, 'de', new ParserContext(0));

        $transients = $entity->getTransients();

        self::assertArrayHasKey('parkingFacilityNearBy', $transients);
        self::assertSame([
            'https://thuecat.org/resources/396420044896-drzt',
            'https://thuecat.org/resources/440055527204-ocar',
        ], $transients['parkingFacilityNearBy']);
    }

    #[Test]
    public function buildsOpeningHourSpecificationChildrenImportedAsIs(): void
    {
        // Two specs (list-shaped + object-shaped dayOfWeek); both kept — import
        // is lossless, no past-date filtering (that moved to display).
        $node = $this->nodeFromFixture('opening-hours-to-filter.json', 'schema:TouristAttraction');
        self::assertNotNull($node);
        $entity = new TouristAttractionEntity();
        $entity->parse($node, 'de', new ParserContext(0));

        $rows = array_map(static fn ($child) => $child->toArray(), $entity->getChildren());

        self::assertCount(2, $rows);
        foreach ($rows as $row) {
            self::assertSame('regular', $row['specification_type']);
        }
        $byDay = array_column($rows, null, 'day_of_week');
        self::assertSame('09:30:00', $byDay['Wednesday']['opens']);
        self::assertSame('2021-10-31', $byDay['Wednesday']['valid_through']);
        self::assertSame('13:00:00', $byDay['Sunday']['opens']);
        self::assertSame('2050-04-30', $byDay['Sunday']['valid_through']);
    }

    #[Test]
    public function buildsChildrenFromSingleObjectMultiDaySpecification(): void
    {
        // schema:openingHoursSpecification is a single object (not a list) but
        // carries 6 weekdays → one child row per day.
        $node = $this->nodeFromFixture('165868194223-zmqf.json', 'schema:TouristAttraction');
        self::assertNotNull($node);
        $entity = new TouristAttractionEntity();
        $entity->parse($node, 'de', new ParserContext(0));

        $rows = array_map(static fn ($child) => $child->toArray(), $entity->getChildren());

        self::assertCount(6, $rows);
        foreach ($rows as $row) {
            self::assertSame('10:00:00', $row['opens']);
            self::assertSame('18:00:00', $row['closes']);
            self::assertSame('regular', $row['specification_type']);
        }
        self::assertContains('Saturday', array_column($rows, 'day_of_week'));
    }

    #[Test]
    public function buildsSpecialOpeningHourSpecificationChildren(): void
    {
        $node = $this->nodeFromFixture('special-opening-hours.json', 'schema:TouristAttraction');
        self::assertNotNull($node);
        $entity = new TouristAttractionEntity();
        $entity->parse($node, 'de', new ParserContext(0));

        $special = array_values(array_filter(
            array_map(static fn ($child) => $child->toArray(), $entity->getChildren()),
            static fn (array $row) => $row['specification_type'] === 'special'
        ));

        self::assertNotEmpty($special);
        self::assertSame('10:00:00', $special[0]['opens']);
        self::assertSame('14:00:00', $special[0]['closes']);
        self::assertSame('Saturday', $special[0]['day_of_week']);
    }

    #[Test]
    public function hasNoOpeningHourChildrenWhenNodeLacksSpecification(): void
    {
        $entity = new TouristAttractionEntity();
        $entity->parse([
            '@id' => 'https://thuecat.org/resources/no-hours',
            '@type' => ['schema:TouristAttraction'],
        ], 'de', new ParserContext(0));

        self::assertSame([], $entity->getChildren());
        // Blob columns are no longer written either.
        self::assertArrayNotHasKey('opening_hours', $entity->toArray());
        self::assertArrayNotHasKey('special_opening_hours', $entity->toArray());
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
        $entity->parse($node, 'de', new ParserContext(0));

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
        // accessibilitySpecification, but the entries are `{kind, id}` tuples:
        // the resolver needs to know which JSON-LD slot each ref came from to
        // set mainImage (true for photo) and type (image vs video) on the
        // shaped output. Photo entries come first to preserve legacy ordering
        // where the schema:photo ref is emitted as the principal image.
        $node = $this->nodeFromFixture('165868194223-zmqf.json', 'schema:TouristAttraction');
        self::assertNotNull($node);
        $entity = new TouristAttractionEntity();
        $entity->parse($node, 'de', new ParserContext(0));

        $transients = $entity->getTransients();

        self::assertArrayHasKey('media', $transients);
        self::assertSame([
            ['kind' => 'photo', 'id' => 'https://thuecat.org/resources/dms_5099196'],
            ['kind' => 'image', 'id' => 'https://thuecat.org/resources/dms_5099196'],
        ], $transients['media']);
    }

    #[Test]
    public function mergesImageAndPhotoRefsIntoSingleMediaBucket(): void
    {
        // Dom fixture has both schema:image (list of three) and schema:photo
        // (single stub) on the same attraction node. One column, one bucket,
        // photo-first ordering — the resolver uses the kind tag to produce the
        // legacy `mainImage:true` entry (photo) and the subsequent
        // `mainImage:false` entries (image). Duplicates between slots stay.
        $node = $this->nodeFromFixture('835224016581-dara.json', 'schema:TouristAttraction');
        self::assertNotNull($node);
        $entity = new TouristAttractionEntity();
        $entity->parse($node, 'de', new ParserContext(0));

        $transients = $entity->getTransients();

        self::assertArrayHasKey('media', $transients);
        self::assertSame([
            ['kind' => 'photo', 'id' => 'https://thuecat.org/resources/dms_5159216'],
            ['kind' => 'image', 'id' => 'https://thuecat.org/resources/dms_5713563'],
            ['kind' => 'image', 'id' => 'https://thuecat.org/resources/dms_5159186'],
            ['kind' => 'image', 'id' => 'https://thuecat.org/resources/dms_5159216'],
        ], $transients['media']);
    }

    #[Test]
    public function transientsAreEmptyWhenNodeLacksRelations(): void
    {
        $entity = new TouristAttractionEntity();
        $entity->parse([
            '@id' => 'https://thuecat.org/resources/no-relations',
            '@type' => ['schema:TouristAttraction'],
        ], 'de', new ParserContext(0));

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
        $entity->parse($node, 'de', new ParserContext(0));

        $decoded = $this->decodeJson($entity->toArray()['offers']);

        self::assertCount(2, $decoded);
        // @phpstan-ignore offsetAccess.nonOffsetAccessible (this array is artificially constructed, so we trust it here)
        self::assertSame(['GuidedTourOffer'], $decoded[0]['types']);
        // @phpstan-ignore offsetAccess.nonOffsetAccessible (this array is artificially constructed, so we trust it here)
        self::assertSame('Führungen', $decoded[0]['title']);
        // @phpstan-ignore offsetAccess.nonOffsetAccessible (this array is artificially constructed, so we trust it here)
        self::assertCount(2, $decoded[0]['prices']);
        self::assertSame(
            [
                'title' => 'Erwachsene',
                'description' => '',
                'price' => 8,
                'currency' => 'EUR',
                'rule' => 'PerPerson',
            ],
            // @phpstan-ignore offsetAccess.nonOffsetAccessible, offsetAccess.nonOffsetAccessible (this array is artificially constructed, so we trust it here)
            $decoded[0]['prices'][0]
        );

        // @phpstan-ignore offsetAccess.nonOffsetAccessible (this array is artificially constructed, so we trust it here)
        self::assertSame(['EntryOffer'], $decoded[1]['types']);
        // @phpstan-ignore offsetAccess.nonOffsetAccessible (this array is artificially constructed, so we trust it here)
        self::assertSame('Eintritt', $decoded[1]['title']);
        // @phpstan-ignore offsetAccess.nonOffsetAccessible (this array is artificially constructed, so we trust it here)
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
        $entity->parse($node, 'de', new ParserContext(0));

        $decoded = $this->decodeJson($entity->toArray()['offers']);
        self::assertCount(1, $decoded);
        // @phpstan-ignore offsetAccess.nonOffsetAccessible (this array is artificially constructed, so we trust it here)
        self::assertSame('Eintritt', $decoded[0]['title']);
    }

    #[Test]
    public function offersIsAbsentWhenNodeLacksMakesOffer(): void
    {
        $entity = new TouristAttractionEntity();
        $entity->parse([
            '@id' => 'https://thuecat.org/resources/no-offers',
            '@type' => ['schema:TouristAttraction'],
        ], 'de', new ParserContext(0));

        // Same array_filter contract as opening_hours: '' is dropped, so the
        // column simply doesn't appear.
        self::assertArrayNotHasKey('offers', $entity->toArray());
    }
}
