<?php

declare(strict_types=1);

/*
 * Copyright (C) 2024 werkraum-media
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301, USA.
 */

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

return [
    'pages' => [
        0 => [
            'uid' => '1',
            'pid' => '0',
            'tstamp' => '1613400587',
            'crdate' => '1613400558',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Rootpage',
            'is_siteroot' => '1',
        ],
        1 => [
            'uid' => '10',
            'pid' => '1',
            'tstamp' => '1613400587',
            'crdate' => '1613400558',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'title' => 'Storage folder',
        ],
    ],
    'tx_thuecat_town' => [
        0 => [
            'uid' => '1',
            'pid' => '10',
            'sys_language_uid' => '0',
            'l10n_parent' => '0',
            'tstamp' => '1613400587',
            'crdate' => '1613400558',
            'disable' => '0',
            'remote_id' => 'https://thuecat.org/resources/town-translated',
            'title' => 'Old DE title',
            'description' => 'Alte deutsche Beschreibung.',
        ],
        1 => [
            'uid' => '2',
            'pid' => '10',
            'sys_language_uid' => '1',
            'l10n_parent' => '1',
            'tstamp' => '1613400587',
            'crdate' => '1613400558',
            'disable' => '0',
            'remote_id' => 'https://thuecat.org/resources/town-translated',
            'title' => 'Old EN title',
            'description' => 'Old EN description.',
        ],
        2 => [
            'uid' => '3',
            'pid' => '10',
            'sys_language_uid' => '2',
            'l10n_parent' => '1',
            'tstamp' => '1613400587',
            'crdate' => '1613400558',
            'disable' => '0',
            'remote_id' => 'https://thuecat.org/resources/town-translated',
            'title' => 'Old FR title',
        ],
    ],
];