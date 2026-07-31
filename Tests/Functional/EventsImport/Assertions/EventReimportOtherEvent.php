<?php

declare(strict_types=1);

// Post-state: the run imported only the first event. Every date of the second
// event survives, including one that would look stale if the reap were not
// scoped to the events the run carries.
return [
    'tx_events_domain_model_date' => [
        0 => [
            'uid' => '1',
            'remote_id' => 'https://int.thuecat.org/resources/e_19542-hubev::date::2026-11-29T18:00:00+01:00',
            'event' => '1',
            'deleted' => '0',
        ],
        1 => [
            'uid' => '2',
            'remote_id' => 'https://int.thuecat.org/resources/e_not-in-this-run::date::2026-10-04T18:00:00+02:00',
            'event' => '2',
            'deleted' => '0',
        ],
        2 => [
            'uid' => '3',
            'remote_id' => 'https://int.thuecat.org/resources/e_not-in-this-run::date::2026-10-11T18:00:00+02:00',
            'event' => '2',
            'deleted' => '0',
        ],
    ],
];
