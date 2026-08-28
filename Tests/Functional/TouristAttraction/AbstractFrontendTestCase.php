<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional\TouristAttraction;

use Codappix\Typo3PhpDatasets\TestingFramework;
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
        int $pageId = 10
    ): InternalRequest {
        $queryParams = [$plugin => [$argument => $recordUid]];

        $cHash = GeneralUtility::makeInstance(CacheHashCalculator::class)->generateForParameters(
            http_build_query($queryParams + ['id' => $pageId])
        );

        return (new InternalRequest())
            ->withPageId($pageId)
            ->withQueryParams($queryParams + ['cHash' => $cHash])
        ;
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
