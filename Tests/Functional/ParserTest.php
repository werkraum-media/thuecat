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

namespace WerkraumMedia\ThueCat\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use WerkraumMedia\ThueCat\Domain\Import\Parser\Parser;

final class ParserTest extends AbstractImportTestCase
{
    private const FIXTURE_PATH = __DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/';

    #[Test]
    public function parsesOrganisationNode(): void
    {
        $graph = $this->graphFromFixture('018132452787-ngbe.json');

        $subject = $this->get(Parser::class);
        $subject->parse($graph);
        $result = $subject->getDataHandlerPayload()->getPayload();

        self::assertArrayHasKey('tx_thuecat_organisation', $result);
        self::assertArrayHasKey(
            'https://thuecat.org/resources/018132452787-ngbe',
            $result['tx_thuecat_organisation']
        );

        $row = $result['tx_thuecat_organisation']['https://thuecat.org/resources/018132452787-ngbe'];

        self::assertSame('https://thuecat.org/resources/018132452787-ngbe', $row['remote_id']);
        self::assertSame('Erfurt Tourismus und Marketing GmbH', $row['title']);
    }

    #[Test]
    public function organisationPayloadContainsCompleteRowAndNoTransients(): void
    {
        // Single top-level schema:Organization node, no outgoing relations —
        // exercises the full parse → DataHandlerPayload shape without any
        // resolver-owned fields or transient buckets getting in the way.
        $graph = $this->graphFromFixture('018132452787-ngbe.json');

        $subject = $this->get(Parser::class);
        $subject->parse($graph);
        $payload = $subject->getDataHandlerPayload();

        $data = $payload->getPayload();

        self::assertSame(['tx_thuecat_organisation'], array_keys($data));
        self::assertSame(
            ['https://thuecat.org/resources/018132452787-ngbe'],
            array_keys($data['tx_thuecat_organisation'])
        );

        $row = $data['tx_thuecat_organisation']['https://thuecat.org/resources/018132452787-ngbe'];
        self::assertSame(
            ['remote_id', 'title', 'description'],
            array_keys($row)
        );
        self::assertSame('https://thuecat.org/resources/018132452787-ngbe', $row['remote_id']);
        self::assertSame('Erfurt Tourismus und Marketing GmbH', $row['title']);
        self::assertStringStartsWith('Die Erfurt Tourismus', (string)$row['description']);
        self::assertStringEndsWith('4 Auszubildende', (string)$row['description']);

        // Organisation has no outgoing relations of its own (reverse-inline
        // only), so the transients bucket must stay untouched.
        self::assertSame([], $payload->getTransients());
    }

    #[Test]
    public function townPayloadContainsCompleteRowAndManagedByTransient(): void
    {
        // Single top-level schema:City node that carries a thuecat:managedBy
        // reference — exercises the transients bucket alongside the data row,
        // which Organisation's fixture does not.
        $graph = $this->graphFromFixture('043064193523-jcyt.json');

        $subject = $this->get(Parser::class);
        $subject->parse($graph);
        $payload = $subject->getDataHandlerPayload();

        $data = $payload->getPayload();

        self::assertSame(['tx_thuecat_town'], array_keys($data));
        self::assertSame(
            ['https://thuecat.org/resources/043064193523-jcyt'],
            array_keys($data['tx_thuecat_town'])
        );

        $row = $data['tx_thuecat_town']['https://thuecat.org/resources/043064193523-jcyt'];
        self::assertSame(
            ['remote_id', 'title', 'description'],
            array_keys($row)
        );
        self::assertSame('https://thuecat.org/resources/043064193523-jcyt', $row['remote_id']);
        self::assertSame('Erfurt', $row['title']);
        self::assertStringStartsWith('Krämerbrücke, Dom, Alte Synagoge', (string)$row['description']);

        // managed_by is a real TCA column but stays out of the row — the
        // resolver fills it after looking up the referenced @id.
        self::assertArrayNotHasKey('managed_by', $row);

        self::assertSame(
            [
                'tx_thuecat_town' => [
                    'https://thuecat.org/resources/043064193523-jcyt' => [
                        'managedBy' => ['https://thuecat.org/resources/018132452787-ngbe'],
                    ],
                ],
            ],
            $payload->getTransients()
        );
    }

    #[Test]
    public function touristAttractionPayloadContainsCompleteRowAndTransients(): void
    {
        // Alte Synagoge — the richest fixture in the suite. Exercises JSON blob
        // columns (opening_hours, offers, address), the full enum/localised
        // scalar set, and every transient bucket the attraction carries:
        // containedInPlace, managedBy (normalised from contentResponsible),
        // parkingFacilityNearBy, accessibilitySpecification, media.
        $graph = $this->graphFromFixture('165868194223-zmqf.json');

        $subject = $this->get(Parser::class);
        $subject->parse($graph);
        $payload = $subject->getDataHandlerPayload();

        $data = $payload->getPayload();

        self::assertSame(['tx_thuecat_tourist_attraction'], array_keys($data));
        self::assertSame(
            ['https://thuecat.org/resources/165868194223-zmqf'],
            array_keys($data['tx_thuecat_tourist_attraction'])
        );

        $row = $data['tx_thuecat_tourist_attraction']['https://thuecat.org/resources/165868194223-zmqf'];

        // Full shape golden: catches regressions like the priority leak that
        // the Organisation/Town tests found. Absent keys (special_opening_hours
        // is absent because array_filter drops '' — fixture has no special
        // openings) stay absent.
        self::assertSame(
            [
                'remote_id',
                'title',
                'description',
                'slogan',
                'start_of_construction',
                'sanitation',
                'other_service',
                'museum_service',
                'architectural_style',
                'traffic_infrastructure',
                'payment_accepted',
                'digital_offer',
                'photography',
                'pets_allowed',
                'is_accessible_for_free',
                'public_access',
                'available_languages',
                'distance_to_public_transport',
                'opening_hours',
                'offers',
                'address',
                'url',
            ],
            array_keys($row)
        );

        self::assertSame('https://thuecat.org/resources/165868194223-zmqf', $row['remote_id']);
        self::assertSame('Alte Synagoge', $row['title']);
        self::assertSame('Beispiel Beschreibung', $row['description']);
        self::assertSame('http://www.alte-synagoge.erfurt.de', $row['url']);
        self::assertSame('Highlight', $row['slogan']);
        self::assertSame('11. Jh.', $row['start_of_construction']);
        self::assertSame('false', $row['is_accessible_for_free']);
        self::assertSame('true', $row['public_access']);
        self::assertSame('German,English,French', $row['available_languages']);
        self::assertSame('200:MTR:CityBus', $row['distance_to_public_transport']);

        // Resolver-owned columns stay off the row entirely. Same contract as
        // the Organisation/Town tests, plus the attraction-specific ones.
        self::assertArrayNotHasKey('town', $row);
        self::assertArrayNotHasKey('managed_by', $row);
        self::assertArrayNotHasKey('parking_facility_near_by', $row);
        self::assertArrayNotHasKey('accessibility_specification', $row);
        self::assertArrayNotHasKey('media', $row);

        // JSON blobs: we only spot-check shape here; the unit tests assert
        // the full decoded structure for offers / opening_hours / address.
        /** @var list<array<string, mixed>> $openingHours */
        $openingHours = json_decode((string)$row['opening_hours'], true, 512, JSON_THROW_ON_ERROR);
        self::assertCount(1, $openingHours);
        self::assertSame('10:00:00', $openingHours[0]['opens']);

        /** @var list<array<string, mixed>> $offers */
        $offers = json_decode((string)$row['offers'], true, 512, JSON_THROW_ON_ERROR);
        self::assertCount(2, $offers);
        self::assertSame(['GuidedTourOffer'], $offers[0]['types']);
        self::assertSame(['EntryOffer'], $offers[1]['types']);

        /** @var array<string, mixed> $address */
        $address = json_decode((string)$row['address'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Waagegasse 8', $address['street']);
        self::assertSame('99084', $address['zip']);
        self::assertSame('Erfurt', $address['city']);

        // Transients: every bucket the attraction records. media preserves the
        // duplicate (schema:image + schema:photo pointing at the same dms_*
        // resource) — deliberate, see TouristAttractionEntity::configure().
        self::assertSame(
            [
                'tx_thuecat_tourist_attraction' => [
                    'https://thuecat.org/resources/165868194223-zmqf' => [
                        'containedInPlace' => [
                            'https://thuecat.org/resources/043064193523-jcyt',
                            'https://thuecat.org/resources/573211638937-gmqb',
                            'https://thuecat.org/resources/497839263245-edbm',
                        ],
                        'managedBy' => [
                            'https://thuecat.org/resources/018132452787-ngbe',
                        ],
                        'accessibilitySpecification' => [
                            'https://thuecat.org/resources/e_23bec7f80c864c358da033dd75328f27-rfa',
                        ],
                        'media' => [
                            'https://thuecat.org/resources/dms_5099196',
                            'https://thuecat.org/resources/dms_5099196',
                        ],
                    ],
                ],
            ],
            $payload->getTransients()
        );
    }

    #[Test]
    public function parsesTouristInformationNode(): void
    {
        $graph = $this->graphFromFixture('333039283321-xxwg.json');

        $subject = $this->get(Parser::class);
        $subject->parse($graph);
        $result = $subject->getDataHandlerPayload()->getPayload();

        self::assertArrayHasKey('tx_thuecat_tourist_information', $result);
        self::assertArrayHasKey(
            'https://thuecat.org/resources/333039283321-xxwg',
            $result['tx_thuecat_tourist_information']
        );

        $row = $result['tx_thuecat_tourist_information']['https://thuecat.org/resources/333039283321-xxwg'];

        self::assertSame('https://thuecat.org/resources/333039283321-xxwg', $row['remote_id']);
        self::assertSame('Erfurt Tourist Information', $row['title']);
    }

    #[Test]
    public function noRefPlaceholdersLeakIntoPayloadOrTransients(): void
    {
        // Parser::parseNode() returns a REF:<id> string internally, but the
        // top-level parse() loop discards it. Nothing in the payload or transients
        // should ever contain a REF: placeholder — relations are recorded as plain
        // @id strings in the transients bucket for the resolver to handle.
        $graph = $this->graphFromFixture('165868194223-zmqf.json');

        $subject = $this->get(Parser::class);
        $subject->parse($graph);
        $payload = $subject->getDataHandlerPayload();

        $needle = 'REF:';

        foreach ($payload->getPayload() as $table => $rows) {
            foreach ($rows as $remoteId => $row) {
                foreach ($row as $column => $value) {
                    self::assertStringNotContainsString(
                        $needle,
                        (string)$value,
                        "REF: placeholder leaked into {$table}[{$remoteId}][{$column}]"
                    );
                }
            }
        }

        foreach ($payload->getTransients() as $table => $rows) {
            foreach ($rows as $remoteId => $buckets) {
                foreach ($buckets as $key => $ids) {
                    foreach ($ids as $id) {
                        self::assertStringNotContainsString(
                            $needle,
                            $id,
                            "REF: placeholder leaked into transients {$table}[{$remoteId}][{$key}]"
                        );
                    }
                }
            }
        }
    }

    #[Test]
    public function skipsBlankNodes(): void
    {
        $graph = $this->graphFromFixture('018132452787-ngbe.json');

        $subject = $this->get(Parser::class);
        $subject->parse($graph);
        $result = $subject->getDataHandlerPayload()->getPayload();

        foreach (array_keys($result) as $table) {
            foreach (array_keys($result[$table]) as $remoteId) {
                self::assertStringNotContainsString('genid-', (string)$remoteId);
            }
        }
    }

    private function graphFromFixture(string $filename): array
    {
        $path = self::FIXTURE_PATH . $filename;
        $decoded = json_decode((string)file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $graph = is_array($decoded) ? $decoded['@graph'] : [];
        return is_array($graph) ? $graph : [];
    }
}
