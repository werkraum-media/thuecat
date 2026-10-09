<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS extension "thuecat".
 *
 * Copyright (C) werkraum-media <https://werkraum-media.de/>
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * For the full license text, see the LICENSE file distributed with this
 * extension.
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 */

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
        'location' => '3485b8186a975185aa95bd3ba139c4616502c6808ed0385e2708b528413a12c1',
        'organizer' => 'thuecat:organizer:46f6db2968c633395d9cf3c7691033a4bce1ef19c0729539bb08dfa65264803f',
    ],
    'dates' => [
        [
            'start' => '2026-11-29T18:00:00+01:00',
            'end' => '2026-11-29T18:00:00+01:00',
            'canceled' => 'no',
        ],
    ],
    // @type: schema:Thing, schema:Event, thuecat:CultureEvent, dcmitype:Event,
    // ttgds:Event — only thuecat:CultureEvent maps. remoteId carries the 'type:'
    // source prefix.
    'categories' => [
        ['field' => 'categories', 'remoteId' => 'type:thuecat:CultureEvent', 'title' => 'Kulturveranstaltung'],
    ],
];
