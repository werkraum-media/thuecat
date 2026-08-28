<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use TYPO3\CMS\Scheduler\Task\TableGarbageCollectionTask;
use WerkraumMedia\ThueCat\Controller\TouristAttractionController;
use WerkraumMedia\ThueCat\Controller\TrailController;
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

    // Non-cacheable: the plugin's own caches serve the rendered result.
    ExtensionUtility::registerControllerActions(
        'ThueCat',
        'TouristAttractionList',
        [TouristAttractionController::class => ['list']],
        [TouristAttractionController::class => ['list']]
    );
    ExtensionUtility::registerControllerActions(
        'ThueCat',
        'TouristAttractionShow',
        [TouristAttractionController::class => ['show']],
        []
    );
    ExtensionUtility::registerControllerActions(
        'ThueCat',
        'TouristAttractionSearch',
        [TouristAttractionController::class => ['searchForm']],
        [TouristAttractionController::class => ['searchForm']]
    );
    ExtensionUtility::registerControllerActions(
        'ThueCat',
        'TouristAttractionListSelected',
        [TouristAttractionController::class => ['selectedList']],
        []
    );
    ExtensionUtility::registerControllerActions(
        'ThueCat',
        'TrailListSelected',
        [TrailController::class => ['selectedList']],
        []
    );
    ExtensionUtility::registerControllerActions(
        'ThueCat',
        'TrailShow',
        [TrailController::class => ['show']],
        []
    );

    // Both actions are USER_INT, so these never reach a cache identifier.
    // Excluding them waives only the cHash validation, which would otherwise 404
    // every filter and pagination URL; Extbase maps them into a typed demand and
    // listAction re-forces locked filters.
    $GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'][] = '^tx_thuecat_touristattractionlist[demand]';
    $GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'][] = '^tx_thuecat_touristattractionlist[currentPage]';
    $GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'][] = '^tx_thuecat_touristattractionsearch[demand]';
})(Extension::EXTENSION_KEY);
