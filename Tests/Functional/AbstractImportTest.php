<?php

declare(strict_types=1);

/*
 * Copyright (C) 2023 Daniel Siepmann <coding@daniel-siepmann.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301, USA.
 */

namespace WerkraumMedia\ThueCat\Tests\Functional;

use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

abstract class AbstractImportTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        $this->coreExtensionsToLoad = array_merge($this->coreExtensionsToLoad, [
            'core',
            'backend',
            'extbase',
            'frontend',
        ]);

        $this->testExtensionsToLoad = array_merge($this->testExtensionsToLoad, [
            'typo3conf/ext/thuecat/',
        ]);

        $this->pathsToLinkInTestInstance = array_merge($this->pathsToLinkInTestInstance, [
            'typo3conf/ext/thuecat/Tests/Functional/Fixtures/Import/Sites/' => 'typo3conf/sites',
        ]);

        $this->configurationToUseInTestInstance = array_merge($this->configurationToUseInTestInstance, [
            'LOG' => [
                'WerkraumMedia' => [
                    'writerConfiguration' => [
                        \TYPO3\CMS\Core\Log\LogLevel::DEBUG => [
                            \TYPO3\CMS\Core\Log\Writer\FileWriter::class => [
                                'logFileInfix' => 'debug',
                            ],
                        ],
                    ],
                ],
            ],
            'EXTENSIONS' => [
                'thuecat' => [
                    'apiKey' => null,
                ],
            ],
        ]);

        parent::setUp();
        GuzzleClientFaker::registerClient();

        $this->setUpBackendUserFromFixture(1);

        $GLOBALS['LANG'] = $this->getContainer()->get(LanguageService::class);
    }

    protected function assertPostConditions(): void
    {
        $path = self::getInstancePath() . '/typo3temp/var/log/typo3_0493d91d8e.log';
        $this->assertSame(
            '',
            file_get_contents($path),
            'The TYPO3 log file contained content while expecting to be empty.'
        );
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['LANG']);
        GuzzleClientFaker::tearDown();

        parent::tearDown();
    }
}
