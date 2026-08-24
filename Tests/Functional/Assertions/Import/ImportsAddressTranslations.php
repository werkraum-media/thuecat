<?php

declare(strict_types=1);

return [
    // Each translation carries its own values. fr is configured on the site
    // but absent from the source, so it gets no row.
    'tx_thuecat_address' => [
        [
            'pid' => '10',
            'sys_language_uid' => '0',
            'parenttable' => 'tx_thuecat_tourist_attraction',
            'remote_id' => 'https://thuecat.org/resources/900000000001-goet::addr::0',
            'street' => 'Beispielweg 5',
            'zip' => '99425',
            'city' => 'Beispielstadt',
            'email' => 'info@example.com',
            'phone' => '+49 3643 545400',
            // l10n_mode=exclude: same place in every language.
            'latitude' => '50.974722',
            'longitude' => '11.331389',
        ],
        [
            'pid' => '10',
            'sys_language_uid' => '1',
            'parenttable' => 'tx_thuecat_tourist_attraction',
            'street' => 'Example Lane 5',
            'zip' => '99423',
            'city' => 'Beispielstadt',
            'latitude' => '50.974722',
            'longitude' => '11.331389',
        ],
    ],
];
