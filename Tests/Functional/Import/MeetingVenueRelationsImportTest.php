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

namespace WerkraumMedia\ThueCat\Tests\Functional\Import;

use PHPUnit\Framework\Attributes\Test;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractImportTestCase;

/**
 * Each fixture is a real venue payload trimmed to the references under test.
 */
final class MeetingVenueRelationsImportTest extends AbstractImportTestCase
{
    private const TABLE = 'tx_thuecat_tourist_attraction';

    private const WIELANDGUT = 'https://thuecat.org/resources/117034427781-ndta';

    private const RIDING_HALL = 'https://thuecat.org/resources/700475118524-zdca';

    private const CULTURAL_ESTATE = 'https://thuecat.org/resources/332021174274-eyhf';

    private const EVENT = 'https://thuecat.org/resources/e_100967287-hubev';

    private const NAMED_REFERENCE_FIXTURES = [
        '775941615571-khyx.json', '799431457224-zhww.json', '312868368592-wnce.json', '304061111979-rmed.json',
        '290287712606-zkwq.json',
    ];

    #[Test]
    public function operatorIsRelatedApartFromTheManagingOrganisation(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsMeetingVenueRelations.php');
        $this->expectFetchForUrl(self::WIELANDGUT, 'thuecat.org/resources/meeting-venue-operated-by-other-organisation.json');
        $this->expectFetch('079491439271-qqjm.json');
        $this->expectFetch('214681324608-rqzg.json');

        $this->importConfiguration(1);

        $this->assertSame([
            'managed_by' => $this->fetchUidByRemoteId('tx_thuecat_organisation', 'https://thuecat.org/resources/079491439271-qqjm'),
            'operated_by' => $this->fetchUidByRemoteId('tx_thuecat_organisation', 'https://thuecat.org/resources/214681324608-rqzg'),
        ], $this->fetchColumnsByRemoteId(self::TABLE, self::WIELANDGUT, ['managed_by', 'operated_by']));
    }

    // A stored trail is fetched again like every reference of a root; upstream delivers this one untyped.
    #[Test]
    public function touchedTrailIsRelated(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsMeetingVenueRelations.php');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Import/TrailTouchedByMeetingVenue.csv');
        $this->expectFetchForUrl(self::WIELANDGUT, 'thuecat.org/resources/meeting-venue-touching-trail.json');
        $this->expectFetch('e_804174798-oatour.json');

        $this->importConfiguration(1);

        $this->assertSame('41', $this->fetchRowByRemoteId(self::TABLE, self::WIELANDGUT)['geo_touches_trail']);
    }

    #[Test]
    public function storedEventIsAddedToHostedEvents(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsMeetingVenueRelations.php');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Import/EventNamedByMeetingVenue.csv');
        $this->expectFetchForUrl(self::WIELANDGUT, 'thuecat.org/resources/meeting-venue-naming-event.json');

        $this->importConfiguration(1);

        $this->assertSame([901], $this->fetchMmRelatedUids(self::TABLE, $this->fetchUidByRemoteId(self::TABLE, self::WIELANDGUT), 'hosts_events'));
    }

    // The faker fails the test on any fetch it was not told to expect.
    #[Test]
    public function unknownEventIsNeitherFetchedNorReported(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsMeetingVenueRelations.php');
        $this->expectFetchForUrl(self::WIELANDGUT, 'thuecat.org/resources/meeting-venue-naming-event.json');

        $this->importConfiguration(1);

        $this->assertSame([], $this->fetchMmRelatedUids(self::TABLE, $this->fetchUidByRemoteId(self::TABLE, self::WIELANDGUT), 'hosts_events'));
        $entries = $this->getConnectionPool()
            ->getConnectionForTable('tx_thuecat_import_log_entry')
            ->select(['type', 'message', 'context'], 'tx_thuecat_import_log_entry')
            ->fetchAllAssociative()
        ;
        $naming = array_filter(
            $entries,
            static fn (array $entry): bool => str_contains((string)json_encode($entry, JSON_UNESCAPED_SLASHES), self::EVENT)
        );
        $this->assertSame([], array_values($naming));
    }

    #[Test]
    public function eventOfAnotherSiteIsNotRelated(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsMeetingVenueRelations.php');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Import/EventNamedByMeetingVenueInOtherSite.csv');
        $this->expectFetchForUrl(self::WIELANDGUT, 'thuecat.org/resources/meeting-venue-naming-event.json');

        $this->importConfiguration(1);

        $this->assertSame([], $this->fetchMmRelatedUids(self::TABLE, $this->fetchUidByRemoteId(self::TABLE, self::WIELANDGUT), 'hosts_events'));
    }

    #[Test]
    public function earlierMatchOfTheEventImportIsKept(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsMeetingVenueRelations.php');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Import/MeetingVenueHostingEarlierMatch.csv');
        $this->expectFetchForUrl(self::WIELANDGUT, 'thuecat.org/resources/meeting-venue-naming-event.json');

        $this->importConfiguration(1);

        $this->assertSame([900, 901], $this->fetchMmRelatedUids(self::TABLE, 1, 'hosts_events'));
    }

    #[Test]
    public function personsWhoWereThereAreStoredByNamePerLanguage(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsMeetingVenueRelations.php');
        $this->expectFetchForUrl(self::WIELANDGUT, 'thuecat.org/resources/meeting-venue-with-named-references.json');
        $this->expectFetch(...self::NAMED_REFERENCE_FIXTURES);

        $this->importConfiguration(1);

        $this->assertSame('Christoph Martin Wieland', $this->fetchRowByRemoteId(self::TABLE, self::WIELANDGUT)['who_was_here']);
        $this->assertSame('Christoph Martin Wieland', $this->fetchTranslationsByParentRemoteId(self::TABLE, self::WIELANDGUT)[1]['who_was_here'] ?? null);
    }

    // Audiences arrive in German only; no other language's name stands in.
    #[Test]
    public function audiencesAreStoredOnlyInTheLanguagesTheyAreNamedIn(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsMeetingVenueRelations.php');
        $this->expectFetchForUrl(self::WIELANDGUT, 'thuecat.org/resources/meeting-venue-with-named-references.json');
        $this->expectFetch(...self::NAMED_REFERENCE_FIXTURES);

        $this->importConfiguration(1);

        $this->assertSame("Zielgruppe Konsum-Hedonisten\nZielgruppe Postmateriell\nKulturreisende\nZielgruppe Traditionelle", $this->fetchRowByRemoteId(self::TABLE, self::WIELANDGUT)['tourist_types']);
        $this->assertSame('', $this->fetchTranslationsByParentRemoteId(self::TABLE, self::WIELANDGUT)[1]['tourist_types'] ?? null);
    }

    #[Test]
    public function pagesAboutTheVenueAreStoredAsUris(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsMeetingVenueRelations.php');
        $this->expectFetchForUrl(self::WIELANDGUT, 'thuecat.org/resources/meeting-venue-with-named-references.json');
        $this->expectFetch(...self::NAMED_REFERENCE_FIXTURES);

        $this->importConfiguration(1);

        $this->assertSame("https://thuecat.org/resources/724271217629-ffrb\nhttps://thuecat.org/resources/626444958944-bnpb", $this->fetchRowByRemoteId(self::TABLE, self::WIELANDGUT)['subject_of']);
    }

    #[Test]
    public function architectIsStoredByName(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsMeetingVenueRelations.php');
        $this->expectFetch('700475118524-zdca.json');
        $this->expectFetch('795890427838-jmby.json');

        $this->importConfiguration(2);

        $this->assertSame('Heinrich Gentz', $this->fetchRowByRemoteId(self::TABLE, self::RIDING_HALL)['architects']);
    }

    #[Test]
    public function managedObjectIsRelated(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsMeetingVenueRelations.php');
        $this->expectFetch('332021174274-eyhf.json');
        $this->expectFetch('201518958399-qbmh.json');

        $this->importConfiguration(3);

        $this->assertSame((string)$this->fetchUidByRemoteId(self::TABLE, 'https://thuecat.org/resources/201518958399-qbmh'), $this->fetchRowByRemoteId(self::TABLE, self::CULTURAL_ESTATE)['managed_objects']);
    }

    #[Test]
    public function droppedOperatorIsUnrelated(): void
    {
        $this->markTestSkipped('Reference writes never clear a dropped reference; see resolve-relation-reap-gap.');

        // @phpstan-ignore-next-line deadCode.unreachable
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsMeetingVenueRelations.php');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Import/MeetingVenueWithOperatorAndTrails.csv');
        $this->expectFetch('117034427781-ndta.json');

        $this->importConfiguration(1);

        $this->assertSame(0, $this->fetchRowByRemoteId(self::TABLE, self::WIELANDGUT)['operated_by']);
    }

    #[Test]
    public function oneDroppedTrailIsUnrelated(): void
    {
        $this->markTestSkipped('Reference writes never clear a dropped reference; see resolve-relation-reap-gap.');

        // @phpstan-ignore-next-line deadCode.unreachable
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsMeetingVenueRelations.php');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Import/MeetingVenueWithOperatorAndTrails.csv');
        $this->expectFetchForUrl(self::WIELANDGUT, 'thuecat.org/resources/meeting-venue-touching-trail.json');
        $this->expectFetch('e_804174798-oatour.json');

        $this->importConfiguration(1);

        $this->assertSame('41', $this->fetchRowByRemoteId(self::TABLE, self::WIELANDGUT)['geo_touches_trail']);
    }

    #[Test]
    public function allDroppedTrailsAreUnrelated(): void
    {
        $this->markTestSkipped('Reference writes never clear a dropped reference; see resolve-relation-reap-gap.');

        // @phpstan-ignore-next-line deadCode.unreachable
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsMeetingVenueRelations.php');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Import/MeetingVenueWithOperatorAndTrails.csv');
        $this->expectFetch('117034427781-ndta.json');

        $this->importConfiguration(1);

        $this->assertSame('', $this->fetchRowByRemoteId(self::TABLE, self::WIELANDGUT)['geo_touches_trail']);
    }

    #[Test]
    public function droppedManagedObjectIsUnrelated(): void
    {
        $this->markTestSkipped('Reference writes never clear a dropped reference; see resolve-relation-reap-gap.');

        // @phpstan-ignore-next-line deadCode.unreachable
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsMeetingVenueRelations.php');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Import/MeetingVenueWithManagedObject.csv');
        $this->expectFetchForUrl(self::CULTURAL_ESTATE, 'thuecat.org/resources/meeting-venue-without-managed-objects.json');

        $this->importConfiguration(3);

        $this->assertSame('', $this->fetchRowByRemoteId(self::TABLE, self::CULTURAL_ESTATE)['managed_objects']);
    }
}
