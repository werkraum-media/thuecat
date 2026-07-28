<?php

declare(strict_types=1);

// Both roots reference the same dead org. Each keeps its own record and
// loses only managed_by; no organisation row is created.
return [
    'tx_thuecat_tourist_attraction' => [
        0 => [
            'uid' => '1',
            'pid' => '10',
            'sys_language_uid' => '0',
            'remote_id' => 'https://thuecat.org/resources/attraction-with-single-slogan',
            'title' => 'Attraktion mit single slogan',
            'managed_by' => '0',
        ],
        1 => [
            'uid' => '2',
            'pid' => '10',
            'sys_language_uid' => '0',
            'remote_id' => 'https://thuecat.org/resources/attraction-with-slogan-array',
            'title' => 'Attraktion mit slogan array',
            'managed_by' => '0',
        ],
        2 => [
            'uid' => '3',
            'pid' => '10',
            'sys_language_uid' => '1',
            'l18n_parent' => '1',
            'remote_id' => 'https://thuecat.org/resources/attraction-with-single-slogan',
            'title' => 'Attraction with single slogan',
            'managed_by' => '0',
        ],
        3 => [
            'uid' => '4',
            'pid' => '10',
            'sys_language_uid' => '1',
            'l18n_parent' => '2',
            'remote_id' => 'https://thuecat.org/resources/attraction-with-slogan-array',
            'title' => 'Attraction with slogan array',
            'managed_by' => '0',
        ],
    ],
];
