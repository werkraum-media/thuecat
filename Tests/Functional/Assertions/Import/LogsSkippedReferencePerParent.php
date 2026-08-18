<?php

declare(strict_types=1);

// One skip row per owning parent, not per URL.
return [
    'tx_thuecat_import_log_entry' => [
        0 => [
            'uid' => '1',
            'type' => 'effectiveSettings',
            'severity' => 'debug',
            'table_name' => '',
            'record_uid' => '0',
        ],
        1 => [
            'uid' => '2',
            'type' => 'savingEntity',
            'severity' => 'info',
            'table_name' => 'tx_thuecat_tourist_attraction',
            'record_uid' => '1',
        ],
        2 => [
            'uid' => '3',
            'type' => 'savingEntity',
            'severity' => 'info',
            'table_name' => 'tx_thuecat_tourist_attraction',
            'record_uid' => '2',
        ],
        3 => [
            'uid' => '4',
            'type' => 'referenceSkipped',
            'severity' => 'warning',
            'table_name' => 'tx_thuecat_tourist_attraction',
            'remote_id' => 'https://thuecat.org/resources/attraction-with-single-slogan',
            'record_uid' => '0',
            'message' => 'Skipped reference "https://thuecat.org/resources/018132452787-ngbe" for field "managed_by": WerkraumMedia\ThueCat\Import\Importer\FetchData\ResourceNotFoundException: Not found, given resource could not be found: "https://thuecat.org/resources/018132452787-ngbe?format=jsonld".',
        ],
        4 => [
            'uid' => '5',
            'type' => 'referenceSkipped',
            'severity' => 'warning',
            'table_name' => 'tx_thuecat_tourist_attraction',
            'remote_id' => 'https://thuecat.org/resources/attraction-with-slogan-array',
            'record_uid' => '0',
            'message' => 'Skipped reference "https://thuecat.org/resources/018132452787-ngbe" for field "managed_by": WerkraumMedia\ThueCat\Import\Importer\FetchData\ResourceNotFoundException: Not found, given resource could not be found: "https://thuecat.org/resources/018132452787-ngbe?format=jsonld".',
        ],
    ],
];
