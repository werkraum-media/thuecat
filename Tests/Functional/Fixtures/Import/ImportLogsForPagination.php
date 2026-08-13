<?php

declare(strict_types=1);

/*
 * Twelve import logs, crdate ascending with uid, so ORDER BY crdate DESC
 * puts uid 12 first and pagination boundaries are unambiguous.
 */

$logs = [];
for ($uid = 1; $uid <= 12; ++$uid) {
    $logs[] = [
        'uid' => (string)$uid,
        'pid' => '0',
        'crdate' => (string)(1613400000 + $uid),
        'tstamp' => (string)(1613400000 + $uid),
        'configuration' => '0',
    ];
}

return [
    'tx_thuecat_import_log' => $logs,
];
