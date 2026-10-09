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
    'tx_thuecat_tourist_attraction' => [
        0 => [
            'uid' => '1',
            'pid' => '10',
            'sys_language_uid' => '0',
            'remote_id' => 'https://thuecat.org/resources/165868194223-zmqf',
            'title' => 'Alte Synagoge',
        ],
        1 => [
            'uid' => '2',
            'pid' => '10',
            'sys_language_uid' => '1',
            'remote_id' => 'https://thuecat.org/resources/165868194223-zmqf',
            'title' => 'Old Synagogue',
        ],
        2 => [
            'uid' => '3',
            'pid' => '10',
            'sys_language_uid' => '2',
            'remote_id' => 'https://thuecat.org/resources/165868194223-zmqf',
            'title' => 'La vieille synagogue',
        ],
    ],
    'tx_thuecat_import_log' => [
        0 => [
            'uid' => '1',
            'pid' => '0',
            'configuration' => '1',
            'log_entries' => '0',
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
            'type' => 'mappingError',
            'import_log' => '1',
            'record_uid' => '0',
            'table_name' => '',
            'insertion' => '0',
            'errors' => '["Could not map incoming JSON-LD to target object: Failed to parse time string (18:00: 00) at position 5 (:): Unexpected character"]',
        ],
        2 => [
            'uid' => '3',
            'pid' => '0',
            'type' => 'mappingError',
            'import_log' => '1',
            'record_uid' => '0',
            'table_name' => '',
            'insertion' => '0',
            'errors' => '["Could not map incoming JSON-LD to target object: Failed to parse time string (18:00: 00) at position 5 (:): Unexpected character"]',
        ],
        3 => [
            'uid' => '4',
            'pid' => '0',
            'type' => 'mappingError',
            'import_log' => '1',
            'record_uid' => '0',
            'table_name' => '',
            'insertion' => '0',
            'errors' => '["Could not map incoming JSON-LD to target object: Failed to parse time string (18:00: 00) at position 5 (:): Unexpected character"]',
        ],
        4 => [
            'uid' => '5',
            'pid' => '0',
            'type' => 'savingEntity',
            'import_log' => '1',
            'record_uid' => '1',
            'table_name' => 'tx_thuecat_organisation',
            'insertion' => '1',
            'errors' => '[]',
        ],
        5 => [
            'uid' => '6',
            'pid' => '0',
            'type' => 'savingEntity',
            'import_log' => '1',
            'record_uid' => '1',
            'table_name' => 'tx_thuecat_town',
            'insertion' => '0',
            'errors' => '[]',
        ],
        6 => [
            'uid' => '7',
            'pid' => '0',
            'type' => 'savingEntity',
            'import_log' => '1',
            'record_uid' => '1',
            'table_name' => 'tx_thuecat_tourist_attraction',
            'insertion' => '1',
            'errors' => '[]',
        ],
        7 => [
            'uid' => '8',
            'pid' => '0',
            'type' => 'savingEntity',
            'import_log' => '1',
            'record_uid' => '2',
            'table_name' => 'tx_thuecat_tourist_attraction',
            'insertion' => '0',
            'errors' => '[]',
        ],
        8 => [
            'uid' => '9',
            'pid' => '0',
            'type' => 'savingEntity',
            'import_log' => '1',
            'record_uid' => '3',
            'table_name' => 'tx_thuecat_tourist_attraction',
            'insertion' => '0',
            'errors' => '[]',
        ],
    ],
];
