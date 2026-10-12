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

final class SlugImportTest extends AbstractImportTestCase
{
    #[Test]
    public function newAttractionAndItsTranslationGetSlugsFromTheirOwnTitles(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsTouristAttractionWithSingleSlogan.php');
        $this->expectFetch('attraction-with-single-slogan.json');
        $this->expectFetch('018132452787-ngbe.json');

        $this->importConfiguration(1);

        $remoteId = 'https://thuecat.org/resources/attraction-with-single-slogan';
        $default = $this->fetchRowByRemoteId('tx_thuecat_tourist_attraction', $remoteId);
        self::assertSame('attraktion-mit-single-slogan', $default['slug']);

        $translations = $this->fetchTranslationsByParent(
            'tx_thuecat_tourist_attraction',
            $this->fetchUidByRemoteId('tx_thuecat_tourist_attraction', $remoteId)
        );
        self::assertArrayHasKey(1, $translations);
        self::assertSame('attraction-with-single-slogan', $translations[1]['slug']);
    }

    #[Test]
    public function sameTitleInOneSiteGetsDistinctSlug(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsTouristAttractionWithSingleSlogan.php');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Import/ExistingAttractionWithSameTitle.csv');
        $this->expectFetch('attraction-with-single-slogan.json');
        $this->expectFetch('018132452787-ngbe.json');

        $this->importConfiguration(1);

        $imported = $this->fetchRowByRemoteId(
            'tx_thuecat_tourist_attraction',
            'https://thuecat.org/resources/attraction-with-single-slogan'
        );
        self::assertIsString($imported['slug']);
        self::assertStringStartsWith('attraktion-mit-single-slogan', $imported['slug']);
        self::assertNotSame('attraktion-mit-single-slogan', $imported['slug']);
    }

    #[Test]
    public function existingSlugsSurviveTitleChangeUpstream(): void
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Import/ImportsTouristAttractionWithSingleSlogan.php');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Import/ExistingAttractionWithOldTitleAndSlug.csv');
        $this->expectFetch('attraction-with-single-slogan.json');
        $this->expectFetch('018132452787-ngbe.json');

        $this->importConfiguration(1);

        $remoteId = 'https://thuecat.org/resources/attraction-with-single-slogan';
        $default = $this->fetchRowByRemoteId('tx_thuecat_tourist_attraction', $remoteId);
        self::assertSame('Attraktion mit single slogan', $default['title']);
        self::assertSame('alter-titel', $default['slug']);

        $translations = $this->fetchTranslationsByParent(
            'tx_thuecat_tourist_attraction',
            $this->fetchUidByRemoteId('tx_thuecat_tourist_attraction', $remoteId)
        );
        self::assertArrayHasKey(1, $translations);
        self::assertSame('Attraction with single slogan', $translations[1]['title']);
        self::assertSame('old-title', $translations[1]['slug']);
    }
}
