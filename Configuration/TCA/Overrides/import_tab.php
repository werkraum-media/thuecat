<?php

declare(strict_types=1);

defined('TYPO3') || die();

// Only an import writes these fields, and a translation shares its parent's upstream record.
(static function (): void {
    $languagePath = 'LLL:EXT:thuecat/Resources/Private/Language/locallang_tca.xlf:';

    $tca = $GLOBALS['TCA'] ?? null;
    if (!is_array($tca)) {
        return;
    }

    $originColumns = [
        'origin_configuration_uid' => [
            'label' => $languagePath . 'import.origin_configuration_uid',
            'config' => [
                'type' => 'number',
            ],
        ],
        'origin_configuration_title' => [
            'label' => $languagePath . 'import.origin_configuration_title',
            'config' => [
                'type' => 'input',
            ],
        ],
        'origin_sync_id' => [
            'label' => $languagePath . 'import.origin_sync_id',
            'config' => [
                'type' => 'input',
            ],
        ],
    ];

    $addedColumnsByTable = [
        'tx_events_domain_model_event' => [
            'remote_id' => [
                'label' => $languagePath . 'tx_events_domain_model_event.remote_id',
                'config' => [
                    'type' => 'input',
                    'searchable' => false,
                ],
            ],
            'thuecat_import_configuration' => [
                'exclude' => true,
                'label' => $languagePath . 'tx_events_domain_model_event.thuecat_import_configuration',
                'description' => $languagePath . 'tx_events_domain_model_event.thuecat_import_configuration.description',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'foreign_table' => 'tx_thuecat_import_configuration',
                    'items' => [
                        [
                            'value' => '0',
                            'label' => 0,
                        ],
                    ],
                ],
            ],
        ],
        'tx_events_domain_model_organizer' => [
            'remote_id' => [
                'label' => $languagePath . 'tx_events_domain_model_organizer.remote_id',
                'config' => [
                    'type' => 'input',
                    'searchable' => false,
                ],
            ],
        ],
    ];

    $remoteIdentity = ['import_identity' => ['remote_id']];
    $importPalettesByTable = [
        'tx_thuecat_tourist_attraction' => $remoteIdentity,
        'tx_thuecat_trail' => $remoteIdentity,
        'tx_thuecat_organisation' => $remoteIdentity,
        'tx_thuecat_town' => $remoteIdentity,
        'tx_thuecat_tourist_information' => $remoteIdentity,
        'tx_thuecat_place' => $remoteIdentity,
        'tx_thuecat_meeting_room' => $remoteIdentity,
        'tx_thuecat_parking_facility' => $remoteIdentity,
        'tx_events_domain_model_event' => [
            'import_identity' => ['global_id', 'remote_id'],
            'import_source' => [
                'source_name',
                'source_url',
                '--linebreak--',
                'import_configuration',
                'thuecat_import_configuration',
            ],
        ],
        'tx_events_domain_model_organizer' => $remoteIdentity,
        'tx_events_domain_model_location' => ['import_identity' => ['global_id']],
    ];

    // Kept last.
    $trailingTabs = ['LGL.language', 'tabs.access'];

    foreach ($importPalettesByTable as $table => $importPalettes) {
        $tableTca = $tca[$table] ?? null;
        if (!is_array($tableTca)) {
            continue;
        }

        $importPalettes['import_origin'] = [
            'origin_configuration_uid',
            'origin_configuration_title',
            '--linebreak--',
            'origin_sync_id',
        ];
        $importFields = array_values(array_diff(array_merge(...array_values($importPalettes)), ['--linebreak--']));

        $columns = array_replace(
            is_array($tableTca['columns'] ?? null) ? $tableTca['columns'] : [],
            $addedColumnsByTable[$table] ?? [],
            $originColumns
        );
        foreach ($importFields as $field) {
            $column = is_array($columns[$field] ?? null) ? $columns[$field] : [];
            $config = is_array($column['config'] ?? null) ? $column['config'] : [];
            $config['readOnly'] = true;
            $column['config'] = $config;
            $column['l10n_mode'] = 'exclude';
            $columns[$field] = $column;
        }

        $tableTca['columns'] = $columns;

        $isImportField = static fn (string $item): bool => in_array(trim(explode(';', $item)[0]), $importFields, true);

        $palettes = is_array($tableTca['palettes'] ?? null) ? $tableTca['palettes'] : [];
        $emptiedPalettes = [];
        foreach ($palettes as $paletteName => $palette) {
            if (!is_array($palette) || !is_string($palette['showitem'] ?? null)) {
                continue;
            }

            $items = array_values(array_filter(
                array_map(trim(...), explode(',', $palette['showitem'])),
                static fn (string $item): bool => $item !== '' && !$isImportField($item)
            ));
            if (array_diff($items, ['--linebreak--']) === []) {
                unset($palettes[$paletteName]);
                $emptiedPalettes[] = (string)$paletteName;
                continue;
            }

            $palette['showitem'] = implode(', ', $items);
            $palettes[$paletteName] = $palette;
        }

        foreach ($importPalettes as $paletteName => $fields) {
            $palettes[$paletteName] = [
                'label' => $languagePath . str_replace('import_', 'import.palette.', $paletteName),
                'showitem' => implode(', ', $fields),
            ];
        }

        $tableTca['palettes'] = $palettes;

        $types = is_array($tableTca['types'] ?? null) ? $tableTca['types'] : [];
        foreach ($types as $type => $typeConfiguration) {
            if (!is_array($typeConfiguration) || !is_string($typeConfiguration['showitem'] ?? null)) {
                continue;
            }

            $items = [];
            foreach (array_map(trim(...), explode(',', $typeConfiguration['showitem'])) as $item) {
                $parts = array_map(trim(...), explode(';', $item));
                if ($item === '' || $isImportField($item)) {
                    continue;
                }

                if ($parts[0] === '--palette--' && in_array($parts[2] ?? '', $emptiedPalettes, true)) {
                    continue;
                }

                // A tab left without fields would show up empty.
                if ($parts[0] === '--div--' && $items !== [] && str_starts_with(end($items), '--div--')) {
                    array_pop($items);
                }

                $items[] = $item;
            }

            if ($items !== [] && str_starts_with(end($items), '--div--')) {
                array_pop($items);
            }

            $position = count($items);
            foreach ($items as $index => $item) {
                if (!str_starts_with($item, '--div--')) {
                    continue;
                }

                foreach ($trailingTabs as $trailingTab) {
                    if (str_contains($item, $trailingTab)) {
                        $position = $index;
                        break 2;
                    }
                }
            }

            array_splice($items, $position, 0, [
                '--div--;' . $languagePath . 'import.tab',
                ...array_map(static fn (string $paletteName): string => '--palette--;;' . $paletteName, array_keys($importPalettes)),
            ]);
            $typeConfiguration['showitem'] = implode(', ', $items);
            $types[$type] = $typeConfiguration;
        }

        $tableTca['types'] = $types;

        $tca[$table] = $tableTca;
    }

    $GLOBALS['TCA'] = $tca;
})();
