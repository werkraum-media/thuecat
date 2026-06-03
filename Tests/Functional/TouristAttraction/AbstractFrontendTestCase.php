<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional\TouristAttraction;

use Codappix\Typo3PhpDatasets\TestingFramework;
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
            'fluid_styled_content',
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
            'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
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
}
