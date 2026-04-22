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
use WerkraumMedia\ThueCat\Domain\Import\Parser\Entity\TouristAttractionEntity;
use WerkraumMedia\ThueCat\Tests\Unit\Domain\Import\Parser\Fake\ParserContextFake;

class TouristAttractionEntityTest extends TestCase
{
    private const FIXTURE_PATH = __DIR__ . '/../Fixtures/';

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
        $entity->configure($node, new ParserContextFake());
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
        $entity->configure($node, new ParserContextFake());
        $result = $entity->toArray();

        $expectedAddress = '{"remote_id":"genid-39178cabb01c40e091809d730cb07b5a-b0","street":"Benediktsplatz 1","zip":"99084","city":"Erfurt","email":"info@erfurt-tourismus.de","phone":"+49 361 66400","fax":"+49 361 6640290","geo":{"latitude":50.9784118,"longitude":11.0298392}}';
        self::assertSame($expectedAddress, $result['address']);
    }

    #[Test]
    public function extractsFlatEnumAndValueFields(): void
    {
        // Golden values are the sys_language_uid=0 row for 165868194223-zmqf in
        // Tests/Unit/Domain/Import/Parser/Assertions/ImportsTouristAttractionsWithRelations.php.
        $node = $this->nodeFromFixture('165868194223-zmqf.json');
        self::assertNotNull($node);
        $entity = new TouristAttractionEntity();
        $entity->configure($node, new ParserContextFake());

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
        $node = $this->nodeFromFixture('835224016581-dara.json');
        self::assertNotNull($node);
        $entity = new TouristAttractionEntity();
        $entity->configure($node, new ParserContextFake());

        self::assertSame('350:MTR:Streetcar:CityBus', $entity->toArray()['distance_to_public_transport']);
    }

    #[Test]
    public function rowOmitsRelationFieldsForResolverToFill(): void
    {
        // Resolver-owned columns: parser mustn't pre-fill them. The JSON-LD
        // stub only carries @id, and containedInPlace mixes several place types.
        $node = $this->nodeFromFixture('165868194223-zmqf.json');
        self::assertNotNull($node);
        $entity = new TouristAttractionEntity();
        $entity->configure($node, new ParserContextFake());

        $row = $entity->toArray();

        self::assertArrayNotHasKey('town', $row);
        self::assertArrayNotHasKey('managed_by', $row);
        self::assertArrayNotHasKey('parking_facility_near_by', $row);
    }

    #[Test]
    public function capturesContainedInPlaceRefsAsTransient(): void
    {
        $node = $this->nodeFromFixture('165868194223-zmqf.json');
        self::assertNotNull($node);
        $entity = new TouristAttractionEntity();
        $entity->configure($node, new ParserContextFake());

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
        $node = $this->nodeFromFixture('165868194223-zmqf.json');
        self::assertNotNull($node);
        $entity = new TouristAttractionEntity();
        $entity->configure($node, new ParserContextFake());

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
        $node = $this->nodeFromFixture('215230952334-yyno.json');
        self::assertNotNull($node);
        $entity = new TouristAttractionEntity();
        $entity->configure($node, new ParserContextFake());

        $transients = $entity->getTransients();

        self::assertArrayHasKey('parkingFacilityNearBy', $transients);
        self::assertSame([
            'https://thuecat.org/resources/396420044896-drzt',
            'https://thuecat.org/resources/440055527204-ocar',
        ], $transients['parkingFacilityNearBy']);
    }

    #[Test]
    public function transientsAreEmptyWhenNodeLacksRelations(): void
    {
        $entity = new TouristAttractionEntity();
        $entity->configure([
            '@id' => 'https://thuecat.org/resources/no-relations',
            '@type' => ['schema:TouristAttraction'],
        ], new ParserContextFake());

        self::assertSame([], $entity->getTransients());
    }

    private function nodeFromFixture(string $filename): ?array
    {
        $path = self::FIXTURE_PATH . $filename;
        $decoded = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $graph = is_array($decoded) ? $decoded['@graph'] : [];
        foreach ($graph as $node) {
            if (is_array($node) && in_array('schema:TouristAttraction', $node['@type'] ?? [], true)) {
                return $node;
            }
        }
        return null;
    }
}
