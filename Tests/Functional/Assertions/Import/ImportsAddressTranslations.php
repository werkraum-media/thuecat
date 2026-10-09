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

return [
    // Each translation carries its own values. fr is configured on the site
    // but absent from the source, so it gets no row.
    'tx_thuecat_address' => [
        [
            'pid' => '10',
            'sys_language_uid' => '0',
            'parenttable' => 'tx_thuecat_tourist_attraction',
            'remote_id' => 'https://thuecat.org/resources/900000000001-goet::addr::0',
            'street' => 'Beispielweg 5',
            'zip' => '99425',
            'city' => 'Beispielstadt',
            'email' => 'info@example.com',
            'phone' => '+49 3643 545400',
            // l10n_mode=exclude: same place in every language.
            'latitude' => '50.974722',
            'longitude' => '11.331389',
        ],
        [
            'pid' => '10',
            'sys_language_uid' => '1',
            'parenttable' => 'tx_thuecat_tourist_attraction',
            'street' => 'Example Lane 5',
            'zip' => '99423',
            'city' => 'Beispielstadt',
            'latitude' => '50.974722',
            'longitude' => '11.331389',
        ],
    ],
];
