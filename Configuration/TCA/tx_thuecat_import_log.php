<?php

declare(strict_types=1);

defined('TYPO3') || die();

return (static function (string $extensionKey, string $tableName): array {
    $languagePath = 'LLL:EXT:thuecat/Resources/Private/Language/locallang_tca.xlf:' . $tableName;

    return [
        'ctrl' => [
            'label' => 'crdate',
            'label_alt' => 'configuration',
            'label_alt_force' => true,
            'iconfile' => 'EXT:thuecat/Resources/Public/Icons/' . $tableName . '.svg',
            'default_sortby' => 'crdate desc',
            'tstamp' => 'tstamp',
            'crdate' => 'crdate',
            'delete' => 'deleted',
            'title' => $languagePath,
            'enablecolumns' => [
                'disabled' => 'disable',
            ],
            'security' => [
                'ignoreRootLevelRestriction' => true,
            ],
            'rootLevel' => 1,
            'hideTable' => true,
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
})('thuecat', 'tx_thuecat_import_log');
