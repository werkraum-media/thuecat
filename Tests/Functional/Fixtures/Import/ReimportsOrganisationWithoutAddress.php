<?php

declare(strict_types=1);

/** @var array<string, array<int, array<string, mixed>>> $state */
$state = require __DIR__ . '/ImportsPlaceTypeAddresses.php';

// Prior run's rows: the organisation carried an address that upstream no
// longer delivers.
$state['tx_thuecat_organisation'] = [
    0 => [
        'uid' => '1',
        'pid' => '10',
        'sys_language_uid' => '0',
        'remote_id' => 'https://thuecat.org/resources/900000000013-orgnoaddr',
        'title' => 'Organisation ohne Adresse',
        'address_inline' => '1',
    ],
];
$state['tx_thuecat_address'] = [
    0 => [
        'uid' => '1',
        'pid' => '10',
        'sys_language_uid' => '0',
        'parentid' => '1',
        'parenttable' => 'tx_thuecat_organisation',
        'remote_id' => 'https://thuecat.org/resources/900000000013-orgnoaddr::addr::0',
        'street' => 'Benediktsplatz 1',
        'zip' => '99084',
        'city' => 'Erfurt',
    ],
];

return $state;
