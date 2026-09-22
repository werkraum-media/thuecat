<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional\EventsImport;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractImportTestCase;

// The inline schema:organizer node becomes a row in
// tx_events_domain_model_organizer, and the event points at it.
class EventOrganizerImportTest extends AbstractImportTestCase
{
    protected array $testExtensionsToLoad = [
        'werkraummedia/thuecat/',
        'werkraummedia/events/',
    ];

    protected string $fixtureGuzzleBase = __DIR__ . '/Fixtures/Guzzle';
    protected string $fixtureDomain = 'cdb.int.thuecat.org';
    protected string $fixturePath = 'api/resources';

    #[Test]
    public function inlineOrganizerBecomesAnOrganizerRowTheEventPointsAt(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventOrganizerImportPreState.php');
        $this->expectFetch('e_organizer-hubev.json');

        $this->importConfiguration(1);

        $event = $this->fetchRowByRemoteId(
            'tx_events_domain_model_event',
            'https://int.thuecat.org/resources/e_organizer-hubev'
        );

        self::assertSame(1, $this->countRows('tx_events_domain_model_organizer'));

        $organizerUid = $event['organizer'] ?? null;
        self::assertIsNumeric($organizerUid, 'Event carries no organizer uid.');

        $organizer = $this->fetchOrganizerByUid((int)$organizerUid);
        self::assertSame(
            [
                'name' => 'Erfurt Tourismus und Marketing GmbH',
                'street' => 'Benediktsplatz 1',
                'zip' => '99084',
                'city' => 'Erfurt',
                'phone' => '+49 361 66 400',
                'email' => 'info@erfurt-tourismus.de',
                'web' => 'http://www.erfurt-tourismus.de/',
            ],
            [
                'name' => $organizer['name'],
                'street' => $organizer['street'],
                'zip' => $organizer['zip'],
                'city' => $organizer['city'],
                'phone' => $organizer['phone'],
                'email' => $organizer['email'],
                'web' => $organizer['web'],
            ]
        );

        // Identity is ours: ext:events supplies no id concept for organizers,
        // so the row must carry the generated remote_id the next run matches on.
        self::assertIsString($organizer['remote_id']);
        self::assertNotSame('', $organizer['remote_id'], 'Organizer row carries no remote_id.');
    }

    #[Test]
    public function eventWithoutAnOrganizerImportsWithTheColumnUnset(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventWithoutOrganizerPreState.php');
        $this->expectFetch('e_noorg-hubev.json');

        $this->importConfiguration(1);

        self::assertSame(0, $this->countRows('tx_events_domain_model_organizer'));

        $event = $this->fetchRowByRemoteId(
            'tx_events_domain_model_event',
            'https://int.thuecat.org/resources/e_noorg-hubev'
        );
        self::assertIsNumeric($event['organizer']);
        self::assertSame(0, (int)$event['organizer']);
    }

    #[Test]
    public function twoEventsWithOneOrganizerShareOneRow(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventSharedOrganizerPreState.php');
        $this->expectFetch('e_organizer-hubev.json');
        $this->expectFetch('e_sameorg-hubev.json');

        $this->importConfiguration(1);

        self::assertSame(2, $this->countRows('tx_events_domain_model_event'));
        self::assertSame(
            1,
            $this->countRows('tx_events_domain_model_organizer'),
            'The shared organizer was written twice.'
        );

        self::assertSame(
            $this->organizerUidOf('https://int.thuecat.org/resources/e_organizer-hubev'),
            $this->organizerUidOf('https://int.thuecat.org/resources/e_sameorg-hubev')
        );
    }

    /**
     * ext:events identifies organizers by name and writes no identity column,
     * so a row with an empty remote_id is destination.data's. Ours is the only
     * import that may claim it, and it must not.
     */
    #[Test]
    public function organizerRowOfTheOtherImportIsNeverClaimed(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventForeignOrganizerPreState.php');
        $this->expectFetch('e_organizer-hubev.json');

        $this->importConfiguration(1);

        self::assertSame(
            2,
            $this->countRows('tx_events_domain_model_organizer'),
            'The import claimed the foreign row instead of writing its own.'
        );

        $foreign = $this->fetchOrganizerByUid(55);
        self::assertIsString($foreign['remote_id']);
        self::assertSame('', $foreign['remote_id'], 'The foreign row was stamped with our identity.');

        $ours = $this->organizerUidOf('https://int.thuecat.org/resources/e_organizer-hubev');
        self::assertNotSame(55, $ours, 'The event was linked to the foreign row.');

        $ourRow = $this->fetchOrganizerByUid($ours);
        self::assertIsString($ourRow['remote_id']);
        self::assertNotSame('', $ourRow['remote_id']);
    }

    /**
     * Both relations are cleared on the event while the rows they pointed at
     * survive: they are shared between events and between import sources, so
     * dropping one event's relation may not delete them.
     *
     * Skipped, not deleted: it turns green when resolve-relation-reap-gap
     * lands. AbstractEntity::toArray() ends with array_filter(), so an event
     * carrying no organizer has no `organizer` key at all rather than an empty
     * one, and wireChildrenOntoParentFields() never sees a value to clear.
     * Absence of a staged value is indistinguishable from absence of a
     * payload — the same empty-set cause as every other relation kind.
     */
    #[Test]
    public function reimportWithoutTheRelationsClearsTheColumnsAndKeepsTheRows(): void
    {
        self::markTestSkipped('Relation clearing awaits resolve-relation-reap-gap.');

        // @phpstan-ignore-next-line deadCode.unreachable
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventRelationRemovalPreState.php');
        $this->expectFetch('e_noorg-hubev.json');

        $this->importConfiguration(1);

        $event = $this->fetchRowByRemoteId(
            'tx_events_domain_model_event',
            'https://int.thuecat.org/resources/e_noorg-hubev'
        );
        self::assertIsNumeric($event['organizer']);
        self::assertIsNumeric($event['location']);
        self::assertSame(0, (int)$event['organizer'], 'The organizer column was not cleared.');
        self::assertSame(0, (int)$event['location'], 'The location column was not cleared.');

        self::assertSame(1, $this->countRows('tx_events_domain_model_organizer'));
        self::assertSame(1, $this->countRows('tx_events_domain_model_location'));
    }

    /**
     * Passes today by construction rather than by a guard: a record that could
     * not be fetched stages no row, so nothing reaches the clearing path — the
     * same reason the dropped-relation case above cannot work. Kept running so
     * that closing resolve-relation-reap-gap cannot make a technical failure
     * clear a stored relation.
     */
    #[Test]
    public function fetchFailureLeavesTheStoredRelationsUntouched(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventRelationRemovalPreState.php');
        // 401 is what FetchData turns into an InvalidResponseException, and it
        // is not retried — a 500 would need one staged response per attempt.
        $this->expectFailure('e_noorg-hubev', 401);

        $this->importConfigurationReturningSeverity(1);

        $event = $this->fetchRowByRemoteId(
            'tx_events_domain_model_event',
            'https://int.thuecat.org/resources/e_noorg-hubev'
        );
        self::assertIsNumeric($event['organizer']);
        self::assertIsNumeric($event['location']);
        self::assertSame(55, (int)$event['organizer'], 'A technical failure cleared the organizer.');
        self::assertSame(77, (int)$event['location'], 'A technical failure cleared the location.');
    }

    protected function organizerUidOf(string $eventRemoteId): int
    {
        $uid = $this->fetchRowByRemoteId('tx_events_domain_model_event', $eventRemoteId)['organizer'] ?? null;
        self::assertIsNumeric($uid, 'Event ' . $eventRemoteId . ' carries no organizer uid.');

        return (int)$uid;
    }

    /** @return array<string, mixed> */
    protected function fetchOrganizerByUid(int $uid): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tx_events_domain_model_organizer');
        $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());

        $row = $queryBuilder
            ->select('*')
            ->from('tx_events_domain_model_organizer')
            ->where($queryBuilder->expr()->eq(
                'uid',
                $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)
            ))
            ->executeQuery()
            ->fetchAssociative()
        ;

        self::assertIsArray($row, 'No organizer row for uid ' . $uid);

        return $row;
    }
}
