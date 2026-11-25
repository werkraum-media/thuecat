<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS extension "thuecat".
 *
 * Copyright (C) werkraum-media <https://werkraum-media.de/>
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * For the full license text, see the LICENSE file distributed with this
 * extension.
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 */

namespace WerkraumMedia\ThueCat\Tests\Functional\Typo3\Upgrades;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Output\BufferedOutput;
use TYPO3\CMS\Core\Service\UpgradeWizardsService;
use TYPO3\CMS\Core\Upgrades\DatabaseUpdatedPrerequisite;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractFunctionalTestCase;
use WerkraumMedia\ThueCat\Typo3\Upgrades\ImportConfigurationFlexFormMigration;

final class ImportConfigurationFlexFormMigrationTest extends AbstractFunctionalTestCase
{
    private ImportConfigurationFlexFormMigration $wizard;

    private BufferedOutput $output;

    protected function setUp(): void
    {
        parent::setUp();
        $this->output = new BufferedOutput();
        $this->wizard = $this->get(ImportConfigurationFlexFormMigration::class);
        $this->wizard->setOutput($this->output);
    }

    #[Test]
    public function migratesEveryTypeAsStoredByThuecat7(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Upgrades/ImportConfigurationFlexForms.csv');

        $this->assertTrue($this->wizard->executeUpdate());

        $this->assertCSVDataSet(__DIR__ . '/../../Assertions/Upgrades/ImportConfigurationFlexForms.csv');
    }

    #[Test]
    public function reportsARunBudgetAboveTheMessageBudgetLimit(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Upgrades/ImportConfigurationFlexForms.csv');

        $this->wizard->executeUpdate();

        $this->assertStringContainsString(
            'Import configuration 5: the run budget of 3600 s exceeds 300 s and was not carried over.',
            $this->output->fetch(),
        );
    }

    #[Test]
    public function dropsTheApiDomainAndReportsAnyHostOtherThanTheDefault(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Upgrades/ImportConfigurationFlexForms.csv');

        $this->wizard->executeUpdate();

        $report = $this->output->fetch();
        $this->assertStringContainsString(
            'Import configuration 1: the API domain https://cdb.int.thuecat.org is dropped, hosts follow the configuration type.',
            $report,
        );
        $this->assertStringNotContainsString('Import configuration 2: the API domain', $report, 'An empty domain is not reported.');
        $this->assertStringNotContainsString('Import configuration 3: the API domain', $report, 'The default domain is not reported.');
    }

    #[Test]
    public function keepsTheXmlOfAConfigurationWithAnUnknownKeyAndReportsIt(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Upgrades/ImportConfigurationFlexFormWithUnknownKey.csv');

        $this->wizard->executeUpdate();

        $row = $this->getAllRecords('tx_thuecat_import_configuration', true)[6];
        $this->assertIsArray($row);
        $this->assertIsString($row['configuration']);
        $this->assertStringContainsString('<field index="unknownSetting">', $row['configuration']);
        $this->assertSame('', $row['sync_channel_id'], 'Nothing of a configuration with an unknown key is written.');
        $this->assertStringContainsString(
            'Import configuration 6: unknown setting "unknownSetting", its settings were not migrated.',
            $this->output->fetch(),
        );
    }

    #[Test]
    public function migratesTheConfigurationsFollowingOneWithAnUnknownKey(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Upgrades/ImportConfigurationFlexFormWithUnknownKey.csv');

        $this->wizard->executeUpdate();

        $this->assertSame(['test-known'], $this->fetchColumnMatching('tx_thuecat_import_configuration', 'sync_channel_id', ['uid' => 7]));
    }

    #[Test]
    public function isOfferedOnceTheDatabaseSchemaIsUpdated(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Upgrades/ImportConfigurationFlexForms.csv');

        $information = $this->get(UpgradeWizardsService::class)->getWizardInformationByIdentifier('thuecat_importConfigurationFlexFormMigration');

        $this->assertSame('ThueCat: Move import configuration settings out of the FlexForm', $information['title']);
        $this->assertSame(
            'Writes the settings of every import configuration, hidden and deleted ones included, to their own fields and '
            . 'its URLs to URL records. A configuration holding a setting this wizard does not know is reported and left '
            . 'as it is.',
            $information['explanation']
        );
        $this->assertTrue($information['shouldRenderWizard']);
        $this->assertSame([DatabaseUpdatedPrerequisite::class], $this->wizard->getPrerequisites());
    }

    #[Test]
    public function aSecondRunHasNothingToDo(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Upgrades/ImportConfigurationFlexForms.csv');
        $this->assertTrue($this->wizard->updateNecessary());

        $this->wizard->executeUpdate();
        $this->wizard->executeUpdate();

        $this->assertFalse($this->wizard->updateNecessary());
        $this->assertCSVDataSet(__DIR__ . '/../../Assertions/Upgrades/ImportConfigurationFlexForms.csv');
    }
}
