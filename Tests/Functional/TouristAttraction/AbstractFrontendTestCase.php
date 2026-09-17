<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional\TouristAttraction;

use Codappix\Typo3PhpDatasets\TestingFramework;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Page\CacheHashCalculator;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

abstract class AbstractFrontendTestCase extends FunctionalTestCase
{
    use TestingFramework;

    protected function setUp(): void
    {
        $this->coreExtensionsToLoad = [
            'core',
            'backend',
            'extbase',
            'filelist',
            'filemetadata',
            'frontend',
            'install',
        ];

        $this->testExtensionsToLoad = [
            'werkraummedia/thuecat',
            'werkraummedia/events',
        ];

        $this->pathsToLinkInTestInstance = [
            'typo3conf/ext/thuecat/Tests/Functional/Fixtures/Frontend/Sites/' => 'typo3conf/sites',
        ];

        parent::setUp();

        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Frontend/' . $this->getDataSetFileName());
        $this->setUpFrontendRootPage(1, [
            'EXT:thuecat/Configuration/TypoScript/Default/Setup.typoscript',
            'EXT:thuecat/Tests/Functional/Fixtures/Frontend/' . $this->getRenderingTypoScript(),
        ]);
    }

    // Not getDataSet(): the TestingFramework trait has a private getDataSet(path)
    // that importPHPDataSet relies on; overriding it breaks data-set loading.
    /** PHP data-set filename under Fixtures/Frontend/. */
    abstract protected function getDataSetFileName(): string;

    /** Rendering TypoScript filename under Fixtures/Frontend/. */
    protected function getRenderingTypoScript(): string
    {
        return 'PluginRendering.typoscript';
    }

    /**
     * A request for one page in one language.
     *
     * Language comes from the site's base path, the way the router resolves it.
     * `InternalRequest::withLanguageId()` sets the v8 `L` parameter, which
     * routing ignores: the shell renders translated while records do not.
     */
    protected function pageRequest(int $pageId, int $languageId = 0): InternalRequest
    {
        return (new InternalRequest($this->urlFor($pageId, $languageId)))->withPageId($pageId);
    }

    /**
     * A detail request for one record, carrying a valid cHash.
     *
     * The record argument is cacheable, so a request without a cHash 404s; a
     * real list link carries one, and this computes it the same way core does.
     *
     * @param string $plugin   plugin namespace, e.g. `tx_thuecat_touristattractionshow`
     * @param string $argument the action's record argument, e.g. `attraction`
     */
    protected function detailRequest(
        string $plugin,
        string $argument,
        string $recordUid,
        int $pageId = 10,
        int $languageId = 0
    ): InternalRequest {
        $queryParams = [$plugin => [$argument => $recordUid]];

        // `id` stays in the hash base even for a slug URL: the calculator
        // requires it as input, independent of how the page was routed.
        $cHash = GeneralUtility::makeInstance(CacheHashCalculator::class)->generateForParameters(
            http_build_query($queryParams + ['id' => $pageId])
        );

        return (new InternalRequest($this->urlFor($pageId, $languageId)))
            ->withPageId($pageId)
            ->withQueryParams($queryParams + ['cHash' => $cHash])
        ;
    }

    /** The page's own URL in one language, base path included. */
    protected function urlFor(int $pageId, int $languageId = 0): string
    {
        return rtrim('http://localhost' . $this->languageBase($languageId), '/')
            . $this->slugFor($pageId);
    }

    /** The site's base path for one language, read from its configuration. */
    protected function languageBase(int $languageId): string
    {
        $site = $this->get(SiteFinder::class)->getSiteByPageId(1);

        return rtrim($site->getLanguageById($languageId)->getBase()->getPath(), '/');
    }

    protected function slugFor(int $pageId): string
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());

        $slug = $queryBuilder
            ->select('slug')
            ->from('pages')
            ->where($queryBuilder->expr()->eq(
                'uid',
                $queryBuilder->createNamedParameter($pageId, Connection::PARAM_INT)
            ))
            ->executeQuery()
            ->fetchOne()
        ;

        self::assertIsString($slug, 'Page ' . $pageId . ' must carry a slug to be requested by URL.');

        return $slug === '/' ? '/' : rtrim($slug, '/') . '/';
    }

    /**
     * Render the request and return only the markup of the
     * <section data-{$attribute}="..."> block, so assertions cannot accidentally
     * match a sibling section's output.
     */
    protected function renderedSection(InternalRequest $request, string $attribute, string $value): string
    {
        $body = (string)$this->executeFrontendSubRequest($request)->getBody();

        $marker = '<section data-' . $attribute . '="';
        $open = $marker . $value . '">';
        $start = strpos($body, $open);
        self::assertNotFalse($start, 'Section ' . $attribute . '="' . $value . '" not rendered.');

        $rest = substr($body, $start + strlen($open));
        $end = strpos($rest, $marker);

        return $end === false ? $rest : substr($rest, 0, $end);
    }
}
