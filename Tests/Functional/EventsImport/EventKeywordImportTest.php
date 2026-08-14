<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional\EventsImport;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\Connection;
use WerkraumMedia\ThueCat\Domain\Repository\Backend\ImportConfigurationRepository;
use WerkraumMedia\ThueCat\Import\Importer;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractImportTestCase;

/**
 * Events store keywords in `keywords_relation`, not the `keywords` column their
 * own importer fills. All three upstream shapes are exercised here and in the
 * place suite alike: the observed split (typed literals on places, free text on
 * events) describes today's data, not a contract.
 */
class EventKeywordImportTest extends AbstractImportTestCase
{
    protected array $testExtensionsToLoad = [
        'werkraummedia/thuecat/',
        'werkraummedia/events/',
    ];

    protected string $fixtureGuzzleBase = __DIR__ . '/Fixtures/Guzzle';
    protected string $fixtureDomain = 'cdb.int.thuecat.org';
    protected string $fixturePath = 'api/resources';

    #[Test]
    public function relatesEveryKeywordShapeToTheEvent(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventKeywordImportPreState.php');
        $this->expectKeywordFetches();

        $this->runImport();

        $eventUid = $this->eventUid();

        self::assertSame(
            ['Comedy', 'Erfurt', 'Landeshauptstadt Erfurt', 'Weingut'],
            $this->relatedKeywordTitles($eventUid),
            'Reference, ontology literal and free text all become relations.'
        );
    }

    #[Test]
    public function keywordsLandInTheirOwnFieldNotTheCategoryOne(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventKeywordImportPreState.php');
        $this->expectKeywordFetches();

        $this->runImport();

        $eventUid = $this->eventUid();

        self::assertSame(
            ['Kulturveranstaltung'],
            $this->relatedTitles($eventUid, 'categories'),
            'The @type category relation holds only its own member.'
        );
        self::assertNotContains(
            'Kulturveranstaltung',
            $this->relatedKeywordTitles($eventUid),
            'A type category must never appear among the keywords.'
        );
    }

    #[Test]
    public function everyKeywordSitsUnderTheKeywordAnchor(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventKeywordImportPreState.php');
        $this->expectKeywordFetches();

        $this->runImport();

        foreach ($this->remoteIdsOfCategoriesUnder(100) as $remoteId) {
            self::assertStringStartsNotWith(
                'keyword:',
                $remoteId,
                'A keyword category appeared under the category anchor.'
            );
        }
    }

    // The keywords column belongs to ext:events; this importer must not touch it.
    #[Test]
    public function leavesTheLegacyKeywordStringUntouched(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventKeywordImportLegacyStringPreState.php');
        $this->expectKeywordFetches();

        $this->runImport();

        self::assertSame(
            'Vorbestehend, aus anderer Quelle',
            $this->eventColumn($this->eventUid(), 'keywords'),
            'The string column belongs to another importer.'
        );
        self::assertNotSame(
            [],
            $this->relatedKeywordTitles($this->eventUid()),
            'Relations are written alongside it.'
        );
    }

    private function expectKeywordFetches(): void
    {
        $this->expectFetch('e_19542-keywords.json');
        $this->expectFetchForUrl(
            'https://thuecat.org/resources/475728955106-qdcc',
            'thuecat.org/resources/475728955106-qdcc.json'
        );
        $this->expectFetchForUrl(
            'https://thuecat.org/resources/155933862969-mofh',
            'thuecat.org/resources/155933862969-mofh.json'
        );
        $this->expectFetchForUrl(
            'https://thuecat.org/ontology/thuecat/1.0/CollisionWeingut',
            'thuecat.org/ontology/thuecat/1.0/CollisionWeingut.json'
        );
        $this->expectFetchForUrl(
            'https://thuecat.org/ontology/thuecat/1.0/Ambiance',
            'thuecat.org/ontology/thuecat/1.0/Ambiance.json'
        );
    }

    private function runImport(): void
    {
        $this->workaroundExtbaseConfiguration();
        $configuration = $this->get(ImportConfigurationRepository::class)->findOneByUid(1);
        self::assertNotNull($configuration, 'Import configuration not found in pre-state.');
        $this->get(Importer::class)->importConfiguration($configuration);
    }

    private function eventUid(): int
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tx_events_domain_model_event');
        $queryBuilder->getRestrictions()->removeAll();
        $uid = $queryBuilder->select('uid')
            ->from('tx_events_domain_model_event')
            ->where(
                $queryBuilder->expr()->eq(
                    'remote_id',
                    $queryBuilder->createNamedParameter(
                        'https://cdb.int.thuecat.org/api/resources/e_19542-keywords'
                    )
                ),
                $queryBuilder->expr()->eq(
                    'sys_language_uid',
                    $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                )
            )
            ->executeQuery()
            ->fetchOne()
        ;

        self::assertIsNumeric($uid, 'Event was not imported.');

        return (int)$uid;
    }

    private function eventColumn(int $uid, string $column): string
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tx_events_domain_model_event');
        $queryBuilder->getRestrictions()->removeAll();
        $value = $queryBuilder->select($column)
            ->from('tx_events_domain_model_event')
            ->where($queryBuilder->expr()->eq(
                'uid',
                $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)
            ))
            ->executeQuery()
            ->fetchOne()
        ;

        return is_string($value) ? $value : '';
    }

    /** @return list<string> */
    private function relatedKeywordTitles(int $ownerUid): array
    {
        return $this->relatedTitles($ownerUid, 'keywords_relation');
    }

    /** @return list<string> */
    private function relatedTitles(int $ownerUid, string $field): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('sys_category_record_mm');
        $uids = $queryBuilder->select('uid_local')
            ->from('sys_category_record_mm')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid_foreign',
                    $queryBuilder->createNamedParameter($ownerUid, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'tablenames',
                    $queryBuilder->createNamedParameter('tx_events_domain_model_event')
                ),
                $queryBuilder->expr()->eq('fieldname', $queryBuilder->createNamedParameter($field))
            )
            ->executeQuery()
            ->fetchFirstColumn()
        ;

        $titles = [];
        foreach ($uids as $uid) {
            if (!is_numeric($uid)) {
                continue;
            }
            $categoryQuery = $this->getConnectionPool()->getQueryBuilderForTable('sys_category');
            $categoryQuery->getRestrictions()->removeAll();
            $title = $categoryQuery->select('title')
                ->from('sys_category')
                ->where($categoryQuery->expr()->eq(
                    'uid',
                    $categoryQuery->createNamedParameter((int)$uid, Connection::PARAM_INT)
                ))
                ->executeQuery()
                ->fetchOne()
            ;
            if (is_string($title)) {
                $titles[] = $title;
            }
        }

        sort($titles);

        return $titles;
    }

    /** @return list<string> */
    private function remoteIdsOfCategoriesUnder(int $parent): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('sys_category');
        $queryBuilder->getRestrictions()->removeAll();

        $rows = $queryBuilder
            ->select('remote_id')
            ->from('sys_category')
            ->where($queryBuilder->expr()->eq(
                'parent',
                $queryBuilder->createNamedParameter($parent, Connection::PARAM_INT)
            ))
            ->orderBy('uid')
            ->executeQuery()
            ->fetchFirstColumn()
        ;

        $remoteIds = [];
        foreach ($rows as $remoteId) {
            $remoteIds[] = is_string($remoteId) ? $remoteId : '';
        }

        return $remoteIds;
    }
}
