<?php

defined('TYPO3') or die();

\WerkraumMedia\ThueCat\Extension::registerConfig();

(static function (string $extensionKey) {
    TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScriptSetup(
        '@import "EXT:' . $extensionKey . '/Configuration/TypoScript/Default/Setup.typoscript"'
    );

    $tablesForCleanup = [
        'tx_thuecat_import_log',
        'tx_thuecat_import_log_entry',
    ];

    foreach ($tablesForCleanup as $tableName) {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\TYPO3\CMS\Scheduler\Task\TableGarbageCollectionTask::class]['options']['tables'][$tableName] = [
            'dateField' => 'crdate',
            'expirePeriod' => '180',
        ];
    }
})(\WerkraumMedia\ThueCat\Extension::EXTENSION_KEY);
