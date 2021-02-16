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
 * @covers WerkraumMedia\ThueCat\Controller\Backend\AbstractController
 * @covers WerkraumMedia\ThueCat\Controller\Backend\ImportController
 * @covers WerkraumMedia\ThueCat\DependencyInjection\ConverterPass
 * @covers WerkraumMedia\ThueCat\DependencyInjection\UrlProvidersPass
 * @covers WerkraumMedia\ThueCat\Domain\Import\Importer\SaveData
 * @covers WerkraumMedia\ThueCat\Domain\Repository\Backend\ImportLogRepository
 * @covers WerkraumMedia\ThueCat\Domain\Repository\Backend\OrganisationRepository
 * @covers WerkraumMedia\ThueCat\Domain\Repository\Backend\TownRepository
 * @covers WerkraumMedia\ThueCat\Extension
 * @covers WerkraumMedia\ThueCat\Typo3Wrapper\TranslationService
 * @covers WerkraumMedia\ThueCat\View\Backend\Menu
 *
 * @uses WerkraumMedia\ThueCat\Domain\Import\Converter\Organisation
 * @uses WerkraumMedia\ThueCat\Domain\Import\Converter\Registry
 * @uses WerkraumMedia\ThueCat\Domain\Import\Converter\TouristAttraction
 * @uses WerkraumMedia\ThueCat\Domain\Import\Converter\TouristInformation
 * @uses WerkraumMedia\ThueCat\Domain\Import\Converter\Town
 * @uses WerkraumMedia\ThueCat\Domain\Import\Importer
 * @uses WerkraumMedia\ThueCat\Domain\Import\Importer\FetchData
 * @uses WerkraumMedia\ThueCat\Domain\Import\JsonLD\Parser
 * @uses WerkraumMedia\ThueCat\Domain\Import\JsonLD\Parser\Address
 * @uses WerkraumMedia\ThueCat\Domain\Import\JsonLD\Parser\Media
 * @uses WerkraumMedia\ThueCat\Domain\Import\JsonLD\Parser\OpeningHours
 * @uses WerkraumMedia\ThueCat\Domain\Import\JsonLD\Parser\OpeningHours
 * @uses WerkraumMedia\ThueCat\Domain\Import\Model\EntityCollection
 * @uses WerkraumMedia\ThueCat\Domain\Import\Model\GenericEntity
 * @uses WerkraumMedia\ThueCat\Domain\Import\RequestFactory
 * @uses WerkraumMedia\ThueCat\Domain\Import\UrlProvider\Registry
 * @uses WerkraumMedia\ThueCat\Domain\Import\UrlProvider\StaticUrlProvider
 * @uses WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfiguration
 * @uses WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLog
 * @uses WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLogEntry
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
        self::assertSame('1', $importLogEntries[0]['insertion']);
        self::assertSame('[]', $importLogEntries[0]['errors']);
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
    public function importsTouristAttractionsWithRelations(): void
    {
        $this->importDataSet(__DIR__ . '/Fixtures/Import/ImportsTouristAttractionsWithRelations.xml');

        $serverRequest = $this->getServerRequest();

        $extbaseBootstrap = $this->getContainer()->get(Bootstrap::class);
        $extbaseBootstrap->handleBackendRequest($serverRequest->reveal());

        $touristAttractions = $this->getAllRecords('tx_thuecat_tourist_attraction');
        self::assertCount(3, $touristAttractions);

        self::assertSame('https://thuecat.org/resources/835224016581-dara', $touristAttractions[0]['remote_id']);
        self::assertSame('Dom St. Marien', $touristAttractions[0]['title']);
        self::assertSame('[{"opens":"09:30:00","closes":"18:00:00","from":{"date":"2021-05-01 00:00:00.000000","timezone_type":3,"timezone":"UTC"},"through":{"date":"2021-10-31 00:00:00.000000","timezone_type":3,"timezone":"UTC"},"daysOfWeek":["Friday","Monday","Saturday","Thursday","Tuesday","Wednesday"]},{"opens":"13:00:00","closes":"18:00:00","from":{"date":"2021-05-01 00:00:00.000000","timezone_type":3,"timezone":"UTC"},"through":{"date":"2021-10-31 00:00:00.000000","timezone_type":3,"timezone":"UTC"},"daysOfWeek":["Sunday"]},{"opens":"09:30:00","closes":"17:00:00","from":{"date":"2021-11-01 00:00:00.000000","timezone_type":3,"timezone":"UTC"},"through":{"date":"2022-04-30 00:00:00.000000","timezone_type":3,"timezone":"UTC"},"daysOfWeek":["Friday","Monday","Saturday","Thursday","Tuesday","Wednesday"]},{"opens":"13:00:00","closes":"17:00:00","from":{"date":"2021-11-01 00:00:00.000000","timezone_type":3,"timezone":"UTC"},"through":{"date":"2022-04-30 00:00:00.000000","timezone_type":3,"timezone":"UTC"},"daysOfWeek":["Sunday"]}]', $touristAttractions[0]['opening_hours']);
        self::assertSame('{"street":"Domstufen 1","zip":"99084","city":"Erfurt","email":"dominformation@domberg-erfurt.de","phone":"+49 361 6461265","fax":"","geo":{"latitude":50.975955358589545,"longitude":11.023667024961856}}', $touristAttractions[0]['address']);
        self::assertSame('[{"mainImage":true,"type":"image","title":"Erfurt-Dom und Severikirche-beleuchtet.jpg","description":"","url":"https:\/\/cms.thuecat.org\/o\/adaptive-media\/image\/5159216\/Preview-1280x0\/image","copyrightYear":2016,"license":{"type":"https:\/\/creativecommons.org\/licenses\/by\/4.0\/","author":""}},{"mainImage":false,"type":"image","title":"Erfurt-Dom-und-Severikirche.jpg","description":"Sicht auf Dom St. Marien, St. Severikirche sowie die davor liegenden Klostergeb\u00e4ude und einem Ausschnitt des Biergartens umgeben von einem d\u00e4mmerungsverf\u00e4rten Himmel","url":"https:\/\/cms.thuecat.org\/o\/adaptive-media\/image\/5159186\/Preview-1280x0\/image","copyrightYear":2020,"license":{"type":"https:\/\/creativecommons.org\/licenses\/by\/4.0\/","author":""}},{"mainImage":false,"type":"image","title":"Erfurt-Dom und Severikirche-beleuchtet.jpg","description":"","url":"https:\/\/cms.thuecat.org\/o\/adaptive-media\/image\/5159216\/Preview-1280x0\/image","copyrightYear":2016,"license":{"type":"https:\/\/creativecommons.org\/licenses\/by\/4.0\/","author":""}}]', $touristAttractions[0]['media']);
        self::assertSame('1', $touristAttractions[0]['managed_by']);
        self::assertSame('1', $touristAttractions[0]['town']);
        self::assertSame('1', $touristAttractions[0]['uid']);

        self::assertSame('https://thuecat.org/resources/165868194223-zmqf', $touristAttractions[1]['remote_id']);
        self::assertSame('Alte Synagoge', $touristAttractions[1]['title']);
        self::assertSame('[{"opens":"10:00:00","closes":"18:00:00","from":{"date":"2021-03-01 00:00:00.000000","timezone_type":3,"timezone":"UTC"},"through":{"date":"2021-12-31 00:00:00.000000","timezone_type":3,"timezone":"UTC"},"daysOfWeek":["Friday","Saturday","Sunday","Thursday","Tuesday","Wednesday"]}]', $touristAttractions[1]['opening_hours']);
        self::assertSame('{"street":"Waagegasse 8","zip":"99084","city":"Erfurt","email":"altesynagoge@erfurt.de","phone":"+49 361 6551520","fax":"+49 361 6551669","geo":{"latitude":50.978765,"longitude":11.029133}}', $touristAttractions[1]['address']);
        self::assertSame('[{"mainImage":true,"type":"image","title":"Erfurt-Alte Synagoge","description":"Frontaler Blick auf die Hausfront\/Hausfassade im Innenhof mit Zugang \u00fcber die Waagegasse","url":"https:\/\/cms.thuecat.org\/o\/adaptive-media\/image\/5099196\/Preview-1280x0\/image","copyrightYear":2009,"license":{"type":"https:\/\/creativecommons.org\/licenses\/by\/4.0\/","author":"F:\\\\Bilddatenbank\\\\Museen und Ausstellungen\\\\Alte Synagoge"}},{"mainImage":false,"type":"image","title":"Erfurt-Alte Synagoge","description":"Frontaler Blick auf die Hausfront\/Hausfassade im Innenhof mit Zugang \u00fcber die Waagegasse","url":"https:\/\/cms.thuecat.org\/o\/adaptive-media\/image\/5099196\/Preview-1280x0\/image","copyrightYear":2009,"license":{"type":"https:\/\/creativecommons.org\/licenses\/by\/4.0\/","author":"F:\\\\Bilddatenbank\\\\Museen und Ausstellungen\\\\Alte Synagoge"}}]', $touristAttractions[1]['media']);
        self::assertSame('1', $touristAttractions[1]['managed_by']);
        self::assertSame('1', $touristAttractions[1]['town']);
        self::assertSame('2', $touristAttractions[1]['uid']);

        self::assertSame('https://thuecat.org/resources/215230952334-yyno', $touristAttractions[2]['remote_id']);
        self::assertSame('Krämerbrücke', $touristAttractions[2]['title']);
        self::assertSame('[]', $touristAttractions[2]['opening_hours']);
        self::assertSame('{"street":"Benediktsplatz 1","zip":"99084","city":"Erfurt","email":"service@erfurt-tourismus.de","phone":"+49 361 66 400","fax":"","geo":{"latitude":50.978772,"longitude":11.031622}}', $touristAttractions[2]['address']);
        self::assertSame('[{"mainImage":true,"type":"image","title":"Erfurt-Kraemerbruecke-11.jpg","description":"Kr\u00e4merbr\u00fccke in Erfurt","url":"https:\/\/cms.thuecat.org\/o\/adaptive-media\/image\/134362\/Preview-1280x0\/image","copyrightYear":2019,"license":{"type":"https:\/\/creativecommons.org\/publicdomain\/zero\/1.0\/deed.de","author":"https:\/\/home.ttgnet.de\/ttg\/projekte\/10006\/90136\/Projektdokumente\/Vergabe%20Rahmenvertrag%20Fotoproduktion"}},{"mainImage":false,"type":"image","title":"Erfurt-Kraemerbruecke.jpg","description":"Kr\u00e4merbr\u00fccke in Erfurt","url":"https:\/\/cms.thuecat.org\/o\/adaptive-media\/image\/134288\/Preview-1280x0\/image","copyrightYear":2019,"license":{"type":"https:\/\/creativecommons.org\/publicdomain\/zero\/1.0\/deed.de","author":"https:\/\/home.ttgnet.de\/ttg\/projekte\/10006\/90136\/Projektdokumente\/Vergabe%20Rahmenvertrag%20Fotoproduktion"}},{"mainImage":false,"type":"image","title":"Erfurt-Kraemerbruecke-11.jpg","description":"Kr\u00e4merbr\u00fccke in Erfurt","url":"https:\/\/cms.thuecat.org\/o\/adaptive-media\/image\/134362\/Preview-1280x0\/image","copyrightYear":2019,"license":{"type":"https:\/\/creativecommons.org\/publicdomain\/zero\/1.0\/deed.de","author":"https:\/\/home.ttgnet.de\/ttg\/projekte\/10006\/90136\/Projektdokumente\/Vergabe%20Rahmenvertrag%20Fotoproduktion"}},{"mainImage":false,"type":"image","title":"Erfurt-Kraemerbruecke-13.jpg","description":"Ansicht der Kr\u00e4merbr\u00fccke, Erfurt","url":"https:\/\/cms.thuecat.org\/o\/adaptive-media\/image\/652340\/Preview-1280x0\/image","copyrightYear":2019,"license":{"type":"https:\/\/creativecommons.org\/publicdomain\/zero\/1.0\/deed.de","author":"https:\/\/home.ttgnet.de\/ttg\/projekte\/10006\/90136\/Projektdokumente\/Vergabe%20Rahmenvertrag%20Fotoproduktion"}}]', $touristAttractions[2]['media']);
        self::assertSame('1', $touristAttractions[2]['managed_by']);
        self::assertSame('1', $touristAttractions[2]['town']);
        self::assertSame('3', $touristAttractions[2]['uid']);
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
