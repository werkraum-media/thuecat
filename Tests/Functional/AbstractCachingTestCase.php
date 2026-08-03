<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use WerkraumMedia\ThueCat\Tests\Functional\TouristAttraction\AbstractFrontendTestCase;

/**
 * Ground for every test that asserts on caching.
 *
 * The testing framework points `pages` and friends at NullBackend, so nothing
 * is stored and no wrong entry can be served — a test asserting on rendered
 * output would pass whatever the caching does. A real backend is restored here.
 *
 * Assert on the cache, not the response body: output looks right whenever the
 * cache is cold.
 */
abstract class AbstractCachingTestCase extends AbstractFrontendTestCase
{
    /** The `tx_thuecat_*` caches are ours and already store; `pages` is core's. */
    protected array $configurationToUseInTestInstance = [
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

    /** A subclass needing a particular page tree names its own fixture. */
    protected function getDataSetFileName(): string
    {
        return 'TouristAttractionsForList.php';
    }

    /**
     * Saves through DataHandler, which is what emits the cache tags. Needs a
     * backend user: start() falls back to $GLOBALS['BE_USER'].
     *
     * @param array<string, string> $values
     */
    protected function saveRecord(
        int $uid,
        array $values,
        string $table = 'tx_thuecat_tourist_attraction'
    ): void {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->bypassAccessCheckForRecords = true;
        $dataHandler->start([$table => [$uid => $values]], []);
        $dataHandler->process_datamap();

        // v13 does not type errorLog's members.
        $errors = [];
        foreach ($dataHandler->errorLog as $error) {
            $errors[] = is_scalar($error) ? (string)$error : get_debug_type($error);
        }

        self::assertSame(
            [],
            $dataHandler->errorLog,
            'DataHandler must save cleanly: ' . implode(', ', $errors)
        );
    }

    /** Call from tests that save records. */
    protected function setUpBackendUserForDataHandler(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Frontend/BackendUser.csv');
        $this->setUpBackendUser(1);
    }

    /**
     * A translated page is requested by its DEFAULT uid plus the language, not
     * by its overlay's uid.
     *
     * @param array<string, mixed> $pluginArguments Under the plugin namespace.
     */
    protected function request(
        int $pageId,
        array $pluginArguments = [],
        string $pluginNamespace = 'tx_thuecat_touristattractionlist',
        int $languageId = 0
    ): ResponseInterface {
        $request = (new InternalRequest())->withPageId($pageId);
        if ($languageId > 0) {
            $request = $request->withLanguageId($languageId);
        }
        if ($pluginArguments !== []) {
            $request = $request->withQueryParams([$pluginNamespace => $pluginArguments]);
        }

        return $this->executeFrontendSubRequest($request);
    }

    /**
     * Entries of one cache tagged for any of the given records.
     *
     * @param list<int> $recordUids
     *
     * @return list<string>
     */
    protected function identifiersForRecords(
        string $cacheIdentifier,
        array $recordUids,
        string $table = 'tx_thuecat_tourist_attraction'
    ): array {
        $identifiers = [];
        foreach ($recordUids as $uid) {
            foreach ($this->cacheIdentifiersTaggedWith($cacheIdentifier, $table . '_' . $uid) as $identifier) {
                $identifiers[$identifier] = true;
            }
        }
        $identifiers = array_keys($identifiers);
        sort($identifiers);

        return $identifiers;
    }

    /**
     * Stored content keyed by identifier, for comparing across requests.
     *
     * @param list<string> $identifiers
     *
     * @return array<string, string>
     */
    protected function storedContent(string $cacheIdentifier, array $identifiers): array
    {
        $stored = [];
        foreach ($identifiers as $identifier) {
            $stored[$identifier] = $this->cachedContent($cacheIdentifier, $identifier);
        }

        return $stored;
    }

    /**
     * Entry identifiers currently held by a cache, sorted for comparability.
     *
     * @return list<string>
     */
    protected function cacheIdentifiersTaggedWith(string $cacheIdentifier, string $tag): array
    {
        $backend = $this->getCache($cacheIdentifier)->getBackend();
        self::assertInstanceOf(
            Typo3DatabaseBackend::class,
            $backend,
            'Cache "' . $cacheIdentifier . '" must store, or an assertion on it proves nothing.'
        );

        $identifiers = [];
        foreach ($backend->findIdentifiersByTag($tag) as $identifier) {
            if (is_string($identifier)) {
                $identifiers[] = $identifier;
            }
        }
        sort($identifiers);

        return $identifiers;
    }

    protected function getCache(string $cacheIdentifier): FrontendInterface
    {
        return $this->get(CacheManager::class)->getCache($cacheIdentifier);
    }

    /** Stored HTML of one entry; fails rather than casts on anything else. */
    protected function cachedContent(string $cacheIdentifier, string $entryIdentifier): string
    {
        $content = $this->getCache($cacheIdentifier)->get($entryIdentifier);
        self::assertIsString(
            $content,
            'Entry "' . $entryIdentifier . '" must hold a string.'
        );

        return $content;
    }

    /**
     * Every entry must hold markup, not merely exist: an empty string caches as
     * readily as a page of HTML.
     */
    protected function assertCachedEntriesHaveContent(string $cacheIdentifier, string $tag): void
    {
        $identifiers = $this->cacheIdentifiersTaggedWith($cacheIdentifier, $tag);
        self::assertNotSame([], $identifiers, 'No entry stored, so there is nothing to check.');

        foreach ($identifiers as $identifier) {
            $content = $this->cachedContent($cacheIdentifier, $identifier);
            self::assertNotSame(
                '',
                trim($content),
                'Entry "' . $identifier . '" holds nothing but whitespace.'
            );
            self::assertStringContainsString(
                '<',
                $content,
                'Entry "' . $identifier . '" holds no markup.'
            );
        }
    }
}
