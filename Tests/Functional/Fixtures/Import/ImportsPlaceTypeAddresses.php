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
            'uid' => '10',
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
            'title' => 'Organisation with address',
            'type' => 'static',
            'storage_pid' => '10',
        ],
        1 => [
            'uid' => '2',
            'pid' => '0',
            'tstamp' => '1613400587',
            'crdate' => '1613400558',
            'disable' => '0',
            'title' => 'Town with address',
            'type' => 'static',
            'storage_pid' => '10',
        ],
        2 => [
            'uid' => '3',
            'pid' => '0',
            'tstamp' => '1613400587',
            'crdate' => '1613400558',
            'disable' => '0',
            'title' => 'Tourist information with address',
            'type' => 'static',
            'storage_pid' => '10',
        ],
        3 => [
            'uid' => '4',
            'pid' => '0',
            'tstamp' => '1613400587',
            'crdate' => '1613400558',
            'disable' => '0',
            'title' => 'Organisation without address',
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
            'url' => 'https://thuecat.org/resources/900000000010-orgaddr',
        ],
        [
            'uid' => '201',
            'pid' => '0',
            'configuration' => '2',
            'sorting' => '1',
            'url' => 'https://thuecat.org/resources/900000000011-townaddr',
        ],
        [
            'uid' => '301',
            'pid' => '0',
            'configuration' => '3',
            'sorting' => '1',
            'url' => 'https://thuecat.org/resources/900000000012-tiaddr',
        ],
        [
            'uid' => '401',
            'pid' => '0',
            'configuration' => '4',
            'sorting' => '1',
            'url' => 'https://thuecat.org/resources/900000000013-orgnoaddr',
        ],
    ],
];
