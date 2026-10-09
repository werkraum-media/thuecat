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

// row is deleted while the rest of the series keeps its uids.
return [
    'tx_events_domain_model_date' => [
        0 => [
            'uid' => '1',
            'event' => '1',
            'deleted' => '0',
        ],
        1 => [
            'uid' => '2',
            'event' => '1',
            'deleted' => '0',
        ],
        2 => [
            'uid' => '3',
            'deleted' => '1',
        ],
        3 => [
            'uid' => '4',
            'event' => '1',
            'deleted' => '0',
        ],
        4 => [
            'uid' => '5',
            'event' => '1',
            'deleted' => '0',
        ],
    ],
];
