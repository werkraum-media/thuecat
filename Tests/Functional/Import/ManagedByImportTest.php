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

namespace WerkraumMedia\ThueCat\Tests\Functional\Import;

use PHPUnit\Framework\Attributes\Test;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractImportTestCase;

// A translation relates to the default-language organisation; the overlay resolves its language.
final class ManagedByImportTest extends AbstractImportTestCase
{
    private const ATTRACTION = 'https://thuecat.org/resources/attraction-managed-by-translated-organisation';

    private const ORGANISATION = 'https://thuecat.org/resources/organisation-translated';

    private const STORAGE_PID = 10;

    // The source delivers German and English only. Localizing the organisation must not
    // localize the records it manages.
    #[Test]
    public function translatingTheOrganisationCreatesNoAttractionTranslation(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsTouristAttractionManagedByTranslatedOrganisation.php');
        $this->expectFetch('attraction-managed-by-translated-organisation.json');
        $this->expectFetch('organisation-translated.json');

        $this->importConfiguration(1);

        self::assertSame([0, 1, 2], $this->fetchLanguagesByRemoteId('tx_thuecat_organisation', self::ORGANISATION, self::STORAGE_PID));
        self::assertSame([0, 1], $this->fetchLanguagesByRemoteId('tx_thuecat_tourist_attraction', self::ATTRACTION, self::STORAGE_PID));
    }

    #[Test]
    public function translatedAttractionIsManagedByTheDefaultOrganisation(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsTouristAttractionManagedByTranslatedOrganisation.php');
        $this->expectFetch('attraction-managed-by-translated-organisation.json');
        $this->expectFetch('organisation-translated.json');

        $this->importConfiguration(1);

        $organisationUid = $this->fetchUidByRemoteId('tx_thuecat_organisation', self::ORGANISATION);
        self::assertArrayHasKey(1, $this->fetchTranslationsByParent('tx_thuecat_organisation', $organisationUid));

        $attractionTranslations = $this->fetchTranslationsByParent(
            'tx_thuecat_tourist_attraction',
            $this->fetchUidByRemoteId('tx_thuecat_tourist_attraction', self::ATTRACTION)
        );
        self::assertArrayHasKey(1, $attractionTranslations);

        self::assertSame($organisationUid, $this->fetchRowByRemoteId('tx_thuecat_tourist_attraction', self::ATTRACTION)['managed_by']);
        self::assertSame($organisationUid, $attractionTranslations[1]['managed_by']);
    }

    #[Test]
    public function reimportKeepsTranslatedAttractionManagedByTheDefaultOrganisation(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsTouristAttractionManagedByTranslatedOrganisation.php');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Import/ExistingAttractionManagedByTranslatedOrganisation.csv');
        $this->expectFetch('attraction-managed-by-translated-organisation.json');
        $this->expectFetch('organisation-translated.json');

        $this->importConfiguration(1);

        $attractionTranslations = $this->fetchTranslationsByParent('tx_thuecat_tourist_attraction', 1);
        self::assertArrayHasKey(1, $attractionTranslations);
        self::assertSame(1, $attractionTranslations[1]['managed_by']);
    }
}
