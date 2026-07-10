<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Information\Typo3Version;
use WerkraumMedia\ThueCat\Extension;

defined('TYPO3') or die();

return (static function (string $extensionKey, string $tableName) {
    $languagePath = Extension::getLanguagePath() . 'locallang_tca.xlf:' . $tableName;

    // The DS content is supplied by ImportConfigurationFlexFormListener. v14 reads
    // the columnsOverrides DS directly (plain string) and only requires it to be
    // NON-empty so the identifier resolves; hence a bare sDEF/ROOT.
    // el needs a field so it parses as an array, not an empty string: core's
    // RelationMapBuilder foreach-es ROOT/el on the raw placeholder.
    $placeholderDs = '<T3DataStructure><sheets><sDEF><ROOT><type>array</type><el><placeholder><config><type>passthrough</type></config></placeholder></el></ROOT></sDEF></sheets></T3DataStructure>';

    // TODO: typo3/cms-core:15.0 Remove condition and keep v14 support.
    $majorVersion = (new Typo3Version())->getMajorVersion();
    $flexFormField = 'configuration';
    if ($majorVersion === 13) {
        $flexFormField = 'pi_flexform';
    }

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
                    ],
                ],
            ],
            'configuration' => [
                'label' => $languagePath . '.configuration',
                'config' => [
                    'type' => 'flex',
                    // Content comes from ImportConfigurationFlexFormListener; this
                    // placeholder only lets the identifier resolve a dataStructureKey.
                    'ds' => ['default' => $placeholderDs],
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
                    $flexFormField => [
                        'config' => ['ds' => $placeholderDs],
                    ],
                ],
            ],
            'syncScope' => [
                'showitem' => 'title, type, configuration',
                'columnsOverrides' => [
                    $flexFormField => [
                        'config' => ['ds' => $placeholderDs],
                    ],
                ],
            ],
            'containsPlace' => [
                'showitem' => 'title, type, configuration',
                'columnsOverrides' => [
                    $flexFormField => [
                        'config' => ['ds' => $placeholderDs],
                    ],
                ],
            ],
        ],
    ];
})(Extension::EXTENSION_KEY, 'tx_thuecat_import_configuration');
