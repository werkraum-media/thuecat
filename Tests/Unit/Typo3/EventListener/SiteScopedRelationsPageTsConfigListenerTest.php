<?php

declare(strict_types=1);

/*
 * Copyright (C) 2026 werkraum-media
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 */

namespace WerkraumMedia\ThueCat\Tests\Unit\Typo3\EventListener;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidIdentifierException;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Event\ModifyLoadedPageTsConfigEvent;
use WerkraumMedia\ThueCat\Service\SitePageIds;
use WerkraumMedia\ThueCat\Service\SiteScopedSelectFields;
use WerkraumMedia\ThueCat\Typo3\EventListener\SiteScopedRelationsPageTsConfigListener;

class SiteScopedRelationsPageTsConfigListenerTest extends TestCase
{
    #[Test]
    public function everyScopedFieldGetsTheSitesPageIds(): void
    {
        $emitted = $this->emitFor([7, 11, 12], [
            'tx_thuecat_town' => $this->tableWithColumns([
                'managed_by' => $this->selectColumn('tx_thuecat_organisation'),
            ]),
        ]);

        self::assertStringContainsString(
            'TCEFORM.tx_thuecat_town.managed_by.PAGE_TSCONFIG_IDLIST = 7,11,12',
            $emitted
        );
    }

    #[Test]
    public function fieldOutsideTheCriterionGetsNoLine(): void
    {
        $emitted = $this->emitFor([7], [
            'tx_thuecat_import_log_entry' => $this->tableWithColumns([
                'log' => $this->selectColumn('tx_thuecat_import_log'),
            ]),
        ]);

        self::assertStringNotContainsString('tx_thuecat_import_log_entry', $emitted);
    }

    #[Test]
    public function pageBelongingToNoSiteOffersNothingRatherThanEverything(): void
    {
        $emitted = $this->emitFor([], [
            'tx_thuecat_town' => $this->tableWithColumns([
                'managed_by' => $this->selectColumn('tx_thuecat_organisation'),
            ]),
        ]);

        self::assertStringContainsString(
            'TCEFORM.tx_thuecat_town.managed_by.PAGE_TSCONFIG_IDLIST = 0',
            $emitted
        );
    }

    #[Test]
    public function everyScopedFieldOfEveryTableIsCovered(): void
    {
        $emitted = $this->emitFor([7], [
            'tx_thuecat_town' => $this->tableWithColumns([
                'managed_by' => $this->selectColumn('tx_thuecat_organisation'),
            ]),
            'tx_thuecat_trail' => $this->tableWithColumns([
                'managed_by' => $this->selectColumn('tx_thuecat_organisation'),
            ]),
        ]);

        self::assertStringContainsString('TCEFORM.tx_thuecat_town.managed_by.PAGE_TSCONFIG_IDLIST = 7', $emitted);
        self::assertStringContainsString('TCEFORM.tx_thuecat_trail.managed_by.PAGE_TSCONFIG_IDLIST = 7', $emitted);
    }

    #[Test]
    public function translationParentGetsNoLine(): void
    {
        $emitted = $this->emitFor([7], [
            'tx_thuecat_town' => $this->tableWithColumns(
                [
                    'l10n_parent' => $this->selectColumn('tx_thuecat_town'),
                ],
                'l10n_parent'
            ),
        ]);

        self::assertStringNotContainsString('l10n_parent', $emitted);
    }

    #[Test]
    public function flexFormFieldGetsItsIdListUnderTheDataStructureKey(): void
    {
        // TcaFlexProcess looks the marker up at
        // TCEFORM.<table>.<flexField>.<dataStructureKey>.<sheet>.<field>,
        // by exact key, and the key is the record type. The data structure is
        // an XML string in TCA, so it has to be parsed to find the fields.
        $emitted = $this->emitFor(
            [7, 11],
            [
                'tt_content' => [
                    'ctrl' => ['type' => 'CType'],
                    'columns' => [
                        'pi_flexform' => ['config' => ['type' => 'flex']],
                    ],
                    'types' => [
                        'werkraummedia_thuecatattractionlistfiltered' => [
                            'columnsOverrides' => [
                                'pi_flexform' => ['config' => ['ds' => '<irrelevant/>']],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'werkraummedia_thuecatattractionlistfiltered' => [
                    'sheets' => [
                        'sDEF' => [
                            'ROOT' => [
                                'el' => [
                                    'settings.towns' => $this->selectColumn('tx_thuecat_town'),
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        );

        self::assertStringContainsString(
            'TCEFORM.tt_content.pi_flexform.werkraummedia_thuecatattractionlistfiltered'
            . '.sDEF.settings\\.towns.PAGE_TSCONFIG_IDLIST = 7,11',
            $emitted
        );
    }

    #[Test]
    public function typeWhoseDataStructureCannotBeParsedIsSkipped(): void
    {
        $emitted = $this->emitFor(
            [7],
            [
                'tt_content' => [
                    'ctrl' => ['type' => 'CType'],
                    'columns' => [
                        'pi_flexform' => ['config' => ['type' => 'flex']],
                    ],
                    'types' => [
                        'broken' => [
                            'columnsOverrides' => [
                                'pi_flexform' => ['config' => ['ds' => '<broken/>']],
                            ],
                        ],
                    ],
                ],
            ],
            []
        );

        self::assertStringNotContainsString('broken', $emitted);
    }

    #[Test]
    public function theDeepestRootLinePageDecidesTheScope(): void
    {
        // The rootline runs root first and opens with a synthetic uid 0; the
        // page being edited is the last entry.
        $sitePageIds = $this->createMock(SitePageIds::class);
        $sitePageIds->expects(self::once())
            ->method('forStoragePidOrEmpty')
            ->with(4010)
            ->willReturn([4000, 4010])
        ;

        $GLOBALS['TCA'] = [
            'tx_thuecat_town' => $this->tableWithColumns([
                'managed_by' => $this->selectColumn('tx_thuecat_organisation'),
            ]),
        ];

        $listener = new SiteScopedRelationsPageTsConfigListener(
            $sitePageIds,
            new SiteScopedSelectFields(),
            $this->flexFormToolsReturning([])
        );

        $event = new ModifyLoadedPageTsConfigEvent([], [
            ['uid' => 0],
            ['uid' => 4000],
            ['uid' => 4010],
        ]);
        $listener($event);

        $emitted = [];
        foreach ($event->getTsConfig() as $entry) {
            if (is_string($entry)) {
                $emitted[] = $entry;
            }
        }

        self::assertStringContainsString(
            'TCEFORM.tx_thuecat_town.managed_by.PAGE_TSCONFIG_IDLIST = 4000,4010',
            implode("\n", $emitted)
        );
    }

    #[Test]
    public function rootLineWithoutAPageEmitsNothing(): void
    {
        $listener = new SiteScopedRelationsPageTsConfigListener(
            $this->sitePageIdsReturning([7]),
            new SiteScopedSelectFields(),
            $this->flexFormToolsReturning([])
        );

        $event = new ModifyLoadedPageTsConfigEvent([], []);
        $listener($event);

        self::assertSame([], $event->getTsConfig());
    }

    /**
     * @param list<int> $sitePageIds
     * @param array<string, mixed> $tca
     * @param array<string, array<string, mixed>> $dataStructures parsed structure per record type
     */
    private function emitFor(array $sitePageIds, array $tca, array $dataStructures = []): string
    {
        $GLOBALS['TCA'] = $tca;

        $listener = new SiteScopedRelationsPageTsConfigListener(
            $this->sitePageIdsReturning($sitePageIds),
            new SiteScopedSelectFields(),
            $this->flexFormToolsReturning($dataStructures)
        );

        $event = new ModifyLoadedPageTsConfigEvent([], [['uid' => 3]]);
        $listener($event);

        $emitted = [];
        foreach ($event->getTsConfig() as $entry) {
            if (is_string($entry)) {
                $emitted[] = $entry;
            }
        }

        return implode("\n", $emitted);
    }

    /**
     * @param array<string, array<string, mixed>> $dataStructures parsed structure per record type
     */
    private function flexFormToolsReturning(array $dataStructures): FlexFormTools
    {
        $flexFormTools = $this->createMock(FlexFormTools::class);
        $flexFormTools->method('parseDataStructureByIdentifier')->willReturnCallback(
            static function (string $identifier) use ($dataStructures): array {
                $decoded = json_decode($identifier, true);
                $key = is_array($decoded) ? ($decoded['dataStructureKey'] ?? null) : null;
                $type = is_string($key) ? $key : '';
                if (!isset($dataStructures[$type])) {
                    throw new InvalidIdentifierException('no structure', 1758100000);
                }

                return $dataStructures[$type];
            }
        );

        return $flexFormTools;
    }

    /**
     * @param list<int> $pageIds
     */
    private function sitePageIdsReturning(array $pageIds): SitePageIds
    {
        $sitePageIds = $this->createMock(SitePageIds::class);
        $sitePageIds->method('forStoragePidOrEmpty')->willReturn($pageIds);

        return $sitePageIds;
    }

    /**
     * @param array<string, array<string, mixed>> $columns
     *
     * @return array<string, mixed>
     */
    private function tableWithColumns(array $columns, string $transOrigPointerField = 'l18n_parent'): array
    {
        return [
            'ctrl' => [
                'languageField' => 'sys_language_uid',
                'transOrigPointerField' => $transOrigPointerField,
            ],
            'columns' => $columns,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function selectColumn(string $foreignTable): array
    {
        return [
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => $foreignTable,
            ],
        ];
    }
}
