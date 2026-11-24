<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

// Multi-category case: the Distel fixture (e_100771372-hubev) maps to TWO
// categories — schema:EventSeries (Veranstaltungsserie) and thuecat:CultureEvent
// (Kulturveranstaltung). The fixture's test: type is unmappable by construction
// and dropped. Both categories must be created under parent 100 and wired onto
// the one event. No categories
// pre-exist. Points the static config at the Distel URL.
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
            'title' => 'Distel event import',
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
            'url' => 'https://cdb.int.thuecat.org/api/resources/e_100771372-hubev',
        ],
    ],
];
