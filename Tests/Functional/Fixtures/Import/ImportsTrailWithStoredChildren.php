<?php

declare(strict_types=1);

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
            'uid' => '11',
            'pid' => '1',
            'tstamp' => '1613400587',
            'crdate' => '1613400558',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'title' => 'Storage folder',
        ],
    ],
    'tx_thuecat_import_configuration' => [
        0 => [
            'uid' => '1',
            'pid' => '0',
            'tstamp' => '1613400587',
            'crdate' => '1613400558',
            'disable' => '0',
            'title' => 'Trail With Stored Children',
            'type' => 'static',
            'storage_pid' => '11',
        ],
    ],
    'tx_thuecat_trail' => [
        0 => [
            'uid' => '1',
            'pid' => '11',
            'remote_id' => 'https://thuecat.org/resources/e_52469786-oatour',
            'title' => 'Radtour "Nessetal-Radweg" - Von Erfurt nach Eisenach',
        ],
    ],
    'tx_thuecat_trail_way_type' => [
        0 => [
            'uid' => '1',
            'pid' => '11',
            'parentid' => '1',
            'parenttable' => 'tx_thuecat_trail',
            'remote_id' => 'https://thuecat.org/resources/e_52469786-oatour::wt::0',
            'title' => 'Asphalt',
        ],
        1 => [
            'uid' => '2',
            'pid' => '11',
            'parentid' => '1',
            'parenttable' => 'tx_thuecat_trail',
            'remote_id' => 'https://thuecat.org/resources/e_52469786-oatour::wt::1',
            'title' => 'Schotterweg',
        ],
        2 => [
            'uid' => '3',
            'pid' => '11',
            'parentid' => '1',
            'parenttable' => 'tx_thuecat_trail',
            'remote_id' => 'https://thuecat.org/resources/e_52469786-oatour::wt::2',
            'title' => 'Naturweg',
        ],
        3 => [
            'uid' => '4',
            'pid' => '11',
            'parentid' => '1',
            'parenttable' => 'tx_thuecat_trail',
            'remote_id' => 'https://thuecat.org/resources/e_52469786-oatour::wt::3',
            'title' => 'Pfad',
        ],
        4 => [
            'uid' => '5',
            'pid' => '11',
            'parentid' => '1',
            'parenttable' => 'tx_thuecat_trail',
            'remote_id' => 'https://thuecat.org/resources/e_52469786-oatour::wt::4',
            'title' => 'Straße',
        ],
    ],
    'tx_thuecat_import_configuration_url' => [
        [
            'uid' => '101',
            'pid' => '0',
            'configuration' => '1',
            'sorting' => '1',
            'title' => '',
            'url' => 'https://thuecat.org/resources/e_52469786-oatour',
        ],
    ],
];
