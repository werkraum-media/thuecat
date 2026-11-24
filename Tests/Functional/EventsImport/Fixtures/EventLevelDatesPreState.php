<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

// Five URLs in one configuration, one per event-level shape: full datetimes,
// no time keys, time keys disagreeing with the datetimes, date-only (full day),
// and the mixed shape where a schedule must win.
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
    'tx_thuecat_import_configuration' => [
        0 => [
            'uid' => '1',
            'pid' => '0',
            'tstamp' => 1613400587,
            'crdate' => 1613400558,
            'disable' => '0',
            'title' => 'Event level dates import',
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
            'url' => 'https://cdb.int.thuecat.org/api/resources/e_19c21523-343f-4b72-b13c-756ac4bde8c5-tdm',
        ],
        [
            'uid' => '102',
            'pid' => '0',
            'configuration' => '1',
            'sorting' => '2',
            'title' => '',
            'url' => 'https://cdb.int.thuecat.org/api/resources/e_19c21523-343f-4b72-b13c-756ac4bde8c5-notimes',
        ],
        [
            'uid' => '103',
            'pid' => '0',
            'configuration' => '1',
            'sorting' => '3',
            'title' => '',
            'url' => 'https://cdb.int.thuecat.org/api/resources/e_19c21523-343f-4b72-b13c-756ac4bde8c5-timedisagree',
        ],
        [
            'uid' => '104',
            'pid' => '0',
            'configuration' => '1',
            'sorting' => '4',
            'title' => '',
            'url' => 'https://cdb.int.thuecat.org/api/resources/e_19c21523-343f-4b72-b13c-756ac4bde8c5-dateonly',
        ],
        [
            'uid' => '105',
            'pid' => '0',
            'configuration' => '1',
            'sorting' => '5',
            'title' => '',
            'url' => 'https://cdb.int.thuecat.org/api/resources/e_349b798696b77c81995d71a99b4460d1-kcev',
        ],
    ],
];
