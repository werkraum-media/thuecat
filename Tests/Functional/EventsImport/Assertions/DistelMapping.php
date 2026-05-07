<?php

declare(strict_types=1);

// Expected EventEntity::toArray() + getDates() for the e_100771372-hubev
// (Distel-Comedy) fixture. Three schemas in the JSON-LD: two single
// occurrences (b18, b20) and one Monthly recurring (b5, 4th Sunday until
// 2026-12-27). DatesFactory expands the recurring block into 4 rows
// (Sep–Dec 2026). DST: Europe/Berlin switches Oct 25 03:00, so Sep is +02:00
// and Oct/Nov/Dec are +01:00 (the 19:00 occurrence on Oct 25 is past the
// switch, hence already in CET).
//
// Output order mirrors the JSON-LD eventSchedule list order: singles first
// (in fixture order), then the recurring block expanded chronologically.

return [
    'event' => [
        'source_name' => 'thuecat',
        'source_url' => 'https://cdb.thuecat.org',
        'remote_id' => 'https://int.thuecat.org/resources/e_100771372-hubev',
        'title' => 'Distel-Comedy. Große Stand-up-Comedy-Show',
        'details' => '<p>Am 28. Juni findet ein CSD-Special statt.</p>',
        'ticket' => 'https://www.eventbrite.de/e/distel-comedy-tickets-1111060414609',
    ],
    'dates' => [
        ['start' => '2026-02-22T19:00:00+01:00', 'end' => '2026-02-22T21:00:00+01:00', 'canceled' => 'no'],
        ['start' => '2026-06-28T17:00:00+02:00', 'end' => '2026-06-28T19:00:00+02:00', 'canceled' => 'no'],
        ['start' => '2026-09-27T19:00:00+02:00', 'end' => '2026-09-27T20:30:00+02:00', 'canceled' => 'no'],
        ['start' => '2026-10-25T19:00:00+01:00', 'end' => '2026-10-25T20:30:00+01:00', 'canceled' => 'no'],
        ['start' => '2026-11-22T19:00:00+01:00', 'end' => '2026-11-22T20:30:00+01:00', 'canceled' => 'no'],
        ['start' => '2026-12-27T19:00:00+01:00', 'end' => '2026-12-27T20:30:00+01:00', 'canceled' => 'no'],
    ],
];
