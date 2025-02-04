<?php

declare(strict_types=1);

/*
 * Copyright (C) 2025 Daniel Siepmann <daniel.siepmann@codappix.com>
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

namespace WerkraumMedia\ThueCat\Tests\Functional\Typo3\Hook;

use Codappix\Typo3PhpDatasets\PhpDataSet;
use Codappix\Typo3PhpDatasets\TestingFramework;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use WerkraumMedia\ThueCat\Tests\Functional\GuzzleClientFaker;
use WerkraumMedia\ThueCat\Typo3\Hook\AddTitleForStaticUrlsDataHandlerHook;

#[CoversClass(AddTitleForStaticUrlsDataHandlerHook::class)]
final class AddTitleForStaticUrlsDataHandlerHookTest extends FunctionalTestCase
{
    use TestingFramework;

    protected function setUp(): void
    {
        $this->coreExtensionsToLoad = array_merge($this->coreExtensionsToLoad, [
            'core',
            'backend',
        ]);
        $this->testExtensionsToLoad = array_merge($this->testExtensionsToLoad, [
            'werkraummedia/thuecat/',
        ]);
        $this->pathsToLinkInTestInstance = array_merge($this->pathsToLinkInTestInstance, [
            'typo3conf/ext/thuecat/Tests/Functional/Fixtures/Import/Sites/' => 'typo3conf/sites',
        ]);
        $this->configurationToUseInTestInstance = array_merge($this->configurationToUseInTestInstance, [
            'EXTENSIONS' => [
                'thuecat' => [
                    'apiKey' => null,
                ],
            ],
        ]);

        parent::setUp();

        GuzzleClientFaker::registerClient();
        $this->importPHPDataSet(__DIR__ . '/../../Fixtures/Import/BasicPages.php');
        $this->importPHPDataSet(__DIR__ . '/../../Fixtures/Import/BackendUser.php');
        $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->getContainer()->get(LanguageServiceFactory::class)->create('en_US');
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['LANG']);
        GuzzleClientFaker::tearDown();
        parent::tearDown();
    }

    #[Test]
    #[DataProvider('possibleUpdatesForTitle')]
    public function changesTitleFromRemote(
        string $existingConfiguration,
        array $submittedValues,
    ): void {
        (new PhpDataSet())->import([
            'tx_thuecat_import_configuration' => [
                [
                    'uid' => '1',
                    'pid' => '10',
                    'title' => 'Existing Configuration',
                    'type' => 'static',
                    'configuration' => $existingConfiguration,
                ],
            ],
        ]);

        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/../../Fixtures/AddTitleForStaticUrlsDataHandlerHook/Guzzle/thuecat.org/resources/r_22033250-oapoi.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/../../Fixtures/AddTitleForStaticUrlsDataHandlerHook/Guzzle/thuecat.org/resources/809459960188-epwb.json');

        $errorLog = $this->executeDataHandler($this->createDataMap($submittedValues));

        self::assertCount(
            0,
            $errorLog,
            'Got unexpected errors from DataHandler: ' . implode(', ', $errorLog)
        );

        $this->assertUrlTitles(
            'Titel Eins',
            'Titel Zwei',
        );
    }

    public static function possibleUpdatesForTitle(): iterable
    {
        yield 'when no titles are provided within TYPO3' => [
            'existingConfiguration' => self::createFlexForm('
                <field index="67a1b62355ad6737507957">
                    <value index="url">
                        <el>
                            <field index="title">
                                <value index="vDEF"></value>
                            </field>
                            <field index="url">
                                <value index="vDEF">https://example.com</value>
                            </field>
                        </el>
                    </value>
                </field>
                <field index="67a1bb94b4f41262966709">
                    <value index="url">
                        <el>
                            <field index="title">
                                <value index="vDEF"></value>
                            </field>
                            <field index="url">
                                <value index="vDEF">https://example.com>
                            </field>
                        </el>
                    </value>
                </field>
            '),
            'submittedValues' => [
                '67a1b62355ad6737507957' => [
                    'url' => 'https://thuecat.org/resources/r_22033250-oapoi',
                ],
                '67a1bb94b4f41262966709' => [
                    'url' => 'https://thuecat.org/resources/809459960188-epwb',
                ],
            ],
        ];

        yield 'when titles already existed' => [
            'existingConfiguration' => self::createFlexForm('
                <field index="67a1b62355ad6737507957">
                    <value index="url">
                        <el>
                            <field index="title">
                                <value index="vDEF">Existing Titel Eins</value>
                            </field>
                            <field index="url">
                                <value index="vDEF">https://example.com</value>
                            </field>
                        </el>
                    </value>
                </field>
                <field index="67a1bb94b4f41262966709">
                    <value index="url">
                        <el>
                            <field index="title">
                                <value index="vDEF">Existing Titel Zwei</value>
                            </field>
                            <field index="url">
                                <value index="vDEF">https://example.com>
                            </field>
                        </el>
                    </value>
                </field>
            '),
            'submittedValues' => [
                '67a1b62355ad6737507957' => [
                    'url' => 'https://thuecat.org/resources/r_22033250-oapoi',
                ],
                '67a1bb94b4f41262966709' => [
                    'url' => 'https://thuecat.org/resources/809459960188-epwb',
                ],
            ],
        ];

        yield 'when titles were submitted as well' => [
            'existingConfiguration' => self::createFlexForm('
                <field index="67a1b62355ad6737507957">
                    <value index="url">
                        <el>
                            <field index="title">
                                <value index="vDEF">Existing Titel Eins</value>
                            </field>
                            <field index="url">
                                <value index="vDEF">https://example.com</value>
                            </field>
                        </el>
                    </value>
                </field>
                <field index="67a1bb94b4f41262966709">
                    <value index="url">
                        <el>
                            <field index="title">
                                <value index="vDEF">Existing Titel Zwei</value>
                            </field>
                            <field index="url">
                                <value index="vDEF">https://example.com>
                            </field>
                        </el>
                    </value>
                </field>
            '),
            'submittedValues' => [
                '67a1b62355ad6737507957' => [
                    'title' => 'New Titel Eins',
                    'url' => 'https://thuecat.org/resources/r_22033250-oapoi',
                ],
                '67a1bb94b4f41262966709' => [
                    'title' => 'New Titel Zwei',
                    'url' => 'https://thuecat.org/resources/809459960188-epwb',
                ],
            ],
        ];
    }

    #[Test]
    public function addsTitleForAllUrlsInNewRecord(): void
    {
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/../../Fixtures/AddTitleForStaticUrlsDataHandlerHook/Guzzle/thuecat.org/resources/r_22033250-oapoi.json');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/../../Fixtures/AddTitleForStaticUrlsDataHandlerHook/Guzzle/thuecat.org/resources/809459960188-epwb.json');

        $dataMap = $this->createDataMap([
            '67a1b62355ad6737507957' => [
                'url' => 'https://thuecat.org/resources/r_22033250-oapoi',
            ],
            '67a1bb94b4f41262966709' => [
                'url' => 'https://thuecat.org/resources/809459960188-epwb',
            ],
        ], 'NEW67a1e37c5cc8c460046430');
        ArrayUtility::mergeRecursiveWithOverrule(
            $dataMap,
            [
                'tx_thuecat_import_configuration' => [
                    'NEW67a1e37c5cc8c460046430' => [
                        'title' => 'test new',
                        'type' => 'static',
                        'pid' => '10',
                    ],
                ],
            ]
        );

        $errorLog = $this->executeDataHandler($dataMap);

        self::assertCount(
            0,
            $errorLog,
            'Got unexpected errors from DataHandler: ' . implode(', ', $errorLog)
        );

        $this->assertUrlTitles(
            'Titel Eins',
            'Titel Zwei',
        );
    }

    #[Test]
    public function doesNothingIfNewRecordDoesNotContainUrls(): void
    {
        $dataMap = $this->createDataMap([], 'NEW67a1e37c5cc8c460046430');
        ArrayUtility::mergeRecursiveWithOverrule(
            $dataMap,
            [
                'tx_thuecat_import_configuration' => [
                    'NEW67a1e37c5cc8c460046430' => [
                        'title' => 'test new',
                        'type' => 'static',
                        'pid' => '10',
                    ],
                ],
            ]
        );

        $errorLog = $this->executeDataHandler($dataMap);

        self::assertCount(
            0,
            $errorLog,
            'Got unexpected errors from DataHandler: ' . implode(', ', $errorLog)
        );

        $this->assertUrlTitles();
    }

    private function executeDataHandler(array $dataMap): array
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->bypassAccessCheckForRecords = true;
        $dataHandler->start($dataMap, []);
        $dataHandler->process_datamap();

        return $dataHandler->errorLog;
    }

    private static function createFlexForm(string $part): string
    {
        return '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
            <T3FlexForms>
                <data>
                    <sheet index="sDEF">
                        <language index="lDEF">
                            <field index="storagePid">
                                <value index="vDEF">10</value>
                            </field>
                            <field index="urls">
                                <el index="el">
                                    ' . $part . '
                                </el>
                            </field>
                        </language>
                    </sheet>
                </data>
            </T3FlexForms>
        ';
    }

    private function createDataMap(array $urls, string|int $id = 1): array
    {
        $finalUrls = [];
        foreach ($urls as $identifier => $urlConfiguration) {
            $finalUrls[$identifier] =  [
                'url' => [
                    'el' => [
                        'title' => [
                            'vDEF' => $urlConfiguration['title'] ?? '',
                        ],
                        'url' => [
                            'vDEF' => $urlConfiguration['url'],
                        ],
                    ],
                ],
                '_ACTION' => '',
            ];
        }

        return [
            'tx_thuecat_import_configuration' => [
                $id => [
                    'title' => 'Test',
                    'type' => 'static',
                    'configuration' => [
                        'data' => [
                            'sDEF' => [
                                'lDEF' => [
                                    'storagePid' => [
                                        'vDEF' => '641',
                                    ],
                                    'urls' => [
                                        'el' => $finalUrls,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function assertUrlTitles(string ... $titles): void
    {
        $configurations = $this->getAllRecords('tx_thuecat_import_configuration', true);
        $configuration = GeneralUtility::xml2array($configurations[1]['configuration']);
        self::assertIsArray($configuration);

        $urls = ArrayUtility::getValueByPath($configuration, 'data/sDEF/lDEF/urls/el');
        if ($titles === []) {
            self::assertSame('', $urls);
            return;
        }
        self::assertIsArray($urls);

        self::assertCount(count($titles), $urls);
        $position = 0;
        foreach ($urls as $url) {
            self::assertSame(
                $titles[$position++],
                $url['url']['el']['title']['vDEF'],
                'Title for URL ' . $url['url']['el']['url']['vDEF'] . ' was not as expected.'
            );
        }
    }
}
