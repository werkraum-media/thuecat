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
use WerkraumMedia\ThueCat\Tests\Functional\AbstractFunctionalTestCase;
use WerkraumMedia\ThueCat\Typo3\Upgrades\EventRelationsToMmUpgradeWizard;

final class EventRelationsToMmUpgradeWizardTest extends AbstractFunctionalTestCase
{
    #[Test]
    public function movesAStoredListIntoMmInItsOrder(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Upgrades/EventRelationsAsLists.csv');

        $this->get(EventRelationsToMmUpgradeWizard::class)->executeUpdate();

        self::assertSame([12, 7, 30], $this->fetchMmRelatedUids('tx_thuecat_tourist_attraction', 700, 'hosts_events'));
        self::assertSame('3', $this->fetchChildren('tx_thuecat_tourist_attraction')[0]['hosts_events']);
    }

    #[Test]
    public function movesTheListOfATranslationToo(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Upgrades/EventRelationsAsLists.csv');

        $this->get(EventRelationsToMmUpgradeWizard::class)->executeUpdate();

        self::assertSame([12, 7, 30], $this->fetchMmRelatedUids('tx_thuecat_tourist_attraction', 701, 'hosts_events'));
        self::assertSame('3', $this->fetchTranslationsByParent('tx_thuecat_tourist_attraction', 700)[1]['hosts_events']);
    }

    /**
     * A list of one uid reads like a count, so only the missing MM rows tell it apart.
     */
    #[Test]
    public function movesAListOfASingleUid(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Upgrades/EventRelationsAsLists.csv');

        $this->get(EventRelationsToMmUpgradeWizard::class)->executeUpdate();

        self::assertSame([12], $this->fetchMmRelatedUids('tx_thuecat_town', 720, 'hosts_events'));
        self::assertSame('1', $this->fetchRowByRemoteId('tx_thuecat_town', 'test:wizard-town')['hosts_events']);
    }

    #[Test]
    public function movesTheListOfATouristInformation(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Upgrades/EventRelationsAsLists.csv');

        $this->get(EventRelationsToMmUpgradeWizard::class)->executeUpdate();

        self::assertSame([30, 7], $this->fetchMmRelatedUids('tx_thuecat_tourist_information', 730, 'hosts_events'));
        self::assertSame('2', $this->fetchRowByRemoteId('tx_thuecat_tourist_information', 'test:wizard-tourist-information')['hosts_events']);
    }

    #[Test]
    public function keepsBothOrganisationRolesApart(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Upgrades/EventRelationsAsLists.csv');

        $this->get(EventRelationsToMmUpgradeWizard::class)->executeUpdate();

        self::assertSame([12], $this->fetchMmRelatedUids('tx_thuecat_organisation', 710, 'hosts_events'));
        self::assertSame([7, 30], $this->fetchMmRelatedUids('tx_thuecat_organisation', 710, 'manages_event'));
    }

    #[Test]
    public function aSecondRunCreatesNoDuplicate(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Upgrades/EventRelationsAsLists.csv');
        $wizard = $this->get(EventRelationsToMmUpgradeWizard::class);

        $wizard->executeUpdate();
        $wizard->executeUpdate();

        self::assertSame([12, 7, 30], $this->fetchMmRelatedUids('tx_thuecat_tourist_attraction', 700, 'hosts_events'));
        self::assertSame([12], $this->fetchMmRelatedUids('tx_thuecat_town', 720, 'hosts_events'));
    }

    #[Test]
    public function reportsUpdateNecessaryWhileAListRemains(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Upgrades/EventRelationsAsLists.csv');

        self::assertTrue($this->get(EventRelationsToMmUpgradeWizard::class)->updateNecessary());
    }

    #[Test]
    public function reportsNothingToDoOnceEveryListIsMigrated(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Upgrades/EventRelationsMigrated.csv');

        self::assertFalse($this->get(EventRelationsToMmUpgradeWizard::class)->updateNecessary());
    }
}
