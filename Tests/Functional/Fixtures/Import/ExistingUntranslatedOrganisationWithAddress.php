<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

/**
 * Organisation and its address stored in the default language only.
 */
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
    'tx_thuecat_organisation' => [
        0 => [
            'uid' => '7',
            'pid' => '10',
            'sys_language_uid' => '0',
            'remote_id' => 'https://thuecat.org/resources/018132452787-ngbe',
            'title' => 'Erfurt Tourismus und Marketing GmbH',
            'address_inline' => '1',
        ],
    ],
    'tx_thuecat_address' => [
        0 => [
            'uid' => '1',
            'pid' => '10',
            'sys_language_uid' => '0',
            'parentid' => '7',
            'parenttable' => 'tx_thuecat_organisation',
            'remote_id' => 'https://thuecat.org/resources/018132452787-ngbe::addr::0',
            'street' => 'Benediktsplatz 1',
            'zip' => '99084',
            'city' => 'Erfurt',
        ],
    ],
];
