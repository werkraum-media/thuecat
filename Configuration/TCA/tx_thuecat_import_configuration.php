<?php

defined('TYPO3') or die();

return (static function (string $extensionKey, string $tableName) {
    $languagePath = \WerkraumMedia\ThueCat\Extension::getLanguagePath() . 'locallang_tca.xlf:' . $tableName;
    $flexFormConfigurationPath = 'FILE:EXT:' . \WerkraumMedia\ThueCat\Extension::EXTENSION_KEY . '/Configuration/FlexForm/';

    return [
        'ctrl' => [
            'label' => 'title',
            'iconfile' => \WerkraumMedia\ThueCat\Extension::getIconPath() . $tableName . '.svg',
            'type' => 'type',
            'default_sortby' => 'title',
            'tstamp' => 'tstamp',
            'crdate' => 'crdate',
            'cruser_id' => 'cruser_id',
            'title' => $languagePath,
            'enablecolumns' => [
                'disabled' => 'disable',
            ],
            'searchFields' => 'title',
            'rootLevel' => -1,
        ],
        'columns' => [
            'title' => [
                'label' => $languagePath . '.title',
                'config' => [
                    'type' => 'input',
                    'max' => 255,
                    'eval' => 'required,trim,unique',
                ],
            ],
            'type' => [
                'label' => $languagePath . '.type',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'items' => [
                        [
                            $languagePath . '.type.static',
                            'static',
                        ],
                        [
                            $languagePath . '.type.syncScope',
                            'syncScope',
                        ],
                        [
                            $languagePath . '.type.containsPlace',
                            'containsPlace',
                        ],
                    ],
                ],
            ],
            'configuration' => [
                'label' => $languagePath . '.configuration',
                'config' => [
                    'type' => 'flex',
                    'ds_pointerField' => 'type',
                    'ds' => [
                        'default' => $flexFormConfigurationPath . 'ImportConfiguration/Static.xml',
                        'static' => $flexFormConfigurationPath . 'ImportConfiguration/Static.xml',
                        'syncScope' => $flexFormConfigurationPath . 'ImportConfiguration/SyncScope.xml',
                        'containsPlace' => $flexFormConfigurationPath . 'ImportConfiguration/ContainsPlace.xml',
                    ],
                ],
            ],
            'tstamp' => [
                'config' => [
                    'type' => 'input',
                    'renderType' => 'inputDateTime',
                    'eval' => 'datetime',
                    'readOnly' => true,
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
                'showitem' => 'title, type, configuration',
            ],
        ],
    ];
})(\WerkraumMedia\ThueCat\Extension::EXTENSION_KEY, 'tx_thuecat_import_configuration');
