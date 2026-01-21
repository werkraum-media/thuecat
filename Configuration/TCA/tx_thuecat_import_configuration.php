<?php

declare(strict_types=1);

use WerkraumMedia\ThueCat\Extension;

defined('TYPO3') or die();

return (static function (string $extensionKey, string $tableName) {
    $languagePath = Extension::getLanguagePath() . 'locallang_tca.xlf:' . $tableName;
    $flexFormConfigurationPath = 'FILE:EXT:' . Extension::EXTENSION_KEY . '/Configuration/FlexForm/';

    return [
        'ctrl' => [
            'label' => 'title',
            'iconfile' => Extension::getIconPath() . $tableName . '.svg',
            'type' => 'type',
            'default_sortby' => 'title',
            'tstamp' => 'tstamp',
            'crdate' => 'crdate',
            'title' => $languagePath,
            'enablecolumns' => [
                'disabled' => 'disable',
            ],
            'searchFields' => 'title',
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
                    'type' => 'datetime',
                    'format' => 'datetime',
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
})(Extension::EXTENSION_KEY, 'tx_thuecat_import_configuration');
