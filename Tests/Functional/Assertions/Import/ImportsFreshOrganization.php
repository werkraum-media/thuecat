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
    'tx_thuecat_organisation' => [
        0 => [
            'uid' => '1',
            'pid' => '11',
            'remote_id' => 'https://thuecat.org/resources/018132452787-ngbe',
            'title' => 'Erfurt Tourismus und Marketing GmbH',
        ],
    ],
    'tx_thuecat_import_log' => [
        0 => [
            'uid' => '1',
            'pid' => '0',
            'configuration' => '1',
        ],
    ],
    'tx_thuecat_import_log_entry' => [
        0 => [
            'uid' => '1',
            'pid' => '0',
            'type' => 'effectiveSettings',
            'import_log' => '1',
            'record_uid' => '0',
            'table_name' => '',
            'insertion' => '0',
            'errors' => '[]',
        ],
        1 => [
            'uid' => '2',
            'pid' => '0',
            'import_log' => '1',
            'record_uid' => '1',
            'table_name' => 'tx_thuecat_organisation',
            'insertion' => '1',
            'errors' => '[]',
        ],
        2 => [
            'uid' => '3',
            'pid' => '0',
            'import_log' => '1',
            'record_uid' => '1',
            'table_name' => 'tx_thuecat_address',
            'insertion' => '1',
            'errors' => '[]',
        ],
    ],
];
