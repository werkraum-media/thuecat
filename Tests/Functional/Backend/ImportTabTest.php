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

namespace WerkraumMedia\ThueCat\Tests\Functional\Backend;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractFunctionalTestCase;

// Reads the compiled TCA, so overrides of other extensions and packages are part of the outcome.
final class ImportTabTest extends AbstractFunctionalTestCase
{
    private const IMPORT_TAB = 'LLL:EXT:thuecat/Resources/Private/Language/locallang_tca.xlf:import.tab';

    private const ORIGIN_PALETTE = ['import_origin' => ['origin_configuration_uid', 'origin_configuration_title', '--linebreak--', 'origin_sync_id']];

    /**
     * @return iterable<string, array{string, array<string, list<string>>}>
     */
    public static function tables(): iterable
    {
        foreach ([
            'tx_thuecat_tourist_attraction',
            'tx_thuecat_trail',
            'tx_thuecat_organisation',
            'tx_thuecat_town',
            'tx_thuecat_tourist_information',
            'tx_thuecat_place',
            'tx_thuecat_meeting_room',
            'tx_thuecat_parking_facility',
            'tx_events_domain_model_organizer',
        ] as $table) {
            yield $table => [$table, ['import_identity' => ['remote_id'], ...self::ORIGIN_PALETTE]];
        }

        yield 'tx_events_domain_model_event' => [
            'tx_events_domain_model_event',
            [
                'import_identity' => ['global_id', 'remote_id'],
                'import_source' => ['source_name', 'source_url', '--linebreak--', 'import_configuration', 'thuecat_import_configuration'],
                ...self::ORIGIN_PALETTE,
            ],
        ];
        yield 'tx_events_domain_model_location' => [
            'tx_events_domain_model_location',
            ['import_identity' => ['global_id'], ...self::ORIGIN_PALETTE],
        ];
    }

    /**
     * @param array<string, list<string>> $importPalettes
     */
    #[Test]
    #[DataProvider('tables')]
    public function importTabHoldsOnePalettePerKindOfField(string $table, array $importPalettes): void
    {
        $tca = $GLOBALS['TCA'] ?? null;
        $this->assertIsArray($tca);
        $tableTca = $tca[$table] ?? null;
        $this->assertIsArray($tableTca);
        $types = $tableTca['types'] ?? null;
        $this->assertIsArray($types);
        $palettes = $tableTca['palettes'] ?? null;
        $this->assertIsArray($palettes);

        foreach ($types as $type => $typeConfiguration) {
            $this->assertIsArray($typeConfiguration);
            $showitem = $typeConfiguration['showitem'] ?? '';
            $this->assertIsString($showitem);

            $tabItems = [];
            $tab = '';
            foreach (explode(',', $showitem) as $item) {
                $parts = array_map(trim(...), explode(';', $item));
                if ($parts[0] === '--div--') {
                    $tab = $parts[1] ?? '';
                    continue;
                }
                if ($tab === self::IMPORT_TAB) {
                    $tabItems[] = $parts[0] === '--palette--' ? $parts[2] ?? '' : $parts[0];
                }
            }

            $this->assertSame(array_keys($importPalettes), $tabItems, $table . ' type ' . $type);
        }

        foreach ($importPalettes as $paletteName => $fields) {
            $palette = $palettes[$paletteName] ?? null;
            $this->assertIsArray($palette, $table . ' palette ' . $paletteName);
            $this->assertStringStartsWith('LLL:EXT:thuecat/', is_string($palette['label'] ?? null) ? $palette['label'] : '', $table . ' palette ' . $paletteName);
            $paletteShowitem = $palette['showitem'] ?? '';
            $this->assertIsString($paletteShowitem);
            $this->assertSame(
                $fields,
                array_map(trim(...), explode(',', $paletteShowitem)),
                $table . ' palette ' . $paletteName
            );
        }
    }

    /**
     * @param array<string, list<string>> $importPalettes
     */
    #[Test]
    #[DataProvider('tables')]
    public function importFieldsSitOnTheImportTabOnly(string $table, array $importPalettes): void
    {
        $importFields = array_values(array_diff(array_merge(...array_values($importPalettes)), ['--linebreak--']));

        $tca = $GLOBALS['TCA'] ?? null;
        $this->assertIsArray($tca);
        $tableTca = $tca[$table] ?? null;
        $this->assertIsArray($tableTca);
        $types = $tableTca['types'] ?? null;
        $this->assertIsArray($types);
        $palettes = $tableTca['palettes'] ?? [];
        $this->assertIsArray($palettes);

        foreach ($types as $type => $typeConfiguration) {
            $this->assertIsArray($typeConfiguration);
            $showitem = $typeConfiguration['showitem'] ?? '';
            $this->assertIsString($showitem);

            $fieldsByTab = [];
            $tab = '';
            foreach (explode(',', $showitem) as $item) {
                $parts = array_map(trim(...), explode(';', $item));
                if ($parts[0] === '--div--') {
                    $tab = $parts[1] ?? '';
                    continue;
                }

                if ($parts[0] === '--palette--') {
                    $palette = $palettes[$parts[2] ?? ''] ?? [];
                    $this->assertIsArray($palette);
                    $paletteShowitem = $palette['showitem'] ?? '';
                    $this->assertIsString($paletteShowitem);
                    foreach (explode(',', $paletteShowitem) as $paletteItem) {
                        $field = trim(explode(';', $paletteItem)[0]);
                        if ($field !== '' && $field !== '--linebreak--') {
                            $fieldsByTab[$tab][] = $field;
                        }
                    }
                    continue;
                }

                if ($parts[0] !== '') {
                    $fieldsByTab[$tab][] = $parts[0];
                }
            }

            $this->assertSame($importFields, $fieldsByTab[self::IMPORT_TAB] ?? [], $table . ' type ' . $type);
            unset($fieldsByTab[self::IMPORT_TAB]);
            foreach ($fieldsByTab as $otherTab => $fields) {
                $this->assertSame([], array_values(array_intersect($importFields, $fields)), $table . ' type ' . $type . ' tab ' . $otherTab);
            }
        }
    }

    /**
     * @param array<string, list<string>> $importPalettes
     */
    #[Test]
    #[DataProvider('tables')]
    public function importFieldsAreReadOnlyAndExcludedFromTranslation(string $table, array $importPalettes): void
    {
        $importFields = array_values(array_diff(array_merge(...array_values($importPalettes)), ['--linebreak--']));

        $tca = $GLOBALS['TCA'] ?? null;
        $this->assertIsArray($tca);
        $tableTca = $tca[$table] ?? null;
        $this->assertIsArray($tableTca);
        $columns = $tableTca['columns'] ?? null;
        $this->assertIsArray($columns);

        foreach ($importFields as $field) {
            $column = $columns[$field] ?? null;
            $this->assertIsArray($column, $table . '.' . $field);
            $config = $column['config'] ?? null;
            $this->assertIsArray($config, $table . '.' . $field);
            $this->assertTrue($config['readOnly'] ?? false, $table . '.' . $field);
            $this->assertSame('exclude', $column['l10n_mode'] ?? '', $table . '.' . $field);
        }
    }

    #[Test]
    public function theLastImportingConfigurationOfAnEventIsDescribed(): void
    {
        $tca = $GLOBALS['TCA'] ?? null;
        $this->assertIsArray($tca);
        $tableTca = $tca['tx_events_domain_model_event'] ?? null;
        $this->assertIsArray($tableTca);
        $columns = $tableTca['columns'] ?? null;
        $this->assertIsArray($columns);
        $column = $columns['thuecat_import_configuration'] ?? null;
        $this->assertIsArray($column);

        $this->assertSame(
            'LLL:EXT:thuecat/Resources/Private/Language/locallang_tca.xlf:tx_events_domain_model_event.thuecat_import_configuration.description',
            $column['description'] ?? null
        );
    }
}
