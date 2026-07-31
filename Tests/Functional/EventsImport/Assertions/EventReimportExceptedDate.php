<?php

declare(strict_types=1);

// Post-state: the schedule now excepts one of the stored occurrences, so that
// row is deleted while the rest of the series keeps its uids.
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
            'event' => '1',
            'deleted' => '0',
        ],
        4 => [
            'uid' => '5',
            'event' => '1',
            'deleted' => '0',
        ],
    ],
];
