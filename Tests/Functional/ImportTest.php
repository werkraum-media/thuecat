<?php

namespace WerkraumMedia\ThueCat\Tests\Functional;

/*
 * Copyright (C) 2021 Daniel Siepmann <coding@daniel-siepmann.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301, USA.
 */

use Csa\GuzzleHttp\Middleware\Cache\Adapter\MockStorageAdapter;
use Csa\GuzzleHttp\Middleware\Cache\MockMiddleware;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Extbase\Core\Bootstrap;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase as TestCase;

/**
 * @covers \WerkraumMedia\ThueCat\Controller\Backend\AbstractController
 * @covers \WerkraumMedia\ThueCat\Controller\Backend\ImportController
 * @covers \WerkraumMedia\ThueCat\DependencyInjection\ConverterPass
 * @covers \WerkraumMedia\ThueCat\DependencyInjection\UrlProvidersPass
 * @covers \WerkraumMedia\ThueCat\Domain\Import\Importer\SaveData
 * @covers \WerkraumMedia\ThueCat\Domain\Repository\Backend\ImportLogRepository
 * @covers \WerkraumMedia\ThueCat\Domain\Repository\Backend\OrganisationRepository
 * @covers \WerkraumMedia\ThueCat\Domain\Repository\Backend\TownRepository
 * @covers \WerkraumMedia\ThueCat\Extension
 * @covers \WerkraumMedia\ThueCat\Typo3Wrapper\TranslationService
 * @covers \WerkraumMedia\ThueCat\View\Backend\Menu
 *
 * @uses \WerkraumMedia\ThueCat\Domain\Import\Converter\Organisation
 * @uses \WerkraumMedia\ThueCat\Domain\Import\Converter\Registry
 * @uses \WerkraumMedia\ThueCat\Domain\Import\Converter\TouristAttraction
 * @uses \WerkraumMedia\ThueCat\Domain\Import\Converter\TouristInformation
 * @uses \WerkraumMedia\ThueCat\Domain\Import\Converter\Town
 * @uses \WerkraumMedia\ThueCat\Domain\Import\Importer
 * @uses \WerkraumMedia\ThueCat\Domain\Import\Importer\FetchData
 * @uses \WerkraumMedia\ThueCat\Domain\Import\Importer\LanguageHandling
 * @uses \WerkraumMedia\ThueCat\Domain\Import\JsonLD\Parser
 * @uses \WerkraumMedia\ThueCat\Domain\Import\JsonLD\Parser\Address
 * @uses \WerkraumMedia\ThueCat\Domain\Import\JsonLD\Parser\GenericFields
 * @uses \WerkraumMedia\ThueCat\Domain\Import\JsonLD\Parser\LanguageValues
 * @uses \WerkraumMedia\ThueCat\Domain\Import\JsonLD\Parser\Media
 * @uses \WerkraumMedia\ThueCat\Domain\Import\JsonLD\Parser\Offers
 * @uses \WerkraumMedia\ThueCat\Domain\Import\JsonLD\Parser\OpeningHours
 * @uses \WerkraumMedia\ThueCat\Domain\Import\JsonLD\Parser\OpeningHours
 * @uses \WerkraumMedia\ThueCat\Domain\Import\Model\EntityCollection
 * @uses \WerkraumMedia\ThueCat\Domain\Import\Model\GenericEntity
 * @uses \WerkraumMedia\ThueCat\Domain\Import\RequestFactory
 * @uses \WerkraumMedia\ThueCat\Domain\Import\UrlProvider\Registry
 * @uses \WerkraumMedia\ThueCat\Domain\Import\UrlProvider\StaticUrlProvider
 * @uses \WerkraumMedia\ThueCat\Domain\Import\UrlProvider\SyncScopeUrlProvider
 * @uses \WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfiguration
 * @uses \WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLog
 * @uses \WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLogEntry
 * @uses \WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLogEntry
 *
 * @testdox The import
 */
class ImportTest extends TestCase
{
    use ProphecyTrait;

    protected $coreExtensionsToLoad = [
        'core',
        'backend',
        'extbase',
        'frontend',
    ];

    protected $testExtensionsToLoad = [
        'typo3conf/ext/thuecat/',
    ];

    protected $pathsToLinkInTestInstance = [
        'typo3conf/ext/thuecat/Tests/Functional/Fixtures/Import/Sites/' => 'typo3conf/sites',
    ];

    protected $configurationToUseInTestInstance = [
        'EXTENSIONS' => [
            'thuecat' => [
                'apiKey' => null,
            ],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler']['recorder'] = new MockMiddleware(
            new MockStorageAdapter(
                __DIR__ . '/Fixtures/Import/Guzzle/'
            ),
            // Set to 'record' to record requests and create fixtures.
            '',
            true
        );

        $this->setUpBackendUserFromFixture(1);

        $GLOBALS['LANG'] = $this->getContainer()->get(LanguageService::class);

        // We are NOT in cli (simulate backend request environment)
        Environment::initialize(
            Environment::getContext(),
            false,
            Environment::isComposerMode(),
            Environment::getProjectPath(),
            Environment::getPublicPath(),
            Environment::getVarPath(),
            Environment::getConfigPath(),
            Environment::getCurrentScript(),
            'UNIX'
        );
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['LANG']);
        unset($GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler']['recorder']);
        unset($GLOBALS['TYPO3_REQUEST']);
        $_GET = [];

        parent::tearDown();
    }

    /**
     * @test
     */
    public function importsFreshOrganization(): void
    {
        $this->importDataSet(__DIR__ . '/Fixtures/Import/ImportsFreshOrganization.xml');

        $serverRequest = $this->getServerRequest();

        $extbaseBootstrap = $this->getContainer()->get(Bootstrap::class);
        $extbaseBootstrap->handleBackendRequest($serverRequest->reveal());

        self::assertCount(
            1,
            $this->getAllRecords('tx_thuecat_organisation'),
            'Did not create expected number of organisations.'
        );
        self::assertCount(
            1,
            $this->getAllRecords('tx_thuecat_import_log'),
            'Did not create expected number of import logs.'
        );
        self::assertCount(
            1,
            $this->getAllRecords('tx_thuecat_import_log_entry'),
            'Did not create expected number of import log entries.'
        );
        $this->assertCSVDataSet('EXT:thuecat/Tests/Functional/Fixtures/Import/ImportsFreshOrganization.csv');
    }

    /**
     * @test
     */
    public function updatesExistingOrganization(): void
    {
        $this->importDataSet(__DIR__ . '/Fixtures/Import/UpdatesExistingOrganization.xml');

        $serverRequest = $this->getServerRequest();

        $extbaseBootstrap = $this->getContainer()->get(Bootstrap::class);
        $extbaseBootstrap->handleBackendRequest($serverRequest->reveal());

        $organisations = $this->getAllRecords('tx_thuecat_organisation');
        self::assertCount(1, $organisations);
        self::assertSame('https://thuecat.org/resources/018132452787-ngbe', $organisations[0]['remote_id']);
        self::assertSame('Erfurt Tourismus und Marketing GmbH', $organisations[0]['title']);
        self::assertSame('1', $organisations[0]['uid']);

        $importLogs = $this->getAllRecords('tx_thuecat_import_log');
        self::assertCount(1, $importLogs);
        self::assertSame('1', $importLogs[0]['configuration']);
        self::assertSame('1', $importLogs[0]['uid']);

        $importLogEntries = $this->getAllRecords('tx_thuecat_import_log_entry');
        self::assertCount(1, $importLogEntries);
        self::assertSame('1', $importLogEntries[0]['import_log']);
        self::assertSame('1', $importLogEntries[0]['record_uid']);
        self::assertSame('tx_thuecat_organisation', $importLogEntries[0]['table_name']);
        self::assertSame('0', $importLogEntries[0]['insertion']);
        self::assertSame('[]', $importLogEntries[0]['errors']);
    }

    /**
     * @test
     */
    public function importsTown(): void
    {
        $this->importDataSet(__DIR__ . '/Fixtures/Import/ImportsTown.xml');

        $serverRequest = $this->getServerRequest();

        $extbaseBootstrap = $this->getContainer()->get(Bootstrap::class);
        $extbaseBootstrap->handleBackendRequest($serverRequest->reveal());

        $towns = $this->getAllRecords('tx_thuecat_town');
        self::assertCount(1, $towns);

        $this->assertCSVDataSet('EXT:thuecat/Tests/Functional/Fixtures/Import/ImportsTown.csv');
    }

    /**
     * @test
     */
    public function importsTownWithRelation(): void
    {
        $this->importDataSet(__DIR__ . '/Fixtures/Import/ImportsTownWithRelation.xml');

        $serverRequest = $this->getServerRequest();

        $extbaseBootstrap = $this->getContainer()->get(Bootstrap::class);
        $extbaseBootstrap->handleBackendRequest($serverRequest->reveal());

        $towns = $this->getAllRecords('tx_thuecat_town');
        self::assertCount(1, $towns);

        $this->assertCSVDataSet('EXT:thuecat/Tests/Functional/Fixtures/Import/ImportsTownWithRelation.csv');
    }

    /**
     * @test
     */
    public function importsTouristAttractionsWithRelations(): void
    {
        $this->importDataSet(__DIR__ . '/Fixtures/Import/ImportsTouristAttractionsWithRelations.xml');

        $serverRequest = $this->getServerRequest();

        $extbaseBootstrap = $this->getContainer()->get(Bootstrap::class);
        $extbaseBootstrap->handleBackendRequest($serverRequest->reveal());

        $touristAttractions = $this->getAllRecords('tx_thuecat_tourist_attraction');
        self::assertCount(8, $touristAttractions);

        $this->assertCSVDataSet('EXT:thuecat/Tests/Functional/Fixtures/Import/ImportsTouristAttractionsWithRelationsResult.csv');
    }

    /**
     * @test
     */
    public function importsTouristInformationWithRelation(): void
    {
        $this->importDataSet(__DIR__ . '/Fixtures/Import/ImportsTouristInformationWithRelation.xml');

        $serverRequest = $this->getServerRequest();

        $extbaseBootstrap = $this->getContainer()->get(Bootstrap::class);
        $extbaseBootstrap->handleBackendRequest($serverRequest->reveal());

        $touristInformation = $this->getAllRecords('tx_thuecat_tourist_information');
        self::assertCount(1, $touristInformation);

        $this->assertCSVDataSet('EXT:thuecat/Tests/Functional/Fixtures/Import/ImportsTouristInformationWithRelationResult.csv');
    }

    /**
     * @test
     */
    public function importsBasedOnSyncScope(): void
    {
        $this->importDataSet(__DIR__ . '/Fixtures/Import/ImportsSyncScope.xml');

        $serverRequest = $this->getServerRequest();

        $extbaseBootstrap = $this->getContainer()->get(Bootstrap::class);
        $extbaseBootstrap->handleBackendRequest($serverRequest->reveal());

        $touristAttractions = $this->getAllRecords('tx_thuecat_tourist_attraction');
        self::assertCount(8, $touristAttractions);

        $this->assertCSVDataSet('EXT:thuecat/Tests/Functional/Fixtures/Import/ImportsSyncScopeResult.csv');
    }

    /**
     * @return ObjectProphecy<ServerRequestInterface>
     */
    private function getServerRequest(): ObjectProphecy
    {
        $route = $this->prophesize(Route::class);
        $route->getOption('moduleConfiguration')->willReturn([
            'access' => 'user,group',
            'labels' => 'LLL:EXT:thuecat/Resources/Private/Language/locallang_mod.xlf',
            'name' => 'site_ThuecatThuecat',
            'extensionName' => 'Thuecat',
            'routeTarget' => 'TYPO3\CMS\Extbase\Core\Bootstrap::handleBackendRequest',
            'iconIdentifier' => 'module-site_ThuecatThuecat',
        ]);
        $route->getOption('module')->willReturn(true);
        $route->getOption('moduleName')->willReturn('site_ThuecatThuecat');
        $route->getOption('access')->willReturn('user,group');
        $route->getOption('target')->willReturn('TYPO3\CMS\Extbase\Core\Bootstrap::handleBackendRequest');
        $route->getOption('_identifier')->willReturn('site_ThuecatThuecat');

        $serverRequest = $this->prophesize(ServerRequestInterface::class);
        $serverRequest->getAttribute('route')->willReturn($route->reveal());
        $serverRequest->getAttribute('routing')->willReturn(null);
        $serverRequest->getAttribute('normalizedParams')->willReturn(null);
        $serverRequest->getMethod()->willReturn('GET');
        $serverRequest->getParsedBody()->willReturn([]);
        $serverRequest->getQueryParams()->willReturn([
            'tx_thuecat_site_thuecatthuecat' => [
                'controller' => 'Backend\Import',
                'action' => 'import',
                'importConfiguration' => '1',
            ],
        ]);
        $GLOBALS['TYPO3_REQUEST'] = $serverRequest->reveal();

        // As long as extbase uri builder uses GeneralUtility::_GP
        $_GET['route'] = '/module/site/ThuecatThuecat';

        return $serverRequest;
    }
}
