<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

// Site trail_keywords: separate keyword trees for the thuecat and trails scopes.
return [
    'pages' => [
        [
            'uid' => '1100',
            'pid' => '0',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Rootpage',
            'is_siteroot' => '1',
        ],
        [
            'uid' => '1110',
            'pid' => '1100',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'title' => 'Record storage folder',
        ],
        [
            'uid' => '1130',
            'pid' => '1100',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'title' => 'Keyword storage folder',
        ],
        [
            'uid' => '1140',
            'pid' => '1100',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'title' => 'Trail keyword storage folder',
        ],
    ],
    'sys_category' => [
        [
            'uid' => '1200',
            'pid' => '1130',
            'parent' => '0',
            'title' => 'Keywords',
        ],
        [
            'uid' => '1300',
            'pid' => '1140',
            'parent' => '0',
            'title' => 'Trail keywords',
        ],
    ],
    'tx_thuecat_import_configuration' => [
        [
            'uid' => '1',
            'pid' => '0',
            'tstamp' => '1613400587',
            'crdate' => '1613400558',
            'disable' => '0',
            'title' => 'Trail as root',
            'type' => 'static',
            'storage_pid' => '1110',
        ],
        [
            'uid' => '2',
            'pid' => '0',
            'tstamp' => '1613400587',
            'crdate' => '1613400558',
            'disable' => '0',
            'title' => 'Attraction and trail sharing a keyword',
            'type' => 'static',
            'storage_pid' => '1110',
        ],
        [
            'uid' => '3',
            'pid' => '0',
            'tstamp' => '1613400587',
            'crdate' => '1613400558',
            'disable' => '0',
            'title' => 'Trail run reaching a tourist information',
            'type' => 'static',
            'storage_pid' => '1110',
        ],
        [
            'uid' => '4',
            'pid' => '0',
            'tstamp' => '1613400587',
            'crdate' => '1613400558',
            'disable' => '0',
            'title' => 'Attraction reaching a trail as a relation',
            'type' => 'static',
            'storage_pid' => '1110',
        ],
    ],
    'tx_thuecat_import_configuration_url' => [
        [
            'uid' => '101',
            'pid' => '0',
            'configuration' => '1',
            'sorting' => '1',
            'title' => '',
            'url' => 'https://thuecat.org/resources/e_106954656-oatour',
        ],
        [
            'uid' => '201',
            'pid' => '0',
            'configuration' => '2',
            'sorting' => '1',
            'title' => '',
            'url' => 'https://thuecat.org/resources/attraction-sharing-trail-keyword',
        ],
        [
            'uid' => '202',
            'pid' => '0',
            'configuration' => '2',
            'sorting' => '2',
            'title' => '',
            'url' => 'https://thuecat.org/resources/e_106954656-oatour',
        ],
        [
            'uid' => '301',
            'pid' => '0',
            'configuration' => '3',
            'sorting' => '1',
            'title' => '',
            'url' => 'https://thuecat.org/resources/e_106954656-oatour',
        ],
        [
            'uid' => '302',
            'pid' => '0',
            'configuration' => '3',
            'sorting' => '2',
            'title' => '',
            'url' => 'https://thuecat.org/resources/tourist-information-with-trail-keyword',
        ],
        [
            'uid' => '401',
            'pid' => '0',
            'configuration' => '4',
            'sorting' => '1',
            'title' => '',
            'url' => 'https://thuecat.org/resources/347070073883-rqbn',
        ],
    ],
];
