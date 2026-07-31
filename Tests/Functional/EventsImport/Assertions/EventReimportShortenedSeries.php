<?php

declare(strict_types=1);

// Post-state: the series end was pulled forward, so every occurrence after it
// is deleted while the ones still within the series keep their uids.
return [
    'tx_events_domain_model_date' => [
        0 => [
            'uid' => '1',
            'event' => '1',
            'deleted' => '0',
        ],
        1 => [
            'uid' => '2',
            'event' => '1',
            'deleted' => '0',
        ],
        2 => [
            'uid' => '3',
            'deleted' => '1',
        ],
        3 => [
            'uid' => '4',
            'deleted' => '1',
        ],
        4 => [
            'uid' => '5',
            'deleted' => '1',
        ],
    ],
];
