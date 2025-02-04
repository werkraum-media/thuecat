<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Scheduler\Task\TableGarbageCollectionTask;
use WerkraumMedia\ThueCat\Extension;
use WerkraumMedia\ThueCat\Typo3\Hook\AddTitleForStaticUrlsDataHandlerHook;

defined('TYPO3') or die();

Extension::registerExtLocalconfConfigConfig();

(static function (string $extensionKey) {
    ExtensionManagementUtility::addTypoScriptSetup(
        '@import "EXT:' . $extensionKey . '/Configuration/TypoScript/Default/Setup.typoscript"'
    );

    AddTitleForStaticUrlsDataHandlerHook::register();

    $tablesForCleanup = [
        'tx_thuecat_import_log',
        'tx_thuecat_import_log_entry',
    ];

    foreach ($tablesForCleanup as $tableName) {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][TableGarbageCollectionTask::class]['options']['tables'][$tableName] = [
            'dateField' => 'crdate',
            'expirePeriod' => '180',
        ];
    }
})(Extension::EXTENSION_KEY);
