<?php

declare(strict_types=1);

// Two sites as in TwoSitesPreState, plus a town and its organisation already
// imported into site 4000. Both carry deliberately stale titles: if a site-5000
// import writes into them, the titles change and the foreign record is proven
// to have been touched.
/** @var array<string, list<array<string, string>>> $preState */
$preState = require __DIR__ . '/TwoSitesPreState.php';

$preState['tx_thuecat_town'] = [
    [
        'uid' => '1',
        'pid' => '4010',
        'remote_id' => 'https://thuecat.org/resources/043064193523-jcyt',
        'title' => 'Stale title of the first site',
        'managed_by' => '1',
    ],
];

$preState['tx_thuecat_organisation'] = [
    [
        'uid' => '1',
        'pid' => '4010',
        'remote_id' => 'https://thuecat.org/resources/018132452787-ngbe',
        'title' => 'Stale organisation of the first site',
    ],
];

return $preState;
