<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

// Two roots: one whose managedBy reference is not a fetchable URL (raises in
// the resolver, caught per-root as mappingError), one whose managedBy 404s
// (skipped as a warning).
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
            'title' => 'Skip and error',
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
            'url' => 'https://thuecat.org/resources/unfetchable-reference',
        ],
        [
            'uid' => '102',
            'pid' => '0',
            'configuration' => '1',
            'sorting' => '2',
            'title' => '',
            'url' => 'https://thuecat.org/resources/043064193523-jcyt',
        ],
    ],
];
