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

namespace WerkraumMedia\ThueCat\Tests\Functional\Removal;

use PHPUnit\Framework\Attributes\Test;

final class RelationRemovalTest extends AbstractRemovalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/Roots.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Relations.csv');
    }

    #[Test]
    public function anOrganisationOnlyTheRemovedRootUsedIsDeleted(): void
    {
        $this->removeRoot('tx_thuecat_tourist_attraction', 100);

        $this->assertTrue($this->isDeleted('tx_thuecat_organisation', 100));
    }

    #[Test]
    public function anOrganisationAKeptTrailUsesIsKept(): void
    {
        $this->removeRoot('tx_thuecat_tourist_attraction', 100);

        $this->assertFalse($this->isDeleted('tx_thuecat_organisation', 101));
    }

    #[Test]
    public function aTownLinkedFromAContentElementIsKept(): void
    {
        $this->removeRoot('tx_thuecat_tourist_attraction', 101);

        $this->assertFalse($this->isDeleted('tx_thuecat_town', 101));
    }

    #[Test]
    public function aReferencedPlaceIsKept(): void
    {
        $this->removeRoot('tx_thuecat_tourist_attraction', 100);

        $this->assertFalse($this->isDeleted('tx_thuecat_place', 2));
    }

    #[Test]
    public function aCategoryIsKept(): void
    {
        $this->removeRoot('tx_thuecat_tourist_attraction', 100);

        $this->assertFalse($this->isDeleted('sys_category', 100));
    }

    #[Test]
    public function anUnreferencedOrganisationInTheSameFolderIsKept(): void
    {
        $this->removeRoot('tx_thuecat_tourist_attraction', 100);

        $this->assertFalse($this->isDeleted('tx_thuecat_organisation', 103));
    }

    #[Test]
    public function aRelationReachedThroughADeletedRelationIsDeletedWhenUnused(): void
    {
        $this->removeRoot('tx_thuecat_tourist_attraction', 100);

        $this->assertTrue($this->isDeleted('tx_thuecat_town', 100));
        $this->assertTrue($this->isDeleted('tx_thuecat_organisation', 102));
    }

    #[Test]
    public function aRelationsOwnTranslationDoesNotKeepIt(): void
    {
        $this->removeRoot('tx_thuecat_tourist_attraction', 102);

        $this->assertTrue($this->isDeleted('tx_thuecat_organisation', 105));
    }

    #[Test]
    public function anEventsOrganizerAndLocationOnlyItUsedAreDeleted(): void
    {
        $this->removeRoot('tx_events_domain_model_event', 100);

        $this->assertTrue($this->isDeleted('tx_events_domain_model_organizer', 100));
        $this->assertTrue($this->isDeleted('tx_events_domain_model_location', 100));
    }

    #[Test]
    public function aRefusedRelationIsKeptAndLoggedOnTheJob(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Editor.csv');

        $job = $this->removeRoot('tx_thuecat_tourist_attraction', 102, false, 2);

        $this->assertTrue($this->isDeleted('tx_thuecat_tourist_attraction', 102));
        $this->assertFalse($this->isDeleted('tx_thuecat_organisation', 105));
        $this->assertEquals(0, $this->jobRow($job)['removed_relations']);
        $errorLog = $this->jobRow($job)['error_log'];
        $this->assertIsString($errorLog);
        $this->assertMatchesRegularExpression('/^tx_thuecat_tourist_attraction:102 tx_thuecat_organisation:105 \S/', $errorLog);
    }

    #[Test]
    public function theJobCountsTheRemovedRelations(): void
    {
        $job = $this->removeRoot('tx_thuecat_tourist_attraction', 100);

        $this->assertEquals(3, $this->jobRow($job)['removed_relations']);
        $this->assertEquals(0, $this->jobRow($job)['errors']);
    }
}
