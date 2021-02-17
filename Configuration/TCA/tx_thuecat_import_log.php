<?php

defined('TYPO3') or die();

return (static function (string $extensionKey, string $tableName) {
    $languagePath = \WerkraumMedia\ThueCat\Extension::getLanguagePath() . 'locallang_tca.xlf:' . $tableName;
    $flexFormConfigurationPath = 'FILE:EXT:' . \WerkraumMedia\ThueCat\Extension::EXTENSION_KEY . '/Configuration/FlexForm/';

    return [
        'ctrl' => [
            'label' => 'crdate',
            'iconfile' => \WerkraumMedia\ThueCat\Extension::getIconPath() . $tableName . '.svg',
            'default_sortby' => 'crdate',
            'tstamp' => 'tstamp',
            'crdate' => 'crdate',
            'cruser_id' => 'cruser_id',
            'title' => $languagePath,
            'enablecolumns' => [
                'disabled' => 'disable',
            ],
            'rootLevel' => 1,
        ],
        'columns' => [
            'configuration' => [
                'label' => $languagePath . '.configuration',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'foreign_table' => 'tx_thuecat_import_configuration',
                    'readOnly' => true,
                ],
            ],
            'log_entries' => [
                'label' => $languagePath . '.log_entries',
                'config' => [
                    'type' => 'inline',
                    'foreign_table' => 'tx_thuecat_import_log_entry',
                    'foreign_field' => 'import_log',
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
                'showitem' => 'crdate, log_entries, configuration',
            ],
        ],
    ];
})(\WerkraumMedia\ThueCat\Extension::EXTENSION_KEY, 'tx_thuecat_import_log');
