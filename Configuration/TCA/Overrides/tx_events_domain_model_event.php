<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use WerkraumMedia\ThueCat\Extension;

(static function (string $extensionKey, string $tableName) {
    $languagePath = Extension::getLanguagePath()
        . 'locallang_tca.xlf:' . $tableName;
    ExtensionManagementUtility::addTCAcolumns(
        $tableName,
        [
            'remote_id' => [
                'label' => $languagePath . '.remote_id',
                'config' => [
                    'type' => 'input',
                    'readOnly' => true,
                    'searchable' => false,
                ],
            ],
            'location' => [
                'label' => $languagePath . '.location',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'foreign_table' => 'tx_events_domain_model_location',
                    'default' => 0,
                    'items' => [
                        ['label' => '', 'value' => 0],
                    ],
                ],
            ],
        ]
    );
})(Extension::EXTENSION_KEY, 'tx_events_domain_model_event');
