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

// loses only managed_by; no organisation row is created.
return [
    'tx_thuecat_tourist_attraction' => [
        0 => [
            'uid' => '1',
            'pid' => '10',
            'sys_language_uid' => '0',
            'remote_id' => 'https://thuecat.org/resources/attraction-with-single-slogan',
            'title' => 'Attraktion mit single slogan',
            'managed_by' => '0',
        ],
        1 => [
            'uid' => '2',
            'pid' => '10',
            'sys_language_uid' => '0',
            'remote_id' => 'https://thuecat.org/resources/attraction-with-slogan-array',
            'title' => 'Attraktion mit slogan array',
            'managed_by' => '0',
        ],
        2 => [
            'uid' => '3',
            'pid' => '10',
            'sys_language_uid' => '1',
            'l18n_parent' => '1',
            'remote_id' => 'https://thuecat.org/resources/attraction-with-single-slogan',
            'title' => 'Attraction with single slogan',
            'managed_by' => '0',
        ],
        3 => [
            'uid' => '4',
            'pid' => '10',
            'sys_language_uid' => '1',
            'l18n_parent' => '2',
            'remote_id' => 'https://thuecat.org/resources/attraction-with-slogan-array',
            'title' => 'Attraction with slogan array',
            'managed_by' => '0',
        ],
    ],
];
