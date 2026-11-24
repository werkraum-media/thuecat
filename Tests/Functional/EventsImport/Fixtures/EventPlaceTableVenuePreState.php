<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

return [
    'pages' => [
        0 => [
            'uid' => '2000',
            'pid' => '0',
            'tstamp' => 1613400587,
            'crdate' => 1613400558,
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Rootpage',
            'is_siteroot' => '1',
        ],
        1 => [
            'uid' => '10',
            'pid' => '2000',
            'tstamp' => 1613400587,
            'crdate' => 1613400558,
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'title' => 'Storage folder',
        ],
    ],
    'tx_thuecat_place' => [
        0 => [
            'uid' => '1',
            'pid' => '10',
            'sys_language_uid' => '0',
            'remote_id' => 'https://thuecat.org/resources/900000000060-shpl',
            'title' => 'Bauhaus Museum Weimar',
            'address_inline' => '1',
        ],
    ],
    'tx_thuecat_address' => [
        0 => [
            'uid' => '1',
            'pid' => '10',
            'sys_language_uid' => '0',
            'parentid' => '1',
            'parenttable' => 'tx_thuecat_place',
            'remote_id' => 'https://thuecat.org/resources/900000000060-shpl::addr::0',
            'street' => 'Stéphane-Hessel-Platz 1',
            'zip' => '99423',
            'city' => 'Weimar',
        ],
    ],
    'tx_thuecat_import_configuration' => [
        0 => [
            'uid' => '1',
            'pid' => '0',
            'tstamp' => 1613400587,
            'crdate' => 1613400558,
            'disable' => '0',
            'title' => 'Event place matching import',
            'type' => 'static',
            'storage_pid' => '10',
        ],
    ],
    'tx_thuecat_import_configuration_url' => [
        [
            'uid' => '101',
            'pid' => '0',
            'configuration' => '1',
            'sorting' => '1',
            'title' => '',
            'url' => 'https://cdb.int.thuecat.org/api/resources/e_bauhaus-hubev',
        ],
    ],
];
