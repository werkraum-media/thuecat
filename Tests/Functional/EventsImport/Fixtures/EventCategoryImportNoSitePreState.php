<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

// storagePid 500 is a folder at the tree root with no site config → the import
// must abort in the pre-flight validator (StoragePidConfigurationException)
// BEFORE any URL is fetched.
return [
    'pages' => [
        [
            'uid' => '500',
            'pid' => '0',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'title' => 'Site-less storage folder',
        ],
    ],
    'tx_thuecat_import_configuration' => [
        [
            'uid' => '1',
            'pid' => '0',
            'disable' => '0',
            'title' => 'Kreuzchor event import',
            'type' => 'static',
            'storage_pid' => '500',
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
    ],
];
