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

// linked back via the `event` FK. The Date's remote_id is the synthetic
// '<eventRemoteId>::date::<startISO>' pattern.

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
            'pid' => '10',
            'remote_id' => 'https://int.thuecat.org/resources/e_19542-hubev::date::2026-11-29T18:00:00+01:00',
            'event' => '1',
            // tx_events_domain_model_date.start/end are TCA type: datetime
            // → stored as Unix timestamps. The Importer hands DataHandler an
            // ISO-8601 string; DataHandler converts to int on the way in.
            'start' => 1795971600, // 2026-11-29T18:00:00+01:00
            'end' => 1795971600,
            'canceled' => 'no',
        ],
    ],
];
