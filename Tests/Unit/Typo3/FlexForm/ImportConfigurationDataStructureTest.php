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

namespace WerkraumMedia\ThueCat\Tests\Unit\Typo3\FlexForm;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use WerkraumMedia\ThueCat\Typo3\FlexForm\ImportConfigurationDataStructure;

class ImportConfigurationDataStructureTest extends TestCase
{
    #[Test]
    public function syncChannelHasOnlyItsOwnFields(): void
    {
        $dataStructure = (new ImportConfigurationDataStructure())->forType('syncChannel');
        self::assertIsArray($dataStructure);

        $fields = ArrayUtility::getValueByPath($dataStructure, 'sheets/sDEF/ROOT/el');
        self::assertIsArray($fields);

        self::assertSame(
            ['storagePid', 'fileFolder', 'syncChannelId', 'apiKey', 'runBudget', 'fetchCacheLifetime'],
            array_keys($fields)
        );
    }

    #[Test]
    public function syncChannelRequiresChannelIdAndApiKey(): void
    {
        $dataStructure = (new ImportConfigurationDataStructure())->forType('syncChannel');
        self::assertIsArray($dataStructure);

        self::assertTrue(ArrayUtility::getValueByPath($dataStructure, 'sheets/sDEF/ROOT/el/syncChannelId/config/required'));
        self::assertTrue(ArrayUtility::getValueByPath($dataStructure, 'sheets/sDEF/ROOT/el/apiKey/config/required'));
    }

    #[Test]
    public function syncScopeKeepsItsApiDomainDefault(): void
    {
        $dataStructure = (new ImportConfigurationDataStructure())->forType('syncScope');
        self::assertIsArray($dataStructure);

        self::assertSame(
            'https://cdb.thuecat.org',
            ArrayUtility::getValueByPath($dataStructure, 'sheets/sDEF/ROOT/el/apiDomain/config/default')
        );
    }
}
