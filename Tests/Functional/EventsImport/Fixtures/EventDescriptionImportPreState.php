<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

// Pre-state for the event description test. Static-URL config pointing at one
// trimmed event per schema:description shape.
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
            'title' => 'Event description import',
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
            'url' => 'https://cdb.int.thuecat.org/api/resources/e_desc_html-hubev',
        ],
        [
            'uid' => '102',
            'pid' => '0',
            'configuration' => '1',
            'sorting' => '2',
            'title' => '',
            'url' => 'https://cdb.int.thuecat.org/api/resources/e_desc_textobject-hubev',
        ],
        [
            'uid' => '103',
            'pid' => '0',
            'configuration' => '1',
            'sorting' => '3',
            'title' => '',
            'url' => 'https://cdb.int.thuecat.org/api/resources/e_desc_textonly-hubev',
        ],
        [
            'uid' => '104',
            'pid' => '0',
            'configuration' => '1',
            'sorting' => '4',
            'title' => '',
            'url' => 'https://cdb.int.thuecat.org/api/resources/e_desc_literal-hubev',
        ],
        [
            'uid' => '105',
            'pid' => '0',
            'configuration' => '1',
            'sorting' => '5',
            'title' => '',
            'url' => 'https://cdb.int.thuecat.org/api/resources/e_desc_none-hubev',
        ],
    ],
];
