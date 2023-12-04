<?php

declare(strict_types=1);

use WerkraumMedia\ThueCat\Extension;

defined('TYPO3') or die();

return (static function (string $extensionKey, string $tableName) {
    $languagePath = Extension::getLanguagePath() . 'locallang_tca.xlf:' . $tableName;
    $flexFormConfigurationPath = 'FILE:EXT:' . Extension::EXTENSION_KEY . '/Configuration/FlexForm/';

    return [
        'ctrl' => [
            'label' => 'type',
            'label_alt' => 'remote_id, table_name, record_uid',
            'label_alt_force' => true,
            'iconfile' => Extension::getIconPath() . $tableName . '.svg',
            'type' => 'type',
            'default_sortby' => 'crdate',
            'tstamp' => 'tstamp',
            'crdate' => 'crdate',
            'title' => $languagePath,
            'enablecolumns' => [
                'disabled' => 'disable',
            ],
            'rootLevel' => 1,
            'hideTable' => true,
        ],
        'columns' => [
            'type' => [
                'label' => $languagePath . '.type',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'items' => [
                        [
                            'label' => $languagePath . '.type.savingEntity',
                            'value' => 'savingEntity',
                        ],
                        [
                            'label' => $languagePath . '.type.mappingError',
                            'value' => 'mappingError',
                        ],
                    ],
                ],
            ],
            'remote_id' => [
                'label' => $languagePath . '.remote_id',
                'config' => [
                    'type' => 'input',
                    'readOnly' => true,
                ],
            ],
            'insertion' => [
                'label' => $languagePath . '.insertion',
                'config' => [
                    'type' => 'check',
                    'renderType' => 'checkboxLabeledToggle',
                    'items' => [
                        [
                            'label' => '',
                            1 => '',
                            'labelChecked' => $languagePath . '.insertion.yes',
                            'labelUnchecked' => $languagePath . '.insertion.no',
                        ],
                    ],
                    'readOnly' => true,
                ],
            ],
            'table_name' => [
                'label' => $languagePath . '.table_name',
                'config' => [
                    'type' => 'input',
                    'readOnly' => true,
                ],
            ],
            'record_uid' => [
                'label' => $languagePath . '.record_uid',
                'config' => [
                    'type' => 'input',
                    'readOnly' => true,
                ],
            ],
            'errors' => [
                'label' => $languagePath . '.errors',
                'config' => [
                    'type' => 'json',
                    'readOnly' => true,
                ],
            ],
            'import_log' => [
                'label' => $languagePath . '.import_log',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'foreign_table' => 'tx_thuecat_import_log',
                    'readOnly' => true,
                ],
            ],
            'crdate' => [
                'label' => $languagePath . '.crdate',
                'config' => [
                    'type' => 'datetime',
                    'format' => 'datetime',
                    'readOnly' => true,
                ],
            ],
        ],
        'palettes' => [
            'always' => [
                'label' => $languagePath . '.palette.always',
                'showitem' => 'type, remote_id, import_log, crdate',
            ],
        ],
        'types' => [
            'savingEntity' => [
                'showitem' => '--palette--;;always, table_name, record_uid, insertion, errors',
            ],
            'mappingError' => [
                'showitem' => '--palette--;;always, errors',
            ],
        ],
    ];
})(Extension::EXTENSION_KEY, 'tx_thuecat_import_log_entry');
