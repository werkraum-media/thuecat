<?php

defined('TYPO3') or die();

return (static function (string $extensionKey, string $tableName) {
    $languagePath = \WerkraumMedia\ThueCat\Extension::getLanguagePath() . 'locallang_tca.xlf:' . $tableName;
    $flexFormConfigurationPath = 'FILE:EXT:' . \WerkraumMedia\ThueCat\Extension::EXTENSION_KEY . '/Configuration/FlexForm/';

    return [
        'ctrl' => [
            'label' => 'table_name',
            'label_alt' => 'record_uid',
            'label_alt_force' => true,
            'default_sortby' => 'title',
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
        'types' => [
            '0' => [
                'showitem' => 'table_name, record_uid, insertion, errors, import_log, crdate',
            ],
        ],
    ];
})(\WerkraumMedia\ThueCat\Extension::EXTENSION_KEY, 'tx_thuecat_import_log_entry');
