<?php

declare(strict_types=1);

// Post-state after re-importing with a stale date present. The occurrence the
// schedule still produces is kept, the stale import-owned one is deleted, and
// the backend-created row without a remote_id survives untouched.
return [
    'tx_events_domain_model_event' => [
        0 => [
            'uid' => '1',
            'pid' => '10',
            'remote_id' => 'https://int.thuecat.org/resources/e_19542-hubev',
            'title' => 'Konzert des Dresdner Kreuzchores',
        ],
    ],
    'tx_events_domain_model_date' => [
        0 => [
            'uid' => '1',
            'remote_id' => 'https://int.thuecat.org/resources/e_19542-hubev::date::2026-11-29T18:00:00+01:00',
            'event' => '1',
            'deleted' => '0',
        ],
        1 => [
            'uid' => '2',
            'deleted' => '1',
        ],
        2 => [
            'uid' => '3',
            'remote_id' => '',
            'event' => '1',
            'deleted' => '0',
        ],
    ],
];
