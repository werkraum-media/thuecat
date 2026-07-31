<?php

declare(strict_types=1);

// Expected EventEntity::toArray() + getDates() for the e_7cbe5bb1-tdm fixture.
//
// Weekly schedule whose byDay mixes a weekday with a value that cannot seed a
// series, and which excepts one date: only the weekday expands, minus the
// excepted occurrence.

return [
    'event' => [
        'source_name' => 'thuecat',
        'source_url' => 'https://cdb.thuecat.org',
        'remote_id' => 'https://thuecat.org/resources/e_7cbe5bb1-160b-4916-802c-c64dd2f1bf9e-tdm',
        'title' => 'Test-Altstadtführung',
        'web' => 'www.erfurt-tourismus.de',
        'ticket' => 'https://erfurt-booking.inet-mainz.de/ot/formular',
    ],
    'dates' => [
        [
            'start' => '2026-12-03T14:00:00+01:00',
            'end' => '2026-12-03T16:00:00+01:00',
            'canceled' => 'no',
        ],
        [
            'start' => '2026-12-10T14:00:00+01:00',
            'end' => '2026-12-10T16:00:00+01:00',
            'canceled' => 'no',
        ],
        [
            'start' => '2026-12-24T14:00:00+01:00',
            'end' => '2026-12-24T16:00:00+01:00',
            'canceled' => 'no',
        ],
        [
            'start' => '2026-12-31T14:00:00+01:00',
            'end' => '2026-12-31T16:00:00+01:00',
            'canceled' => 'no',
        ],
    ],
    'categories' => [
        ['field' => 'categories', 'remoteId' => 'type:thuecat:GuidedTourEvent', 'title' => 'Führung und Stadtrundgang'],
    ],
];
