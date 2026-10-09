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
    'tx_thuecat_import_log_entry' => [
        0 => [
            'uid' => '1',
            'type' => 'effectiveSettings',
            'severity' => 'debug',
            'table_name' => '',
            'record_uid' => '0',
        ],
        1 => [
            'uid' => '2',
            'type' => 'savingEntity',
            'severity' => 'info',
            'table_name' => 'tx_thuecat_tourist_attraction',
            'record_uid' => '1',
        ],
        2 => [
            'uid' => '3',
            'type' => 'savingEntity',
            'severity' => 'info',
            'table_name' => 'tx_thuecat_tourist_attraction',
            'record_uid' => '2',
        ],
        3 => [
            'uid' => '4',
            'type' => 'referenceSkipped',
            'severity' => 'warning',
            'table_name' => 'tx_thuecat_tourist_attraction',
            'remote_id' => 'https://thuecat.org/resources/attraction-with-single-slogan',
            'record_uid' => '0',
            'message' => 'Skipped reference "https://thuecat.org/resources/018132452787-ngbe" for field "managed_by": WerkraumMedia\ThueCat\Import\Importer\FetchData\ResourceNotFoundException: Not found, given resource could not be found: "https://thuecat.org/resources/018132452787-ngbe?format=jsonld".',
        ],
        4 => [
            'uid' => '5',
            'type' => 'referenceSkipped',
            'severity' => 'warning',
            'table_name' => 'tx_thuecat_tourist_attraction',
            'remote_id' => 'https://thuecat.org/resources/attraction-with-slogan-array',
            'record_uid' => '0',
            'message' => 'Skipped reference "https://thuecat.org/resources/018132452787-ngbe" for field "managed_by": WerkraumMedia\ThueCat\Import\Importer\FetchData\ResourceNotFoundException: Not found, given resource could not be found: "https://thuecat.org/resources/018132452787-ngbe?format=jsonld".',
        ],
    ],
];
