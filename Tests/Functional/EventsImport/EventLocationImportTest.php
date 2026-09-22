<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional\EventsImport;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractImportTestCase;

// The inline schema:location node becomes a row in
// tx_events_domain_model_location, and the event points at it.
class EventLocationImportTest extends AbstractImportTestCase
{
    protected array $testExtensionsToLoad = [
        'werkraummedia/thuecat/',
        'werkraummedia/events/',
    ];

    protected string $fixtureGuzzleBase = __DIR__ . '/Fixtures/Guzzle';
    protected string $fixtureDomain = 'cdb.int.thuecat.org';
    protected string $fixturePath = 'api/resources';

    #[Test]
    public function inlineVenueBecomesALocationRowTheEventPointsAt(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventLocationImportPreState.php');
        $this->expectFetch('e_101155874-hubev.json');

        $this->importConfiguration(1);

        $event = $this->fetchRowByRemoteId(
            'tx_events_domain_model_event',
            'https://int.thuecat.org/resources/e_101155874-hubev'
        );

        self::assertSame(1, $this->countRows('tx_events_domain_model_location'));

        $locationUid = $event['location'] ?? null;
        self::assertIsNumeric($locationUid, 'Event carries no location uid.');

        $location = $this->fetchLocationByUid((int)$locationUid);
        self::assertSame(
            [
                'name' => 'Messe Erfurt',
                'street' => 'Gothaer Straße 34',
                'zip' => '99094',
                'city' => 'Erfurt - Brühlervorstadt',
                // ThueCat supplies no district, and it is part of the hash.
                'district' => '',
                'country' => 'Deutschland',
                'phone' => '0361 400-0',
                'latitude' => '50.959650',
                'longitude' => '10.992270',
            ],
            [
                'name' => $location['name'],
                'street' => $location['street'],
                'zip' => $location['zip'],
                'city' => $location['city'],
                'district' => $location['district'],
                'country' => $location['country'],
                'phone' => $location['phone'],
                'latitude' => $location['latitude'],
                'longitude' => $location['longitude'],
            ]
        );
    }

    #[Test]
    public function bothCountryEncodingsReachOneLocationRow(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventLocationCountryPreState.php');
        $this->expectFetch('e_101155874-hubev.json');
        $this->expectFetch('e_curiecountry-hubev.json');

        $this->importConfiguration(1);

        self::assertSame(
            1,
            $this->countRows('tx_events_domain_model_location'),
            'The literal and the CURIE country forked the venue into two rows.'
        );

        $literalEvent = $this->fetchRowByRemoteId(
            'tx_events_domain_model_event',
            'https://int.thuecat.org/resources/e_101155874-hubev'
        );
        $curieEvent = $this->fetchRowByRemoteId(
            'tx_events_domain_model_event',
            'https://int.thuecat.org/resources/e_curiecountry-hubev'
        );

        $literalUid = $literalEvent['location'] ?? null;
        $curieUid = $curieEvent['location'] ?? null;
        self::assertIsNumeric($literalUid, 'Literal-country event carries no location uid.');
        self::assertIsNumeric($curieUid, 'CURIE-country event carries no location uid.');
        self::assertSame((int)$literalUid, (int)$curieUid);

        self::assertSame('Deutschland', $this->fetchLocationByUid((int)$curieUid)['country']);
    }

    /**
     * Two events that differ in every respect except their venue shows the convergence holds
     * for events that genuinely have nothing else in common.
     */
    #[Test]
    public function twoEventsAtOneVenueShareOneLocationRow(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventLocationSharedVenuePreState.php');
        $this->expectFetch('e_101155874-hubev.json');
        $this->expectFetch('e_samevenue-hubev.json');

        $this->importConfiguration(1);

        self::assertSame(2, $this->countRows('tx_events_domain_model_event'));
        self::assertSame(
            1,
            $this->countRows('tx_events_domain_model_location'),
            'The shared venue was written twice.'
        );

        self::assertSame(
            $this->locationUidOf('https://int.thuecat.org/resources/e_101155874-hubev'),
            $this->locationUidOf('https://int.thuecat.org/resources/e_samevenue-hubev')
        );
    }

    #[Test]
    public function venueAlreadyPresentIsReusedRatherThanDuplicated(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventLocationReusePreState.php');
        $this->expectFetch('e_101155874-hubev.json');

        $this->importConfiguration(1);

        // The seeded pair, and no third row.
        self::assertSame(2, $this->countRows('tx_events_domain_model_location'));
        self::assertSame(
            77,
            $this->locationUidOf('https://int.thuecat.org/resources/e_101155874-hubev'),
            'The import did not attach to the venue already stored.'
        );
    }

    /**
     * Reuse must respect the hash boundary: rewriting a hashed field would
     * make the row disagree with its own global_id, and the next run would
     * then fail to find it and fork the venue after all.
     */
    #[Test]
    public function reuseLeavesTheHashedFieldsUntouched(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventLocationReusePreState.php');
        $this->expectFetch('e_101155874-hubev.json');

        $this->importConfiguration(1);

        $reused = $this->fetchLocationByUid(77);
        self::assertSame(
            [
                'global_id' => 'e0f2faeb81126342ae90a5445a7d7420fdcd421ab97b0cba09fbcad0695ae5fd',
                'name' => 'Messe Erfurt',
                'street' => 'Gothaer Straße 34',
                'zip' => '99094',
                'city' => 'Erfurt - Brühlervorstadt',
                'district' => '',
                'country' => 'Deutschland',
            ],
            [
                'global_id' => $reused['global_id'],
                'name' => $reused['name'],
                'street' => $reused['street'],
                'zip' => $reused['zip'],
                'city' => $reused['city'],
                'district' => $reused['district'],
                'country' => $reused['country'],
            ]
        );

        // The untouched neighbour, proving the match was on the hash and not
        // on "whatever row happened to be there".
        self::assertSame('Ein anderer Ort', $this->fetchLocationByUid(78)['name']);
    }

    /**
     * 50 of 1815 surveyed venues carry no street. The row is still written:
     * name, postal code and locality identify the venue well enough, and an
     * empty street is part of the hash like any other value.
     */
    #[Test]
    public function venueWithoutAStreetStillProducesALocationRow(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventLocationWithoutStreetPreState.php');
        $this->expectFetch('e_nostreet-hubev.json');

        $this->importConfiguration(1);

        self::assertSame(1, $this->countRows('tx_events_domain_model_location'));

        $location = $this->fetchLocationByUid(
            $this->locationUidOf('https://int.thuecat.org/resources/e_nostreet-hubev')
        );
        self::assertSame('', $location['street']);
        self::assertSame('Messe Erfurt', $location['name']);
        self::assertSame('99094', $location['zip']);
    }

    protected function locationUidOf(string $eventRemoteId): int
    {
        $uid = $this->fetchRowByRemoteId('tx_events_domain_model_event', $eventRemoteId)['location'] ?? null;
        self::assertIsNumeric($uid, 'Event ' . $eventRemoteId . ' carries no location uid.');

        return (int)$uid;
    }

    /** @return array<string, mixed> */
    protected function fetchLocationByUid(int $uid): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tx_events_domain_model_location');
        $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());

        $row = $queryBuilder
            ->select('*')
            ->from('tx_events_domain_model_location')
            ->where($queryBuilder->expr()->eq(
                'uid',
                $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)
            ))
            ->executeQuery()
            ->fetchAssociative()
        ;

        self::assertIsArray($row, 'No location row for uid ' . $uid);

        return $row;
    }
}
