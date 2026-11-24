<?php

declare(strict_types=1);

defined('TYPO3') || die();

return (static function (string $extensionKey, string $tableName): array {
    $languagePath = 'LLL:EXT:thuecat/Resources/Private/Language/locallang_tca.xlf:' . $tableName;

    // configuration holds the settings of thuecat 7 until ImportConfigurationFlexFormMigration moves them to
    // columns; it is shown nowhere. el needs a field so it parses as an array, not an empty string: core's
    // RelationMapBuilder foreach-es ROOT/el on the raw placeholder.
    $placeholderDs = '<T3DataStructure><sheets><sDEF><ROOT><type>array</type><el><placeholder><config><type>passthrough</type></config></placeholder></el></ROOT></sDEF></sheets></T3DataStructure>';

    return [
        'ctrl' => [
            'label' => 'title',
            'iconfile' => 'EXT:thuecat/Resources/Public/Icons/' . $tableName . '.svg',
            'type' => 'type',
            'default_sortby' => 'title',
            'tstamp' => 'tstamp',
            'crdate' => 'crdate',
            'title' => $languagePath,
            'delete' => 'deleted',
            'enablecolumns' => [
                'disabled' => 'disable',
            ],
            'security' => [
                'ignoreRootLevelRestriction' => true,
            ],
            'rootLevel' => -1,
        ],
        'columns' => [
            'title' => [
                'label' => $languagePath . '.title',
                'config' => [
                    'type' => 'input',
                    'max' => 255,
                    'eval' => 'trim,unique',
                    'required' => true,
                ],
            ],
            'type' => [
                'label' => $languagePath . '.type',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'items' => [
                        [
                            'label' => $languagePath . '.type.static',
                            'value' => 'static',
                        ],
                        [
                            'label' => $languagePath . '.type.syncScope',
                            'value' => 'syncScope',
                        ],
                        [
                            'label' => $languagePath . '.type.containsPlace',
                            'value' => 'containsPlace',
                        ],
                        [
                            'label' => $languagePath . '.type.syncChannel',
                            'value' => 'syncChannel',
                        ],
                    ],
                ],
            ],
            'configuration' => [
                'label' => $languagePath . '.configuration',
                'config' => [
                    'type' => 'flex',
                    'ds' => $placeholderDs,
                    'searchable' => false,
                ],
            ],
            'storage_pid' => [
                'label' => $languagePath . '.storage_pid',
                'onChange' => 'reload',
                'config' => [
                    'type' => 'group',
                    'allowed' => 'pages',
                    'maxitems' => 1,
                    'size' => 1,
                    'required' => true,
                    'suggestOptions' => [
                        'default' => [
                            'addWhere' => 'AND pages.doktype = 254',
                        ],
                    ],
                ],
            ],
            'file_folder' => [
                'label' => $languagePath . '.file_folder',
                'description' => $languagePath . '.file_folder.description',
                'config' => [
                    'type' => 'folder',
                    'maxitems' => 1,
                    'minitems' => 1,
                    'size' => 1,
                ],
            ],
            'api_key' => [
                'label' => $languagePath . '.api_key',
                'description' => $languagePath . '.api_key.description',
                'config' => [
                    'type' => 'input',
                    'eval' => 'trim',
                    'searchable' => false,
                ],
            ],
            'urls' => [
                'label' => $languagePath . '.urls',
                'config' => [
                    'type' => 'inline',
                    'foreign_table' => 'tx_thuecat_import_configuration_url',
                    'foreign_field' => 'configuration',
                    'foreign_sortby' => 'sorting',
                    'minitems' => 1,
                    'appearance' => [
                        'useSortable' => true,
                        'expandSingle' => true,
                    ],
                ],
            ],
            'sync_scope_id' => [
                'label' => $languagePath . '.sync_scope_id',
                'config' => [
                    'type' => 'input',
                    'eval' => 'trim',
                    'required' => true,
                ],
            ],
            'fetch_last_x_days' => [
                'label' => $languagePath . '.fetch_last_x_days',
                'description' => $languagePath . '.fetch_last_x_days.description',
                'config' => [
                    'type' => 'number',
                    'default' => 1,
                ],
            ],
            'contains_place_id' => [
                'label' => $languagePath . '.contains_place_id',
                'description' => $languagePath . '.contains_place_id.description',
                'config' => [
                    'type' => 'input',
                    'eval' => 'trim',
                    'required' => true,
                ],
            ],
            'sync_channel_id' => [
                'label' => $languagePath . '.sync_channel_id',
                'description' => $languagePath . '.sync_channel_id.description',
                'config' => [
                    'type' => 'input',
                    'eval' => 'trim',
                    'required' => true,
                ],
            ],
            // 0 is "not set": the extension configuration applies.
            'message_budget' => [
                'label' => $languagePath . '.message_budget',
                'description' => $languagePath . '.message_budget.description',
                'config' => [
                    'type' => 'number',
                    'range' => ['lower' => 0],
                ],
            ],
            'fetch_cache_lifetime' => [
                'label' => $languagePath . '.fetch_cache_lifetime',
                'description' => $languagePath . '.fetch_cache_lifetime.description',
                'config' => [
                    'type' => 'number',
                    'range' => ['lower' => 0],
                ],
            ],
            'tstamp' => [
                'config' => [
                    'type' => 'datetime',
                    'format' => 'datetime',
                    'readOnly' => true,
                    'searchable' => false,
                ],
            ],
            // Configured for usage within Extbase, not TCA itself
            'logs' => [
                'config' => [
                    'type' => 'inline',
                    'foreign_table' => 'tx_thuecat_import_log',
                    'foreign_field' => 'configuration',
                    'readOnly' => true,
                ],
            ],
        ],
        'types' => [
            '0' => [
                'showitem' => 'title, type',
            ],
            'static' => [
                'showitem' => 'title, type, storage_pid, file_folder, api_key, urls, --palette--;;tunables',
            ],
            'syncScope' => [
                'showitem' => 'title, type, storage_pid, file_folder, sync_scope_id, api_key, fetch_last_x_days, --palette--;;tunables',
            ],
            'containsPlace' => [
                'showitem' => 'title, type, storage_pid, file_folder, contains_place_id, api_key, --palette--;;tunables',
            ],
            // No fetch_last_x_days: the window follows the last successful run.
            'syncChannel' => [
                'showitem' => 'title, type, storage_pid, file_folder, sync_channel_id, api_key, --palette--;;tunables',
                'columnsOverrides' => [
                    'api_key' => [
                        'label' => $languagePath . '.api_key.syncChannel',
                        'description' => $languagePath . '.api_key.syncChannel.description',
                        'config' => ['required' => true],
                    ],
                ],
            ],
        ],
        'palettes' => [
            'tunables' => [
                'label' => $languagePath . '.palette.tunables',
                'showitem' => 'message_budget, fetch_cache_lifetime',
            ],
        ],
    ];
})('thuecat', 'tx_thuecat_import_configuration');
