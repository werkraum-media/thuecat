<?php

defined('TYPO3') or die();

return (static function (string $extensionKey, string $tableName) {
    $languagePath = \WerkraumMedia\ThueCat\Extension::getLanguagePath() . 'locallang_tca.xlf:' . $tableName;
    $flexFormConfigurationPath = 'FILE:EXT:' . \WerkraumMedia\ThueCat\Extension::EXTENSION_KEY . '/Configuration/FlexForm/';

    return [
        'ctrl' => [
            'label' => 'type',
            'label_alt' => 'remote_id, table_name, record_uid',
            'label_alt_force' => true,
            'iconfile' => \WerkraumMedia\ThueCat\Extension::getIconPath() . $tableName . '.svg',
            'type' => 'type',
            'default_sortby' => 'crdate',
            'tstamp' => 'tstamp',
            'crdate' => 'crdate',
            'cruser_id' => 'cruser_id',
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
                            $languagePath . '.type.savingEntity',
                            'savingEntity',
                        ],
                        [
                            $languagePath . '.type.mappingError',
                            'mappingError',
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
                            0 => '',
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
                    'type' => 'text',
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
                    'type' => 'input',
                    'renderType' => 'inputDateTime',
                    'eval' => 'datetime',
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
})(\WerkraumMedia\ThueCat\Extension::EXTENSION_KEY, 'tx_thuecat_import_log_entry');
