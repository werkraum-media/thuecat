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

/**
 * A translation relates to the default-language record, and the overlay resolves its language.
 * Seed data points translations at translated records; the import resets them to the default record.
 */
final class TranslationRelationImportTest extends AbstractImportTestCase
{
    #[Test]
    public function reimportPointsTranslatedTouristInformationAtDefaultRecords(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsTouristInformationRelatedToTranslatedRecords.php');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Import/ExistingTownAndTouristInformationRelatedToTranslatedRecords.csv');
        $this->expectFetch('tourist-information-related-to-translated-records.json');
        $this->expectFetch('town-translated.json');
        $this->expectFetch('organisation-translated.json');

        $this->importConfiguration(1);

        $translations = $this->fetchTranslationsByParent('tx_thuecat_tourist_information', 1);
        self::assertArrayHasKey(1, $translations);
        self::assertSame(1, $translations[1]['managed_by']);
        self::assertSame('1', $translations[1]['town']);
        self::assertSame('1', $translations[1]['contained_in_organisation']);
    }

    #[Test]
    public function reimportPointsTranslatedTownAtDefaultOrganisation(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsTownManagedByTranslatedOrganisation.php');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Import/ExistingTownAndTouristInformationRelatedToTranslatedRecords.csv');
        $this->expectFetch('town-managed-by-translated-organisation.json');
        $this->expectFetch('organisation-translated.json');

        $this->importConfiguration(1);

        $translations = $this->fetchTranslationsByParent('tx_thuecat_town', 3);
        self::assertArrayHasKey(1, $translations);
        self::assertSame(1, $translations[1]['managed_by']);
    }
}
