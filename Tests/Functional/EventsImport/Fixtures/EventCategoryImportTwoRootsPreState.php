<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

// Cross-root dedup: a config with TWO event URLs (Kreuzchor + Distel) that both
// map to "Kulturveranstaltung" (type:thuecat:CultureEvent). resolve() runs once
// per URL, so without a run-scoped dedup map the shared category would be staged
// twice → duplicate sys_category rows. This is the production bug the earlier
// single-URL tests never exercised.
return [
    'pages' => [
        [
            'uid' => '2100',
            'pid' => '0',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Rootpage',
            'is_siteroot' => '1',
        ],
        [
            'uid' => '10',
            'pid' => '2100',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'title' => 'Event storage folder',
        ],
        [
            'uid' => '20',
            'pid' => '2100',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'title' => 'Category storage folder',
        ],
    ],
    'sys_category' => [
        [
            'uid' => '100',
            'pid' => '20',
            'parent' => '0',
            'title' => 'Events',
        ],
    ],
    'tx_thuecat_import_configuration' => [
        [
            'uid' => '1',
            'pid' => '0',
            'disable' => '0',
            'title' => 'Two-root event import',
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
            'url' => 'https://cdb.int.thuecat.org/api/resources/e_19542-hubev',
        ],
        [
            'uid' => '102',
            'pid' => '0',
            'configuration' => '1',
            'sorting' => '2',
            'title' => '',
            'url' => 'https://cdb.int.thuecat.org/api/resources/e_100771372-hubev',
        ],
    ],
];
