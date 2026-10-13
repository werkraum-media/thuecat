<?php

declare(strict_types=1);

use TYPO3\CMS\Scheduler\Task\TableGarbageCollectionTask;

defined('TYPO3') or die();

// The scheduler is optional.
// @phpstan-ignore offsetAccess.nonOffsetAccessible
if (isset($GLOBALS['TCA']['tx_scheduler_task'])) {
    foreach (['tx_thuecat_import_log', 'tx_thuecat_import_log_entry'] as $tableName) {
        // @phpstan-ignore offsetAccess.nonOffsetAccessible, offsetAccess.nonOffsetAccessible, offsetAccess.nonOffsetAccessible, offsetAccess.nonOffsetAccessible, offsetAccess.nonOffsetAccessible
        $GLOBALS['TCA']['tx_scheduler_task']['types'][TableGarbageCollectionTask::class]['taskOptions']['tables'][$tableName] = [
            'dateField' => 'crdate',
            'expirePeriod' => 180,
        ];
    }
}
