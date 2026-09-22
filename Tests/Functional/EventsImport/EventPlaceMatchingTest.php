<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional\EventsImport;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractImportTestCase;

// An imported event is related to the ThueCat place it takes place at, by
// resolvable reference or by normalized name and postal code.
class EventPlaceMatchingTest extends AbstractImportTestCase
{
    protected array $testExtensionsToLoad = [
        'werkraummedia/thuecat/',
        'werkraummedia/events/',
    ];

    protected string $fixtureGuzzleBase = __DIR__ . '/Fixtures/Guzzle';
    protected string $fixtureDomain = 'cdb.int.thuecat.org';
    protected string $fixturePath = 'api/resources';

    /**
     * The reference names the place outright, so no address rule runs.
     */
    #[Test]
    public function referencedPlaceIsRelatedToTheEvent(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventReferencedPlacePreState.php');
        $this->expectFetch('e_refloc-hubev.json');

        $this->importConfiguration(1);

        $eventUid = $this->eventUidOf('https://int.thuecat.org/resources/e_refloc-hubev');

        self::assertSame(
            [$eventUid],
            $this->hostedEventsOf('tx_thuecat_tourist_attraction', 1),
            'The referenced attraction does not list the event.'
        );
    }

    #[Test]
    public function referenceWithoutALocalRecordRelatesNoPlace(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventUnknownReferencePreState.php');
        $this->expectFetch('e_unknownref-hubev.json');

        $this->importConfiguration(1);

        // The event still imports; only the relation is absent.
        self::assertGreaterThan(
            0,
            $this->eventUidOf('https://int.thuecat.org/resources/e_unknownref-hubev')
        );

        self::assertSame(
            [],
            $this->hostedEventsOf('tx_thuecat_tourist_attraction', 1),
            'An unresolvable reference related the event to a place anyway.'
        );
    }

    #[Test]
    public function inlineVenueMatchesAPlaceByNameAndPostalCode(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventInlineVenueMatchPreState.php');
        $this->expectFetch('e_bauhaus-hubev.json');

        $this->importConfiguration(1);

        $eventUid = $this->eventUidOf('https://int.thuecat.org/resources/e_bauhaus-hubev');

        self::assertSame(
            [$eventUid],
            $this->hostedEventsOf('tx_thuecat_tourist_attraction', 1),
            'The spelling variant was not matched to the place.'
        );

        self::assertSame(
            [],
            $this->hostedEventsOf('tx_thuecat_tourist_attraction', 2),
            'The postal code alone related the event to the wrong place.'
        );
    }

    #[Test]
    public function differingPostalCodePreventsTheMatch(): void
    {
        $this->importNegativeFixtures();

        self::assertNotContains(
            $this->eventUidOf('https://int.thuecat.org/resources/e_otherzip-hubev'),
            $this->hostedEventsOf('tx_thuecat_tourist_attraction', 1),
            'A venue whose postal code differs was matched on its name alone.'
        );
    }

    /**
     * Street is the least reliable of the three fields — abbreviations,
     * missing house numbers, ranges — so it never establishes or blocks a
     * match.
     */
    #[Test]
    public function differingStreetDoesNotPreventTheMatch(): void
    {
        $this->importNegativeFixtures();

        self::assertContains(
            $this->eventUidOf('https://int.thuecat.org/resources/e_otherstreet-hubev'),
            $this->hostedEventsOf('tx_thuecat_tourist_attraction', 1),
            'A differing street blocked a match that name and postal code agreed on.'
        );
    }

    #[Test]
    public function placeWithoutAStoredAddressNeverMatches(): void
    {
        $this->importNegativeFixtures();

        self::assertSame(
            [],
            $this->hostedEventsOf('tx_thuecat_tourist_attraction', 3),
            'A place carrying no address was matched despite having no postal code.'
        );
    }

    #[Test]
    public function onlyThePlaceInTheImportingSiteIsMatched(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventCrossSiteVenuePreState.php');
        $this->expectFetch('e_bauhaus-hubev.json');

        $this->importConfiguration(1);

        $eventUid = $this->eventUidOf('https://int.thuecat.org/resources/e_bauhaus-hubev');

        self::assertSame(
            [$eventUid],
            $this->hostedEventsOf('tx_thuecat_tourist_attraction', 1)
        );

        self::assertSame(
            [],
            $this->hostedEventsOf('tx_thuecat_tourist_attraction', 2),
            'A place of another site was matched.'
        );
    }

    /**
     * A wrong link puts an event on the wrong place's page, which is worse
     * than no link.
     */
    #[Test]
    public function twoCandidatesRejectTheMatch(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventAmbiguousVenuePreState.php');
        $this->expectFetch('e_bauhaus-hubev.json');

        $this->importConfiguration(1);

        self::assertGreaterThan(
            0,
            $this->eventUidOf('https://int.thuecat.org/resources/e_bauhaus-hubev')
        );

        self::assertSame(
            [],
            $this->hostedEventsOf('tx_thuecat_tourist_attraction', 1),
            'An ambiguous match picked the first candidate.'
        );
        self::assertSame(
            [],
            $this->hostedEventsOf('tx_thuecat_tourist_attraction', 2),
            'An ambiguous match picked the second candidate.'
        );
    }

    #[Test]
    public function organizerMatchFillsManagesEvent(): void
    {
        $this->importOrganizerFixtures();

        self::assertSame(
            [$this->eventUidOf('https://int.thuecat.org/resources/e_orgmatch-hubev')],
            $this->relationOf('tx_thuecat_organisation', 1, 'manages_event')
        );
    }

    #[Test]
    public function oneOrganisationHoldsHostedAndManagedEventsApart(): void
    {
        $this->importOrganizerFixtures();

        self::assertSame(
            [$this->eventUidOf('https://int.thuecat.org/resources/e_orghosts-hubev')],
            $this->relationOf('tx_thuecat_organisation', 1, 'hosts_events'),
            'The hosted event did not land in hosts_events alone.'
        );
        self::assertSame(
            [$this->eventUidOf('https://int.thuecat.org/resources/e_orgmatch-hubev')],
            $this->relationOf('tx_thuecat_organisation', 1, 'manages_event'),
            'The managed event did not land in manages_event alone.'
        );
    }

    /**
     * Only organisations carry manages_event, so a table without that column
     * must never be probed for it.
     */
    #[Test]
    public function organizerReferenceToATableWithoutManagesEventRelatesNothing(): void
    {
        $this->importOrganizerFixtures();

        self::assertGreaterThan(
            0,
            $this->eventUidOf('https://int.thuecat.org/resources/e_orgrefattr-hubev')
        );
        self::assertSame(
            [],
            $this->relationOf('tx_thuecat_tourist_attraction', 1, 'hosts_events'),
            'An organizer reference filled the attraction anyway.'
        );
    }

    /**
     * Removal is not attempted: reconciling against a durable record of what
     * each run related would cost more than the stale entry it prevents.
     */
    #[Test]
    public function matchingOnlyAddsAndNeverDuplicates(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventAddOnlyMatchPreState.php');
        $this->expectFetch('e_bauhaus-hubev.json');

        $this->importConfiguration(1);

        self::assertSame(
            901,
            $this->eventUidOf('https://int.thuecat.org/resources/e_bauhaus-hubev'),
            'The seeded event was not reused, so this run matched a different row.'
        );

        self::assertSame(
            [900, 901],
            $this->hostedEventsOf('tx_thuecat_tourist_attraction', 1)
        );
    }

    /**
     * Several configurations write into the same place records, so adding a
     * match has to preserve what the others related.
     */
    #[Test]
    public function oneConfigurationKeepsAnothersRelations(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventTwoConfigurationsPreState.php');

        $this->expectFetch('e_bauhaus-hubev.json');
        $this->importConfiguration(1);

        $firstUid = $this->eventUidOf('https://int.thuecat.org/resources/e_bauhaus-hubev');
        self::assertSame(
            [$firstUid],
            $this->hostedEventsOf('tx_thuecat_tourist_attraction', 1)
        );

        $this->expectFetch('e_bauhaus2-hubev.json');
        $this->importConfiguration(2);

        $secondUid = $this->eventUidOf('https://int.thuecat.org/resources/e_bauhaus2-hubev');
        self::assertSame(
            [$firstUid, $secondUid],
            $this->hostedEventsOf('tx_thuecat_tourist_attraction', 1),
            'The second configuration dropped the first one\'s relation.'
        );
    }

    #[Test]
    public function successfulReferenceMatchIsLogged(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventReferencedPlacePreState.php');
        $this->expectFetch('e_refloc-hubev.json');

        $this->importConfiguration(1);

        $results = $this->fetchMatchResults();
        self::assertCount(1, $results, 'The run recorded no match result.');

        self::assertSame('https://int.thuecat.org/resources/e_refloc-hubev', $results[0]['remote_id']);
        self::assertSame('tx_thuecat_tourist_attraction', $results[0]['table_name']);
        self::assertSame(1, $results[0]['record_uid']);
        self::assertSame('resolvedByReference', $results[0]['context']['outcome']);
        self::assertSame('hosts_events', $results[0]['context']['field']);
    }

    #[Test]
    public function successfulInlineMatchIsLoggedWithItsRule(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventInlineVenueMatchPreState.php');
        $this->expectFetch('e_bauhaus-hubev.json');

        $this->importConfiguration(1);

        $results = $this->fetchMatchResults();
        self::assertCount(1, $results);

        self::assertSame('tx_thuecat_tourist_attraction', $results[0]['table_name']);
        self::assertSame(1, $results[0]['record_uid']);
        self::assertSame('resolvedByNameAndPostalCode', $results[0]['context']['outcome']);
        self::assertSame('Bauhaus Museum Weimar', $results[0]['context']['name']);
        self::assertSame('99423', $results[0]['context']['postalCode']);
    }

    #[Test]
    public function unmatchedVenueRecordsWhatFoundNoMatch(): void
    {
        $this->importNegativeFixtures();

        $results = array_values(array_filter(
            $this->fetchMatchResults(),
            static fn (array $result): bool => $result['remote_id'] === 'https://int.thuecat.org/resources/e_otherzip-hubev'
        ));
        self::assertCount(1, $results);

        self::assertSame('unmatched', $results[0]['context']['outcome']);
        self::assertSame('', $results[0]['table_name']);
        self::assertSame(0, $results[0]['record_uid']);
        self::assertSame('Bauhaus Museum Weimar', $results[0]['context']['name']);
        self::assertSame('99425', $results[0]['context']['postalCode']);
    }

    #[Test]
    public function unresolvableReferenceIsRecorded(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventUnknownReferencePreState.php');
        $this->expectFetch('e_unknownref-hubev.json');

        $this->importConfiguration(1);

        $results = $this->fetchMatchResults();
        self::assertCount(1, $results);

        self::assertSame('unresolvedReference', $results[0]['context']['outcome']);
        self::assertSame(0, $results[0]['record_uid']);
    }

    #[Test]
    public function ambiguousAttemptRecordsEveryCandidate(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventAmbiguousVenuePreState.php');
        $this->expectFetch('e_bauhaus-hubev.json');

        $this->importConfiguration(1);

        $results = $this->fetchMatchResults();
        self::assertCount(1, $results);

        self::assertSame('ambiguous', $results[0]['context']['outcome']);
        self::assertSame(0, $results[0]['record_uid']);
        self::assertSame(
            ['tx_thuecat_tourist_attraction:1', 'tx_thuecat_tourist_attraction:2'],
            $results[0]['context']['candidates']
        );
    }

    /**
     * @return list<array{remote_id: string, table_name: string, record_uid: int, context: array<mixed>}>
     */
    protected function fetchMatchResults(): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tx_thuecat_import_log_entry');
        $queryBuilder->getRestrictions()->removeAll();

        $rows = $queryBuilder
            ->select('remote_id', 'table_name', 'record_uid', 'context')
            ->from('tx_thuecat_import_log_entry')
            ->where($queryBuilder->expr()->eq(
                'type',
                $queryBuilder->createNamedParameter('eventPlaceMatch')
            ))
            ->orderBy('remote_id')
            ->executeQuery()
            ->fetchAllAssociative()
        ;

        return array_map(static function (array $row): array {
            $context = json_decode(is_string($row['context']) ? $row['context'] : '{}', true);

            return [
                'remote_id' => is_string($row['remote_id']) ? $row['remote_id'] : '',
                'table_name' => is_string($row['table_name']) ? $row['table_name'] : '',
                'record_uid' => is_numeric($row['record_uid']) ? (int)$row['record_uid'] : 0,
                'context' => is_array($context) ? $context : [],
            ];
        }, $rows);
    }

    protected function importOrganizerFixtures(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventOrganizerMatchPreState.php');
        $this->expectFetch('e_orgmatch-hubev.json');
        $this->expectFetch('e_orghosts-hubev.json');
        $this->expectFetch('e_orgrefattr-hubev.json');

        $this->importConfiguration(1);
    }

    protected function importNegativeFixtures(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventInlineVenueNegativesPreState.php');
        $this->expectFetch('e_otherzip-hubev.json');
        $this->expectFetch('e_otherstreet-hubev.json');
        $this->expectFetch('e_noaddrplace-hubev.json');

        $this->importConfiguration(1);
    }

    protected function eventUidOf(string $remoteId): int
    {
        $uid = $this->fetchRowByRemoteId('tx_events_domain_model_event', $remoteId)['uid'] ?? null;
        self::assertIsNumeric($uid, 'No event row for ' . $remoteId);

        return (int)$uid;
    }

    /**
     * @return list<int> the uids the place's hosts_events field holds
     */
    protected function hostedEventsOf(string $table, int $uid): array
    {
        return $this->relationOf($table, $uid, 'hosts_events');
    }

    /**
     * @return list<int> the uids the named relation field holds
     */
    protected function relationOf(string $table, int $uid, string $field): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());

        $value = $queryBuilder
            ->select($field)
            ->from($table)
            ->where($queryBuilder->expr()->eq(
                'uid',
                $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)
            ))
            ->executeQuery()
            ->fetchOne()
        ;

        if (!is_string($value) || $value === '') {
            return [];
        }

        return array_map(intval(...), explode(',', $value));
    }
}
