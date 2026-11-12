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

namespace WerkraumMedia\ThueCat\Tests\Unit\Frontend\Cache;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Cache\Backend\SimpleFileBackend;
use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use WerkraumMedia\ThueCat\Frontend\Cache\FrontendCacheRegistration;

final class FrontendCacheRegistrationTest extends TestCase
{
    private const array REGISTERED = [
        'frontend' => VariableFrontend::class,
        'backend' => Typo3DatabaseBackend::class,
        'groups' => ['pages'],
        'options' => [
            'defaultLifetime' => 31536000,
        ],
    ];

    private mixed $confVarsBackup;

    protected function setUp(): void
    {
        parent::setUp();
        // The suite runs with backupGlobals="false", so restore this by hand.
        $this->confVarsBackup = $GLOBALS['TYPO3_CONF_VARS'] ?? null;
        $GLOBALS['TYPO3_CONF_VARS'] = [];
    }

    protected function tearDown(): void
    {
        $GLOBALS['TYPO3_CONF_VARS'] = $this->confVarsBackup;

        parent::tearDown();
    }

    #[Test]
    public function registersEachFrontendCacheInPagesGroupForOneYear(): void
    {
        FrontendCacheRegistration::register();

        $this->assertSame([
            'SYS' => [
                'caching' => [
                    'cacheConfigurations' => [
                        FrontendCacheRegistration::TEASER => self::REGISTERED,
                        FrontendCacheRegistration::LIST => self::REGISTERED,
                        FrontendCacheRegistration::SEARCH_MASK => self::REGISTERED,
                    ],
                ],
            ],
        ], $GLOBALS['TYPO3_CONF_VARS']);
    }

    #[Test]
    public function keepsAnIntegratorsOwnConfiguration(): void
    {
        $GLOBALS['TYPO3_CONF_VARS'] = [
            'SYS' => [
                'caching' => [
                    'cacheConfigurations' => [
                        FrontendCacheRegistration::LIST => ['backend' => SimpleFileBackend::class],
                    ],
                ],
            ],
        ];

        FrontendCacheRegistration::register();

        $this->assertSame([
            'SYS' => [
                'caching' => [
                    'cacheConfigurations' => [
                        FrontendCacheRegistration::LIST => ['backend' => SimpleFileBackend::class],
                        FrontendCacheRegistration::TEASER => self::REGISTERED,
                        FrontendCacheRegistration::SEARCH_MASK => self::REGISTERED,
                    ],
                ],
            ],
        ], $GLOBALS['TYPO3_CONF_VARS']);
    }
}
