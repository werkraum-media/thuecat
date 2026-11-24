<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

// Four URLs in one configuration, one per route to zero dates: no schedule at
// all, a schedule naming only an unusable day, a schedule whose occurrences are
// all excepted, and one whose occurrences all lie in the past.
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
            'title' => 'Dateless event import',
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
            'url' => 'https://cdb.int.thuecat.org/api/resources/e_7cbe5bb1-nosched',
        ],
        [
            'uid' => '102',
            'pid' => '0',
            'configuration' => '1',
            'sorting' => '2',
            'title' => '',
            'url' => 'https://cdb.int.thuecat.org/api/resources/e_7cbe5bb1-unusable',
        ],
        [
            'uid' => '103',
            'pid' => '0',
            'configuration' => '1',
            'sorting' => '3',
            'title' => '',
            'url' => 'https://cdb.int.thuecat.org/api/resources/e_7cbe5bb1-excepted',
        ],
        [
            'uid' => '104',
            'pid' => '0',
            'configuration' => '1',
            'sorting' => '4',
            'title' => '',
            'url' => 'https://cdb.int.thuecat.org/api/resources/e_7cbe5bb1-past',
        ],
    ],
];
