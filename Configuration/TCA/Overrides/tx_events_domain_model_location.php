<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use WerkraumMedia\ThueCat\Extension;

(static function (string $extensionKey, string $tableName) {
    $languagePath = Extension::getLanguagePath()
        . 'locallang_tca.xlf:' . $tableName;
    ExtensionManagementUtility::addTCAcolumns(
        'tx_events_domain_model_location',
        [
            'remote_id' => [
                'label' => $languagePath . '.remote_id',
                'config' => [
                    'type' => 'input',
                    'readOnly' => true,
                    'searchable' => false,
                ],
            ],
        ]
    );
})(Extension::EXTENSION_KEY, 'tx_events_domain_model_location');
