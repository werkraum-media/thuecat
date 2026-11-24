<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

// Organisation-only root (no schema:TouristAttraction) carrying one keyword;
// shape from survey record 837208006972-onwc. OrganisationEntity never calls
// recordKeywords(), so nothing is extracted.
return [
    'pages' => [
        [
            'uid' => '1000',
            'pid' => '0',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Rootpage',
            'is_siteroot' => '1',
        ],
        [
            'uid' => '10',
            'pid' => '1000',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'title' => 'Record storage folder',
        ],
        [
            'uid' => '20',
            'pid' => '1000',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'title' => 'Category storage folder',
        ],
        [
            'uid' => '30',
            'pid' => '1000',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'title' => 'Keyword storage folder',
        ],
    ],
    'sys_category' => [
        [
            'uid' => '100',
            'pid' => '20',
            'parent' => '0',
            'title' => 'Categories',
        ],
        [
            'uid' => '200',
            'pid' => '30',
            'parent' => '0',
            'title' => 'Keywords',
        ],
    ],
    'tx_thuecat_import_configuration' => [
        [
            'uid' => '1',
            'pid' => '0',
            'disable' => '0',
            'title' => 'Out-of-scope keyword import',
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
            'url' => 'https://thuecat.org/resources/organisation-with-keyword',
        ],
    ],
];
