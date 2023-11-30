<?php

declare(strict_types=1);

use WerkraumMedia\ThueCat\Extension;

defined('TYPO3') or die();

return (static function (string $extensionKey, string $tableName) {
    $languagePath = Extension::getLanguagePath() . 'locallang_tca.xlf:' . $tableName;
    $flexFormConfigurationPath = 'FILE:EXT:' . Extension::EXTENSION_KEY . '/Configuration/FlexForm/';

    return [
        'ctrl' => [
            'label' => 'crdate',
            'label_alt' => 'configuration',
            'label_alt_force' => true,
            'iconfile' => Extension::getIconPath() . $tableName . '.svg',
            'default_sortby' => 'crdate desc',
            'tstamp' => 'tstamp',
            'crdate' => 'crdate',
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
                    'type' => 'datetime',
                    'format' => 'datetime',
                    'readOnly' => true,
                ],
            ],
        ],
        'types' => [
            '0' => [
                'showitem' => 'crdate, configuration, log_entries',
            ],
        ],
    ];
})(Extension::EXTENSION_KEY, 'tx_thuecat_import_log');
