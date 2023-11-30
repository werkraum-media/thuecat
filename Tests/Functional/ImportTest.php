<?php

declare(strict_types=1);

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

use PHPUnit\Framework\Attributes\Test;
use WerkraumMedia\ThueCat\Domain\Import\ImportConfiguration;
use WerkraumMedia\ThueCat\Domain\Import\Importer;
use WerkraumMedia\ThueCat\Domain\Repository\Backend\ImportConfigurationRepository;

class ImportTest extends AbstractImportTestCase
{
    #[Test]
    public function importsFreshOrganization(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsFreshOrganization.php');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/018132452787-ngbe.json');

        $this->importConfiguration();

        $this->assertPHPDataSet(__DIR__ . '/Assertions/Import/ImportsFreshOrganization.php');
    }

    #[Test]
    public function updatesExistingOrganization(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/UpdatesExistingOrganization.php');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/018132452787-ngbe.json');

        $this->importConfiguration();

        $organisations = $this->getAllRecords('tx_thuecat_organisation');
        self::assertCount(1, $organisations);
        self::assertSame('https://thuecat.org/resources/018132452787-ngbe', $organisations[0]['remote_id']);
        self::assertSame('Erfurt Tourismus und Marketing GmbH', $organisations[0]['title']);
        self::assertSame(1, (int)$organisations[0]['uid']);

        $importLogs = $this->getAllRecords('tx_thuecat_import_log');
        self::assertCount(1, $importLogs);
        self::assertSame(1, (int)$importLogs[0]['configuration']);
        self::assertSame(1, (int)$importLogs[0]['uid']);

        $importLogEntries = $this->getAllRecords('tx_thuecat_import_log_entry');
        self::assertCount(1, $importLogEntries);
        self::assertSame(1, (int)$importLogEntries[0]['import_log']);
        self::assertSame(1, (int)$importLogEntries[0]['record_uid']);
        self::assertSame('tx_thuecat_organisation', $importLogEntries[0]['table_name']);
        self::assertSame(0, (int)$importLogEntries[0]['insertion']);
        self::assertSame('[]', $importLogEntries[0]['errors']);
    }

    #[Test]
    public function importsTown(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsTown.php');
        // TODO: Check why we request both twice.
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/043064193523-jcyt.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/018132452787-ngbe.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/043064193523-jcyt.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/018132452787-ngbe.json');

        $this->importConfiguration();

        $this->assertPHPDataSet(__DIR__ . '/Assertions/Import/ImportsTown.php');
    }

    #[Test]
    public function importsTownWithRelation(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsTownWithRelation.php');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/043064193523-jcyt.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/018132452787-ngbe.json');

        $this->importConfiguration();

        $this->assertPHPDataSet(__DIR__ . '/Assertions/Import/ImportsTownWithRelation.php');
    }

    #[Test]
    public function importsTouristAttractionsWithRelations(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsTouristAttractionsWithRelations.php');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/835224016581-dara.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/018132452787-ngbe.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/043064193523-jcyt.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/573211638937-gmqb.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/508431710173-wwne.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/dms_5159216.json');
        GuzzleClientFaker::appendNotFoundResponse();
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/dms_5159186.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/396420044896-drzt.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/dms_6486108.json');
        GuzzleClientFaker::appendNotFoundResponse();
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/165868194223-zmqf.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/497839263245-edbm.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/dms_5099196.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/e_23bec7f80c864c358da033dd75328f27-rfa.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/215230952334-yyno.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/052821473718-oxfq.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/dms_134362.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/dms_134288.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/dms_652340.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/440055527204-ocar.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/dms_5197164.json');

        $this->importConfiguration();

        $this->assertPHPDataSet(__DIR__ . '/Assertions/Import/ImportsTouristAttractionsWithRelations.php');
    }

    #[Test]
    public function importsTouristAttractionsWithFilteredOpeningHours(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsTouristAttractionWithFilteredOpeningHours.php');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/opening-hours-to-filter.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/018132452787-ngbe.json');

        $this->importConfiguration();

        $this->assertPHPDataSet(__DIR__ . '/Assertions/Import/ImportsTouristAttractionsWithFilteredOpeningHours.php');
    }

    #[Test]
    public function importsTouristAttractionsWithSpecialOpeningHours(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsTouristAttractionWithSpecialOpeningHours.php');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/special-opening-hours.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/018132452787-ngbe.json');

        $this->importConfiguration();

        $this->assertPHPDataSet(__DIR__ . '/Assertions/Import/ImportsTouristAttractionsWithSpecialOpeningHours.php');
    }

    #[Test]
    public function importsTouristInformationWithRelation(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsTouristInformationWithRelation.php');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/333039283321-xxwg.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/018132452787-ngbe.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/043064193523-jcyt.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/573211638937-gmqb.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/356133173991-cryw.json');

        $this->importConfiguration();

        $this->assertPHPDataSet(__DIR__ . '/Assertions/Import/ImportsTouristInformationWithRelation.php');
    }

    #[Test]
    public function importsBasedOnSyncScope(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsSyncScope.php');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/cdb.thuecat.org/api/ext-sync/get-updated-nodes/dd4615dc-58a6-4648-a7ce-4950293a06db.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/835224016581-dara.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/018132452787-ngbe.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/043064193523-jcyt.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/573211638937-gmqb.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/508431710173-wwne.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/dms_5159216.json');
        GuzzleClientFaker::appendNotFoundResponse();
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/dms_5159186.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/396420044896-drzt.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/dms_6486108.json');
        GuzzleClientFaker::appendNotFoundResponse();
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/165868194223-zmqf.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/497839263245-edbm.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/dms_5099196.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/e_23bec7f80c864c358da033dd75328f27-rfa.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/215230952334-yyno.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/052821473718-oxfq.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/dms_134362.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/dms_134288.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/dms_652340.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/440055527204-ocar.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/dms_5197164.json');

        $this->importConfiguration();

        $this->assertPHPDataSet(__DIR__ . '/Assertions/Import/ImportsSyncScope.php');
    }

    #[Test]
    public function importsBasedOnContainsPlace(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsContainsPlace.php');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/043064193523-contains.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/835224016581-dara.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/018132452787-ngbe.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/043064193523-jcyt.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/573211638937-gmqb.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/508431710173-wwne.json');
        for ($i = 1; $i <= 4; $i++) {
            GuzzleClientFaker::appendNotFoundResponse();
        }
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/396420044896-drzt.json');
        for ($i = 1; $i <= 10; $i++) {
            GuzzleClientFaker::appendNotFoundResponse();
        }
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/165868194223-zmqf.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/497839263245-edbm.json');
        for ($i = 1; $i <= 2; $i++) {
            GuzzleClientFaker::appendNotFoundResponse();
        }
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/e_23bec7f80c864c358da033dd75328f27-rfa.json');
        for ($i = 1; $i <= 4; $i++) {
            GuzzleClientFaker::appendNotFoundResponse();
        }
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/215230952334-yyno.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/052821473718-oxfq.json');
        for ($i = 1; $i <= 4; $i++) {
            GuzzleClientFaker::appendNotFoundResponse();
        }
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/440055527204-ocar.json');
        for ($i = 1; $i <= 14; $i++) {
            GuzzleClientFaker::appendNotFoundResponse();
        }

        $this->importConfiguration();

        $this->assertPHPDataSet(__DIR__ . '/Assertions/Import/ImportsContainsPlace.php');
    }

    #[Test]
    public function importsFollowingRecordsInCaseOfAnMappingException(): void
    {
        $this->expectErrors = true;
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsFollowingRecordsInCaseOfAnMappingException.php');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/mapping-exception.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/165868194223-zmqf.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/018132452787-ngbe.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/043064193523-jcyt.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/573211638937-gmqb.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/497839263245-edbm.json');
        for ($i = 1; $i <= 9; $i++) {
            GuzzleClientFaker::appendNotFoundResponse();
        }

        $this->importConfiguration();

        if (version_compare(PHP_VERSION, '8.1.0', '<')) {
            $this->assertPHPDataSet(__DIR__ . '/Assertions/Import/ImportsFollowingRecordsInCaseOfAnMappingExceptionOldPhp.php');
        } else {
            $this->assertPHPDataSet(__DIR__ . '/Assertions/Import/ImportsFollowingRecordsInCaseOfAnMappingException.php');
        }
    }

    #[Test]
    public function importWithMultipleReferencesToSameObject(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportWithMultipleReferencesToSameObject.php');

        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/835224016581-dara.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/018132452787-ngbe.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/043064193523-jcyt.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/573211638937-gmqb.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/508431710173-wwne.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/dms_5159216.json');
        GuzzleClientFaker::appendNotFoundResponse();
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/dms_5159186.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/396420044896-drzt.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/dms_6486108.json');
        GuzzleClientFaker::appendNotFoundResponse();
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/165868194223-zmqf.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/497839263245-edbm.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/dms_5099196.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/e_23bec7f80c864c358da033dd75328f27-rfa.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/215230952334-yyno.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/052821473718-oxfq.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/dms_134362.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/dms_134288.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/dms_652340.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/440055527204-ocar.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/dms_5197164.json');

        $this->importConfiguration();

        $this->assertPHPDataSet(__DIR__ . '/Assertions/Import/ImportWithMultipleReferencesToSameObject.php');
    }

    #[Test]
    public function importsTouristAttractionWithMedia(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsTouristAttractionWithMedia.php');

        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/attraction-with-media.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/018132452787-ngbe.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/image-with-foreign-author.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/author-with-names.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/image-with-author-string.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/image-with-license-author.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/image-with-author-and-license-author.json');

        $this->importConfiguration();

        $this->assertPHPDataSet(__DIR__ . '/Assertions/Import/ImportsTouristAttractionWithMedia.php');
    }

    #[Test]
    public function importsTouristAttractionWithAccessibilitySpecification(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsTouristAttractionWithAccessibilitySpecification.php');

        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/attraction-with-accessibility-specification.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/018132452787-ngbe.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/e_331baf4eeda4453db920dde62f7e6edc-rfa-accessibility-specification.json');

        $this->importConfiguration();

        $this->assertPHPDataSet(__DIR__ . '/Assertions/Import/ImportsTouristAttractionWithAccessibilitySpecification.php');
        $records = $this->getAllRecords('tx_thuecat_tourist_attraction');
        self::assertStringEqualsFile(__DIR__ . '/Fixtures/Import/ImportsTouristAttractionWithAccessibilitySpecificationGerman.txt', $records[0]['accessibility_specification'] . PHP_EOL);
        self::assertStringEqualsFile(__DIR__ . '/Fixtures/Import/ImportsTouristAttractionWithAccessibilitySpecificationEnglish.txt', $records[1]['accessibility_specification'] . PHP_EOL);
    }

    private function importConfiguration(): void
    {
        $configuration = $this->get(ImportConfigurationRepository::class)->findByUid(1);
        self::assertInstanceOf(ImportConfiguration::class, $configuration);
        $this->get(Importer::class)->importConfiguration($configuration);
    }
}
