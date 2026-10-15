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
use TYPO3\CMS\Core\Service\UpgradeWizardsService;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractBackendTestCase;
use WerkraumMedia\ThueCat\Typo3\Upgrades\DetailRecordSlugUpgradeWizard;

final class DetailRecordSlugUpgradeWizardTest extends AbstractBackendTestCase
{
    #[Test]
    public function fillsEmptySlugsPerRowAndLanguage(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Upgrades/DetailRecordsWithoutSlugs.csv');

        $this->get(DetailRecordSlugUpgradeWizard::class)->executeUpdate();

        self::assertSame(
            'gemeindebrunnen-weimar',
            $this->fetchRowByRemoteId('tx_thuecat_tourist_attraction', 'test:wizard-attraction')['slug']
        );
        $translations = $this->fetchTranslationsByParent(
            'tx_thuecat_tourist_attraction',
            $this->fetchUidByRemoteId('tx_thuecat_tourist_attraction', 'test:wizard-attraction')
        );
        self::assertArrayHasKey(1, $translations);
        self::assertSame('community-fountain-weimar', $translations[1]['slug']);
        self::assertSame(
            'rennsteig',
            $this->fetchRowByRemoteId('tx_thuecat_trail', 'test:wizard-trail')['slug']
        );
    }

    #[Test]
    public function leavesExistingSlugsUntouched(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Upgrades/DetailRecordsWithoutSlugs.csv');

        $this->get(DetailRecordSlugUpgradeWizard::class)->executeUpdate();

        self::assertSame(
            'eigener-slug',
            $this->fetchRowByRemoteId('tx_thuecat_tourist_attraction', 'test:wizard-own-slug')['slug']
        );
    }

    #[Test]
    public function replacesSlashesInTitlesWithTheFallbackCharacter(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Upgrades/DetailRecordsWithoutSlugs.csv');

        $this->get(DetailRecordSlugUpgradeWizard::class)->executeUpdate();

        self::assertSame(
            'wunderbar-gerber3',
            $this->fetchRowByRemoteId('tx_thuecat_tourist_attraction', 'test:wizard-slash-attraction')['slug']
        );
        self::assertSame(
            'weimar-erfurt',
            $this->fetchRowByRemoteId('tx_thuecat_trail', 'test:wizard-slash-trail')['slug']
        );
    }

    #[Test]
    public function reportsUpdateNecessaryWhileAnEmptySlugRemains(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Upgrades/DetailRecordsWithoutSlugs.csv');

        self::assertTrue($this->get(DetailRecordSlugUpgradeWizard::class)->updateNecessary());
    }

    #[Test]
    public function reportsNothingToDoWhenNoEmptySlugRemains(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Upgrades/DetailRecordsWithSlugs.csv');

        self::assertFalse($this->get(DetailRecordSlugUpgradeWizard::class)->updateNecessary());
    }

    #[Test]
    public function fillsEmptyTownAndCategorySlugs(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Upgrades/FilterRecordsWithoutSlugs.csv');

        $this->get(DetailRecordSlugUpgradeWizard::class)->executeUpdate();

        self::assertSame(
            'bad-langensalza',
            $this->fetchRowByRemoteId('tx_thuecat_town', 'test:wizard-town')['slug']
        );
        self::assertSame(
            'museen-ausstellungen',
            $this->fetchRowByRemoteId('sys_category', 'test:wizard-category')['slug']
        );
        self::assertSame(
            'eigene-burgen',
            $this->fetchRowByRemoteId('sys_category', 'test:wizard-own-category-slug')['slug']
        );
    }

    #[Test]
    public function reportsUpdateNecessaryWhileAFilterRecordSlugIsEmpty(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Upgrades/FilterRecordsWithoutSlugs.csv');

        self::assertTrue($this->get(DetailRecordSlugUpgradeWizard::class)->updateNecessary());
    }

    #[Test]
    public function staysOfferedAfterARunForSlugsEmptiedLater(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Upgrades/DetailRecordsWithoutSlugs.csv');
        $upgradeWizards = $this->get(UpgradeWizardsService::class);

        $upgradeWizards->executeWizard('thuecat_detailRecordSlugs', []);
        $this->getConnectionPool()->getConnectionForTable('tx_thuecat_town')->insert('tx_thuecat_town', [
            'pid' => 10,
            'remote_id' => 'test:wizard-town',
            'title' => 'Bad Langensalza',
            'slug' => '',
        ]);

        self::assertFalse($upgradeWizards->isWizardDone('thuecat_detailRecordSlugs'));
        self::assertTrue($this->get(DetailRecordSlugUpgradeWizard::class)->updateNecessary());
    }
}
