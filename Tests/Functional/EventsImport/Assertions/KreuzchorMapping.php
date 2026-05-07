<?php

declare(strict_types=1);

// Expected EventEntity::toArray() + getDates() for the e_19542-hubev
// (Kreuzchor) fixture. v1: bare event row + per-occurrence dates.
//
// Schedule: single occurrence, startTime=2026-11-29T18:00+01:00, no endTime.
// Per the FALLBACK POLICY in EventScheduleAdapter, end mirrors start when
// endTime is absent.

return [
    'event' => [
        'source_name' => 'thuecat',
        'source_url' => 'https://cdb.thuecat.org',
        'remote_id' => 'https://int.thuecat.org/resources/e_19542-hubev',
        'title' => 'Konzert des Dresdner Kreuzchores',
        'details' => '<p>Der Dresdner Kreuzchor ist einer der ältesten und berühmtesten Knabenchöre der Welt.</p>',
        'web' => 'http://www.kirchengemeinde-gotha.de/',
    ],
    'dates' => [
        [
            'start' => '2026-11-29T18:00:00+01:00',
            'end' => '2026-11-29T18:00:00+01:00',
            'canceled' => 'no',
        ],
    ],
];
