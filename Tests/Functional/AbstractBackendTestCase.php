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

namespace WerkraumMedia\ThueCat\Tests\Functional;

use TYPO3\CMS\Core\Localization\LanguageServiceFactory;

/**
 * Sets up a logged-in backend user, the `LANG` global and the site configurations
 * for tests that run backend code.
 */
abstract class AbstractBackendTestCase extends AbstractFunctionalTestCase
{
    public const PATHS_TO_LINK = [
        'typo3conf/ext/thuecat/Tests/Functional/Fixtures/Import/Sites/' => 'typo3conf/sites',
    ];

    protected array $pathsToLinkInTestInstance = self::PATHS_TO_LINK;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/BackendUser.php');
        $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->getContainer()->get(LanguageServiceFactory::class)->create('en_US');
    }

    protected function tearDown(): void
    {
        // clean up GLOBALS left behind by previous tests
        unset($GLOBALS['LANG']);
        unset($GLOBALS['TYPO3_REQUEST']);
        parent::tearDown();
    }
}
