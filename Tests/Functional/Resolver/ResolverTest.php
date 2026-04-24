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

namespace WerkraumMedia\ThueCat\Tests\Functional\Resolver;

use PHPUnit\Framework\Attributes\Test;
use ReflectionProperty;
use WerkraumMedia\ThueCat\Domain\Import\InvalidTransientReferenceException;
use WerkraumMedia\ThueCat\Domain\Import\Parser\DataHandlerPayload;
use WerkraumMedia\ThueCat\Domain\Import\Parser\Parser;
use WerkraumMedia\ThueCat\Domain\Import\Resolver;
use WerkraumMedia\ThueCat\Domain\Import\ResolverContext;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractImportTestCase;
use WerkraumMedia\ThueCat\Tests\Functional\GuzzleClientFaker;

final class ResolverTest extends AbstractImportTestCase
{
    private const FIXTURE_PATH = __DIR__ . '/../Fixtures/Import/Guzzle/thuecat.org/resources/';

    private const ORG_REMOTE_ID = 'https://thuecat.org/resources/018132452787-ngbe';

    #[Test]
    public function freshOrganisationGetsNewPlaceholderKey(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/BasicPages.php');

        $payload = $this->parseFixture('018132452787-ngbe.json');

        $this->get(Resolver::class)->resolve($payload, new ResolverContext(storagePid: 10));

        $data = $payload->getPayload();
        self::assertSame(['tx_thuecat_organisation'], array_keys($data));

        $keys = array_keys($data['tx_thuecat_organisation']);
        self::assertCount(1, $keys);
        self::assertStringStartsWith('NEW', (string)$keys[0]);

        $row = $data['tx_thuecat_organisation'][$keys[0]];
        self::assertSame(self::ORG_REMOTE_ID, $row['remote_id']);
        self::assertSame('Erfurt Tourismus und Marketing GmbH', $row['title']);
        self::assertSame(10, $row['pid']);

        self::assertSame([], $payload->getTransients());
    }

    #[Test]
    public function existingOrganisationIsKeyedByUidAndDataOverwritten(): void
    {
        // Preloaded row has uid=1, remote_id matching the fixture, title 'Old title'.
        // We expect the resolver to key the row by '1' and leave the fresh data
        // (new title from the fixture) intact — no diffing, legacy behaviour.
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/UpdatesExistingOrganization.php');

        $payload = $this->parseFixture('018132452787-ngbe.json');

        $this->get(Resolver::class)->resolve($payload, new ResolverContext(storagePid: 10));

        $data = $payload->getPayload();
        // PHP casts numeric-string array keys back to int automatically, so the
        // outer key the resolver sets as '1' ends up as int 1 in the array.
        self::assertSame([1], array_keys($data['tx_thuecat_organisation']));

        $row = $data['tx_thuecat_organisation'][1];
        self::assertSame(self::ORG_REMOTE_ID, $row['remote_id']);
        self::assertSame('Erfurt Tourismus und Marketing GmbH', $row['title']);
        self::assertSame(10, $row['pid']);
    }

    #[Test]
    public function townResolvesManagedByToExistingOrganisationUid(): void
    {
        // Organisation is preloaded with uid=7; the town fixture carries a
        // managedBy transient pointing at that same remote_id. Resolver must
        // write `managed_by = 7` on the town row and drop the transient.
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ExistingOrganisationForTown.php');

        $payload = $this->parseFixture('043064193523-jcyt.json');

        $this->get(Resolver::class)->resolve($payload, new ResolverContext(storagePid: 10));

        $data = $payload->getPayload();
        self::assertSame(['tx_thuecat_town'], array_keys($data));

        $townKeys = array_keys($data['tx_thuecat_town']);
        self::assertCount(1, $townKeys);
        self::assertStringStartsWith('NEW', (string)$townKeys[0]);

        $townRow = $data['tx_thuecat_town'][$townKeys[0]];
        self::assertSame('7', $townRow['managed_by']);
        self::assertSame(10, $townRow['pid']);

        self::assertSame([], $payload->getTransients());
    }

    #[Test]
    public function townFetchesMissingOrganisationAndLinksViaNewPlaceholder(): void
    {
        // No preloaded organisation. Resolver must fetch the managedBy @id
        // from ThueCat, parse the returned graph, and merge the organisation
        // row into the payload. The next drain pass wires the town's
        // managed_by to the organisation's NEW placeholder key.
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/BasicPages.php');
        GuzzleClientFaker::appendResponseFromFile(self::FIXTURE_PATH . '018132452787-ngbe.json');

        $payload = $this->parseFixture('043064193523-jcyt.json');

        $this->get(Resolver::class)->resolve($payload, new ResolverContext(storagePid: 10));

        $data = $payload->getPayload();
        self::assertSame(
            ['tx_thuecat_town', 'tx_thuecat_organisation'],
            array_keys($data)
        );

        $townKeys = array_keys($data['tx_thuecat_town']);
        self::assertCount(1, $townKeys);
        self::assertStringStartsWith('NEW', (string)$townKeys[0]);

        $orgKeys = array_keys($data['tx_thuecat_organisation']);
        self::assertCount(1, $orgKeys);
        self::assertStringStartsWith('NEW', (string)$orgKeys[0]);

        $townRow = $data['tx_thuecat_town'][$townKeys[0]];
        self::assertSame((string)$orgKeys[0], $townRow['managed_by']);
        self::assertSame(10, $townRow['pid']);

        $orgRow = $data['tx_thuecat_organisation'][$orgKeys[0]];
        self::assertSame(10, $orgRow['pid']);

        self::assertSame([], $payload->getTransients());
    }

    #[Test]
    public function parkingFacilityResolvesContainedInPlaceAndManagedByToExistingUids(): void
    {
        // ParkingFacility fixture carries two ref→uid transients we now handle:
        // containedInPlace (→ town wwne, preloaded uid=5) and managedBy
        // (→ organisation rfze, preloaded uid=8). Fixture is trimmed to drop
        // the media bucket which is still out of scope for the resolver.
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ExistingTownForParkingFacility.php');

        $payload = $this->parseFixture('396420044896-drzt-without-media.json');

        $this->get(Resolver::class)->resolve($payload, new ResolverContext(storagePid: 10));

        $data = $payload->getPayload();
        self::assertSame(['tx_thuecat_parking_facility'], array_keys($data));

        $keys = array_keys($data['tx_thuecat_parking_facility']);
        self::assertCount(1, $keys);
        self::assertStringStartsWith('NEW', (string)$keys[0]);

        $row = $data['tx_thuecat_parking_facility'][$keys[0]];
        self::assertSame('5', $row['town']);
        self::assertSame('8', $row['managed_by']);
        self::assertSame(10, $row['pid']);

        self::assertSame([], $payload->getTransients());
    }

    #[Test]
    public function touristAttractionResolvesMultipleParkingFacilityNearByToExistingUids(): void
    {
        // Bridge fixture carries two parkingFacilityNearBy @ids; both parking
        // facilities are preloaded (uid=9 for drzt, uid=11 for ocar). Both
        // towns from containedInPlace are preloaded too so the whole payload
        // resolves without any API fetch. This covers the multi-ref path of
        // drainTransients (`foreach ($references as …)`).
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ExistingParkingFacilityForAttraction.php');

        $payload = $this->parseFixture('215230952334-yyno-without-media.json');

        $this->get(Resolver::class)->resolve($payload, new ResolverContext(storagePid: 10));

        $data = $payload->getPayload();
        self::assertSame(['tx_thuecat_tourist_attraction'], array_keys($data));

        $keys = array_keys($data['tx_thuecat_tourist_attraction']);
        self::assertCount(1, $keys);
        $row = $data['tx_thuecat_tourist_attraction'][$keys[0]];

        // town is a single-select; multiple containedInPlace refs collapse
        // via the csv append+dedupe in DataHandlerPayload::setRelationField.
        self::assertSame('5,6', $row['town']);
        self::assertSame('9,11', $row['parking_facility_near_by']);
        self::assertSame('7', $row['managed_by']);
        self::assertSame(10, $row['pid']);

        self::assertSame([], $payload->getTransients());
    }

    #[Test]
    public function parkingFacilityFetchesMissingTownViaContainedInPlaceAndLinksViaNewPlaceholder(): void
    {
        // ParkingFacility ocar references town jcyt via containedInPlace; no
        // town preloaded. Queue the town fixture: parser re-runs, merges the
        // town row with its own managedBy transient pointing at the preloaded
        // organisation ngbe (uid=7). After two drain passes the parking
        // facility's `town` field is wired to the town's NEW placeholder.
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ExistingOrganisationForTown.php');
        GuzzleClientFaker::appendResponseFromFile(self::FIXTURE_PATH . '043064193523-jcyt.json');

        $payload = $this->parseFixture('440055527204-ocar-without-media.json');

        $this->get(Resolver::class)->resolve($payload, new ResolverContext(storagePid: 10));

        $data = $payload->getPayload();
        self::assertSame(
            ['tx_thuecat_parking_facility', 'tx_thuecat_town'],
            array_keys($data)
        );

        $parkingKeys = array_keys($data['tx_thuecat_parking_facility']);
        self::assertCount(1, $parkingKeys);
        self::assertStringStartsWith('NEW', (string)$parkingKeys[0]);

        $townKeys = array_keys($data['tx_thuecat_town']);
        self::assertCount(1, $townKeys);
        self::assertStringStartsWith('NEW', (string)$townKeys[0]);

        $parkingRow = $data['tx_thuecat_parking_facility'][$parkingKeys[0]];
        self::assertSame((string)$townKeys[0], $parkingRow['town']);
        // ocar does not carry thuecat:managedBy, so the row stays without a
        // managed_by value — nothing to assert on the parking row here.

        $townRow = $data['tx_thuecat_town'][$townKeys[0]];
        self::assertSame('7', $townRow['managed_by']);

        self::assertSame([], $payload->getTransients());
    }

    #[Test]
    public function touristAttractionFetchesMissingParkingFacilityAndLinksViaNewPlaceholder(): void
    {
        // Bridge fixture with two parkingFacilityNearBy refs; neither parking
        // facility is preloaded. Queue both trimmed parking-facility fixtures.
        // Towns (jcyt, oxfq) and organisation (ngbe) are preloaded to keep
        // the chain fanout contained — the parking facilities themselves
        // carry containedInPlace + contentResponsible transients that must
        // resolve via DB on the follow-up drain pass.
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ExistingTownsAndOrganisationForAttraction.php');

        GuzzleClientFaker::appendResponseFromFile(self::FIXTURE_PATH . '396420044896-drzt-without-media.json');
        GuzzleClientFaker::appendResponseFromFile(self::FIXTURE_PATH . '440055527204-ocar-without-media.json');

        $payload = $this->parseFixture('215230952334-yyno-without-media.json');

        $this->get(Resolver::class)->resolve($payload, new ResolverContext(storagePid: 10));

        $data = $payload->getPayload();
        self::assertSame(
            ['tx_thuecat_tourist_attraction', 'tx_thuecat_parking_facility'],
            array_keys($data)
        );

        $attractionKeys = array_keys($data['tx_thuecat_tourist_attraction']);
        self::assertCount(1, $attractionKeys);

        $parkingKeys = array_keys($data['tx_thuecat_parking_facility']);
        self::assertCount(2, $parkingKeys);
        foreach ($parkingKeys as $parkingKey) {
            self::assertStringStartsWith('NEW', (string)$parkingKey);
        }

        $row = $data['tx_thuecat_tourist_attraction'][$attractionKeys[0]];
        self::assertSame(
            implode(',', array_map('strval', $parkingKeys)),
            $row['parking_facility_near_by']
        );
        self::assertSame('5,6', $row['town']);
        self::assertSame('7', $row['managed_by']);

        self::assertSame([], $payload->getTransients());
    }

    #[Test]
    public function parkingFacilityMediaBucketShapesIntoJsonBlob(): void
    {
        // drzt's schema:photo and schema:image both point at dms_6486108, so
        // the media bucket has two {kind,id} entries referencing the same
        // resource. The resolver fetches the media node once (FetchData
        // caches by url+apiKey), shapes each entry into the legacy Media
        // frontend JSON, and writes the encoded list onto the `media`
        // column. Photo-first ordering makes the first entry mainImage:true.
        // Preload town + orgs so the ref→uid buckets resolve via DB.
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ExistingTownForParkingFacility.php');
        GuzzleClientFaker::appendResponseFromFile(self::FIXTURE_PATH . 'dms_6486108.json');

        $payload = $this->parseFixture('396420044896-drzt.json');

        $this->get(Resolver::class)->resolve($payload, new ResolverContext(storagePid: 10));

        $data = $payload->getPayload();
        self::assertSame(['tx_thuecat_parking_facility'], array_keys($data));

        $keys = array_keys($data['tx_thuecat_parking_facility']);
        self::assertCount(1, $keys);

        $row = $data['tx_thuecat_parking_facility'][$keys[0]];
        self::assertArrayHasKey('media', $row);

        $media = json_decode((string)$row['media'], true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($media);
        self::assertCount(2, $media);

        self::assertSame([
            'mainImage' => true,
            'type' => 'image',
            'title' => 'Erfurt-Parkhaus-Domplatz.jpg',
            'description' => '',
            'url' => 'https://cms.thuecat.org/o/adaptive-media/image/6486108/Preview-1280x0/image',
            'author' => 'Florian Trykowski',
            'copyrightYear' => 2021,
            'license' => [
                'type' => 'https://creativecommons.org/licenses/by/4.0/',
                'author' => '',
            ],
        ], $media[0]);

        self::assertSame([
            'mainImage' => false,
            'type' => 'image',
            'title' => 'Erfurt-Parkhaus-Domplatz.jpg',
            'description' => '',
            'url' => 'https://cms.thuecat.org/o/adaptive-media/image/6486108/Preview-1280x0/image',
            'author' => 'Florian Trykowski',
            'copyrightYear' => 2021,
            'license' => [
                'type' => 'https://creativecommons.org/licenses/by/4.0/',
                'author' => '',
            ],
        ], $media[1]);

        self::assertSame([], $payload->getTransients());
    }

    #[Test]
    public function touristAttractionMediaBucketResolvesAuthorReference(): void
    {
        // attraction-with-media references four image-with-* resources, one
        // of which (image-with-foreign-author) points its schema:author at an
        // author-with-names Person node. The resolver must fetch the Person
        // node and shape "GivenName FamilyName" into the output. This also
        // covers the three other author shapes in one sweep: literal string,
        // license-author-only, and string + license-author.
        //
        // managedBy via contentResponsible → ngbe (preloaded as uid=7).
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ExistingOrganisationForTown.php');

        GuzzleClientFaker::appendResponseFromFile(self::FIXTURE_PATH . 'image-with-foreign-author.json');
        GuzzleClientFaker::appendResponseFromFile(self::FIXTURE_PATH . 'author-with-names.json');
        GuzzleClientFaker::appendResponseFromFile(self::FIXTURE_PATH . 'image-with-author-string.json');
        GuzzleClientFaker::appendResponseFromFile(self::FIXTURE_PATH . 'image-with-license-author.json');
        GuzzleClientFaker::appendResponseFromFile(self::FIXTURE_PATH . 'image-with-author-and-license-author.json');

        $payload = $this->parseFixture('attraction-with-media.json');

        $this->get(Resolver::class)->resolve($payload, new ResolverContext(storagePid: 10));

        $data = $payload->getPayload();
        self::assertSame(['tx_thuecat_tourist_attraction'], array_keys($data));

        $keys = array_keys($data['tx_thuecat_tourist_attraction']);
        self::assertCount(1, $keys);

        $row = $data['tx_thuecat_tourist_attraction'][$keys[0]];
        /** @var list<array{mainImage: bool, type: string, author: string, license: array{type: string, author: string}}> $media */
        $media = json_decode((string)$row['media'], true, 512, JSON_THROW_ON_ERROR);
        self::assertCount(4, $media);

        // All four entries are mainImage:false (attraction-with-media has no
        // schema:photo slot) and in source order.
        self::assertSame('GivenName FamilyName', $media[0]['author']);
        self::assertSame('', $media[0]['license']['author']);

        self::assertSame('Full Name', $media[1]['author']);
        self::assertSame('', $media[1]['license']['author']);

        self::assertSame('', $media[2]['author']);
        self::assertSame('Autor aus Lizenz', $media[2]['license']['author']);

        self::assertSame('Full Name', $media[3]['author']);
        self::assertSame('Autor aus Lizenz', $media[3]['license']['author']);

        foreach ($media as $entry) {
            self::assertFalse($entry['mainImage']);
            self::assertSame('image', $entry['type']);
        }

        self::assertSame([], $payload->getTransients());
    }

    #[Test]
    public function nonUrlTransientReferenceRaisesException(): void
    {
        // Simulate a bug in a parser entity that leaks a non-URL value into
        // a transient bucket. The resolver must refuse to dispatch a DB
        // lookup or API fetch for that reference and throw instead.
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/BasicPages.php');

        $payload = $this->parseFixture('043064193523-jcyt.json');
        $this->injectTransient(
            $payload,
            'tx_thuecat_town',
            'https://thuecat.org/resources/043064193523-jcyt',
            'managedBy',
            ['not-a-url']
        );

        $this->expectException(InvalidTransientReferenceException::class);

        $this->get(Resolver::class)->resolve($payload, new ResolverContext(storagePid: 10));
    }

    /**
     * @param list<string> $references
     */
    private function injectTransient(
        DataHandlerPayload $payload,
        string $table,
        string $remoteId,
        string $bucket,
        array $references
    ): void {
        $reflection = new ReflectionProperty(DataHandlerPayload::class, 'transients');
        /** @var array<string, array<string, array<string, list<string>>>> $transients */
        $transients = $reflection->getValue($payload);
        $transients[$table][$remoteId][$bucket] = $references;
        $reflection->setValue($payload, $transients);
    }

    private function parseFixture(string $filename): \WerkraumMedia\ThueCat\Domain\Import\Parser\DataHandlerPayload
    {
        $path = self::FIXTURE_PATH . $filename;
        $decoded = json_decode((string)file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $graph = is_array($decoded) && is_array($decoded['@graph'] ?? null) ? $decoded['@graph'] : [];

        $parser = $this->get(Parser::class);
        $parser->parse($graph);
        return $parser->getDataHandlerPayload();
    }
}
