<?php

declare(strict_types=1);

return [
    'tx_events_domain_model_location' => [
        0 => [
            'uid' => '1',
            'pid' => '11',
            'remote_id' => 'https://int.thuecat.org/resources/e_101155560-hubev#location',
            'name' => 'Schillerhaus Rudolstadt',
            'street' => 'Schillerstraße 25',
            'zip' => '07407',
            'city' => 'Rudolstadt',
            'phone' => '+49 3672 486470',
            'latitude' => '50.7212805',
            'longitude' => '11.3351741',
        ],
    ],
    'tx_events_domain_model_event' => [
        0 => [
            'uid' => '1',
            'pid' => '11',
            'remote_id' => 'https://int.thuecat.org/resources/e_101155560-hubev',
            'global_id' => 'https://int.thuecat.org/resources/e_101155560-hubev',
            'title' => 'Museumstag. Entdeckertag.',
            'source_name' => 'thuecat',
            'source_url' => 'https://int.thuecat.org/resources/e_101155560-hubev',
            'location' => '1',
        ],
    ],
    'tx_thuecat_import_log' => [
        0 => [
            'uid' => '1',
            'pid' => '0',
            'configuration' => '1',
        ],
    ],
    'tx_thuecat_import_log_entry' => [
        0 => [
            'uid' => '1',
            'pid' => '0',
            'import_log' => '1',
            'record_uid' => '1',
            'table_name' => 'tx_events_domain_model_event',
            'insertion' => '1',
            'errors' => '[]',
        ],
    ],
];
