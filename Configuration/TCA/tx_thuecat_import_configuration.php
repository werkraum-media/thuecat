<?php

declare(strict_types=1);

use WerkraumMedia\ThueCat\Extension;

defined('TYPO3') or die();

return (static function (string $extensionKey, string $tableName) {
    $languagePath = Extension::getLanguagePath() . 'locallang_tca.xlf:' . $tableName;

    // The DS content is supplied by ImportConfigurationFlexFormListener. The
    // placeholder only has to be NON-empty so the identifier resolves.
    // el needs a field so it parses as an array, not an empty string: core's
    // RelationMapBuilder foreach-es ROOT/el on the raw placeholder.
    $placeholderDs = '<T3DataStructure><sheets><sDEF><ROOT><type>array</type><el><placeholder><config><type>passthrough</type></config></placeholder></el></ROOT></sDEF></sheets></T3DataStructure>';

    return [
        'ctrl' => [
            'label' => 'title',
            'iconfile' => Extension::getIconPath() . $tableName . '.svg',
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
                    // Content comes from ImportConfigurationFlexFormListener; this
                    // placeholder only lets the identifier resolve a dataStructureKey.
                    'ds' => $placeholderDs,
                    'searchable' => false,
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
                'showitem' => 'title, type, configuration',
            ],
            'static' => [
                'showitem' => 'title, type, configuration',
                'columnsOverrides' => [
                    'configuration' => [
                        'config' => ['ds' => $placeholderDs],
                    ],
                ],
            ],
            'syncScope' => [
                'showitem' => 'title, type, configuration',
                'columnsOverrides' => [
                    'configuration' => [
                        'config' => ['ds' => $placeholderDs],
                    ],
                ],
            ],
            'containsPlace' => [
                'showitem' => 'title, type, configuration',
                'columnsOverrides' => [
                    'configuration' => [
                        'config' => ['ds' => $placeholderDs],
                    ],
                ],
            ],
            'syncChannel' => [
                'showitem' => 'title, type, configuration',
                'columnsOverrides' => [
                    'configuration' => [
                        'config' => ['ds' => $placeholderDs],
                    ],
                ],
            ],
        ],
    ];
})(Extension::EXTENSION_KEY, 'tx_thuecat_import_configuration');
