<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional\Caching;

use Codappix\Typo3PhpDatasets\TestingFramework;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use WerkraumMedia\ThueCat\Domain\Repository\Backend\ImportConfigurationRepository;
use WerkraumMedia\ThueCat\Extension;
use WerkraumMedia\ThueCat\Import\FileFolderAccess;
use WerkraumMedia\ThueCat\Import\Importer;
use WerkraumMedia\ThueCat\Tests\Functional\FileFolderAccessStub;
use WerkraumMedia\ThueCat\Tests\Functional\GuzzleClientFaker;

/**
 * What an import that changes nothing does to the caches.
 *
 * It leaves the records alone — `tstamp` is untouched — and discards their
 * caches anyway. Why is not yet established.
 *
 * These tests pin the CURRENT behaviour rather than the desired one, so the day
 * it improves is visible.
 *
 * Needs the import harness and a real cache backend at once, so it sets both up
 * rather than extending either abstract.
 */
class ImportWithoutChangesTest extends FunctionalTestCase
{
    use TestingFramework;

    protected array $coreExtensionsToLoad = [
        'core',
        'backend',
        'extbase',
        'frontend',
        'install',
        'filelist',
        'filemetadata',
    ];

    protected array $testExtensionsToLoad = [
        'werkraummedia/thuecat',
        'werkraummedia/events',
    ];

    protected array $pathsToLinkInTestInstance = [
        // The FRONTEND site fixture: the import one uses an absolute base and
        // never renders, so no site resolves for a test request.
        'typo3conf/ext/thuecat/Tests/Functional/Fixtures/Frontend/Sites/' => 'typo3conf/sites',
    ];

    protected array $configurationToUseInTestInstance = [
        'EXTENSIONS' => [
            'thuecat' => [
                'apiKey' => null,
            ],
        ],
        'SYS' => [
            'caching' => [
                'cacheConfigurations' => [
                    'pages' => [
                        'backend' => Typo3DatabaseBackend::class,
                    ],
                ],
            ],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        GuzzleClientFaker::registerClient();
        // The import demands a writable target folder up front; this fixture
        // downloads nothing.
        // @phpstan-ignore method.notFound (the functional container has set())
        $this->getContainer()->set(FileFolderAccess::class, new FileFolderAccessStub());
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/BackendUser.php');
        $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('en_US');

        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportWithoutChanges.php');
        $this->setUpFrontendRootPage(1, [
            'EXT:thuecat/Configuration/TypoScript/Default/Setup.typoscript',
            'EXT:thuecat/Tests/Functional/Fixtures/Frontend/PluginRendering.typoscript',
        ]);
    }

    /** The record is not written: `tstamp` stands. */
    #[Test]
    public function anImportFindingNoChangesWritesNoRecord(): void
    {
        $this->import();
        $timestamps = $this->timestamps();
        self::assertNotSame([], $timestamps, 'The first import must store a record.');

        $this->import();

        self::assertSame($timestamps, $this->timestamps());
    }

    /**
     * …and the caches go anyway. Asserted as it is, not as it should be.
     *
     * When this is fixed the test fails and should be inverted — that is the
     * point of it.
     */
    #[Test]
    public function anImportFindingNoChangesDiscardsTheCachesRegardless(): void
    {
        $this->import();
        $this->warmCaches();

        self::assertNotSame([], $this->storedEntries()['teaser'], 'Teasers stored.');
        self::assertNotSame([], $this->storedEntries()['list'], 'A list stored.');

        $this->import();

        self::assertSame(
            ['teaser' => [], 'list' => []],
            $this->storedEntries(),
            'Known limitation: an import discards the caches of every record it visits.'
        );
    }

    #[Test]
    public function anImportChangingARecordDiscardsItsCaches(): void
    {
        $this->import();
        $this->warmCaches();
        self::assertNotSame([], $this->storedEntries()['teaser'], 'Teasers stored.');

        $this->import('attraction-with-category-renamed.json');

        self::assertSame(
            [],
            $this->storedEntries()['teaser'],
            'A changed record discards what displayed it.'
        );
    }

    private function import(string $fixture = 'attraction-with-category.json'): void
    {
        $this->expectFetch($fixture);

        $request = (new ServerRequest())->withAttribute(
            'applicationType',
            SystemEnvironmentBuilder::REQUESTTYPE_BE
        );
        $this->get(ConfigurationManagerInterface::class)->setRequest($request);

        $configuration = $this->get(ImportConfigurationRepository::class)->findOneByUid(1);
        self::assertNotNull($configuration, 'Fixture configuration uid=1 not found.');
        $this->get(Importer::class)->importConfiguration($configuration);
    }

    private function expectFetch(string $filename): void
    {
        $segment = pathinfo($filename, PATHINFO_FILENAME);
        GuzzleClientFaker::expectFileForUrl(
            'https://thuecat.org/resources/' . $segment,
            __DIR__ . '/../Fixtures/Import/Guzzle/thuecat.org/resources/' . $filename
        );
    }

    private function warmCaches(): void
    {
        $this->executeFrontendSubRequest((new InternalRequest())->withPageId(30));
    }

    /**
     * @return array{teaser: list<string>, list: list<string>}
     */
    private function storedEntries(): array
    {
        return [
            'teaser' => $this->identifiers(Extension::CACHE_TEASER),
            'list' => $this->identifiers(Extension::CACHE_LIST),
        ];
    }

    /**
     * @return list<string>
     */
    private function identifiers(string $cacheIdentifier): array
    {
        $backend = $this->get(CacheManager::class)->getCache($cacheIdentifier)->getBackend();
        self::assertInstanceOf(Typo3DatabaseBackend::class, $backend);

        $identifiers = [];
        foreach ($backend->findIdentifiersByTag('tx_thuecat_tourist_attraction') as $identifier) {
            if (is_string($identifier)) {
                $identifiers[] = $identifier;
            }
        }
        foreach ($this->attractionUids() as $uid) {
            foreach ($backend->findIdentifiersByTag('tx_thuecat_tourist_attraction_' . $uid) as $identifier) {
                if (is_string($identifier)) {
                    $identifiers[] = $identifier;
                }
            }
        }
        $identifiers = array_values(array_unique($identifiers));
        sort($identifiers);

        return $identifiers;
    }

    /**
     * `tstamp` is written under the same condition that gates the update, so
     * one column shows whether anything was written at all.
     *
     * @return array<int, int>
     */
    private function timestamps(): array
    {
        $rows = $this->getConnectionPool()
            ->getConnectionForTable('tx_thuecat_tourist_attraction')
            ->select(['uid', 'tstamp'], 'tx_thuecat_tourist_attraction')
            ->fetchAllAssociative()
        ;

        $timestamps = [];
        foreach ($rows as $row) {
            $uid = $row['uid'] ?? null;
            $tstamp = $row['tstamp'] ?? null;
            if (is_scalar($uid) && is_scalar($tstamp)) {
                $timestamps[(int)$uid] = (int)$tstamp;
            }
        }
        ksort($timestamps);

        return $timestamps;
    }

    /**
     * @return list<int>
     */
    private function attractionUids(): array
    {
        return array_keys($this->timestamps());
    }
}
