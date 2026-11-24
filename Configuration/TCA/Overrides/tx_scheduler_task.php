<?php

declare(strict_types=1);

use TYPO3\CMS\Scheduler\Task\TableGarbageCollectionTask;

defined('TYPO3') || die();

foreach (['tx_thuecat_import_log', 'tx_thuecat_import_log_entry', 'tx_thuecat_removal_job'] as $tableName) {
    // @phpstan-ignore offsetAccess.nonOffsetAccessible, offsetAccess.nonOffsetAccessible, offsetAccess.nonOffsetAccessible, offsetAccess.nonOffsetAccessible, offsetAccess.nonOffsetAccessible, offsetAccess.nonOffsetAccessible
    $GLOBALS['TCA']['tx_scheduler_task']['types'][TableGarbageCollectionTask::class]['taskOptions']['tables'][$tableName] = [
        'dateField' => 'crdate',
        'expirePeriod' => 180,
    ];
}
