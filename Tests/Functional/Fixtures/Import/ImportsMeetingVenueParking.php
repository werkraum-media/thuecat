<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

// Inside the attraction_categories site, so the categories of the venue types get their anchor.
return [
    'pages' => [
        [
            'uid' => '3000',
            'pid' => '0',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Rootpage',
            'is_siteroot' => '1',
        ],
        [
            'uid' => '10',
            'pid' => '3000',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'title' => 'Attraction storage folder',
        ],
        [
            'uid' => '20',
            'pid' => '3000',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'title' => 'Category storage folder',
        ],
    ],
    'sys_category' => [
        [
            'uid' => '100',
            'pid' => '20',
            'parent' => '0',
            'title' => 'POIs',
        ],
    ],
    'tx_thuecat_import_configuration' => [
        [
            'uid' => '1',
            'pid' => '0',
            'disable' => '0',
            'title' => 'Venue with one parking facility',
            'type' => 'static',
            'storage_pid' => '10',
        ],
        [
            'uid' => '2',
            'pid' => '0',
            'disable' => '0',
            'title' => 'Venue with a described parking facility',
            'type' => 'static',
            'storage_pid' => '10',
        ],
        [
            'uid' => '3',
            'pid' => '0',
            'disable' => '0',
            'title' => 'Venue with a translated parking facility',
            'type' => 'static',
            'storage_pid' => '10',
        ],
        [
            'uid' => '4',
            'pid' => '0',
            'disable' => '0',
            'title' => 'Venue with two parking facilities',
            'type' => 'static',
            'storage_pid' => '10',
        ],
        [
            'uid' => '5',
            'pid' => '0',
            'disable' => '0',
            'title' => 'Parking facility',
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
            'url' => 'https://thuecat.org/resources/160874095809-phhn',
        ],
        [
            'uid' => '201',
            'pid' => '0',
            'configuration' => '2',
            'sorting' => '1',
            'title' => '',
            'url' => 'https://thuecat.org/resources/332021174274-eyhf',
        ],
        [
            'uid' => '301',
            'pid' => '0',
            'configuration' => '3',
            'sorting' => '1',
            'title' => '',
            'url' => 'https://thuecat.org/resources/117034427781-ndta',
        ],
        [
            'uid' => '401',
            'pid' => '0',
            'configuration' => '4',
            'sorting' => '1',
            'title' => '',
            'url' => 'https://thuecat.org/resources/e_ttg00020050000287335-tomas',
        ],
        [
            'uid' => '501',
            'pid' => '0',
            'configuration' => '5',
            'sorting' => '1',
            'title' => '',
            'url' => 'https://thuecat.org/resources/109087118959-gwhg',
        ],
    ],
];
