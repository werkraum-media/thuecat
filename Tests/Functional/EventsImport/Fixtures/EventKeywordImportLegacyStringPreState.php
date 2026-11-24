<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

// The event already exists with a keywords string written by another importer,
// which a ThueCat import must leave verbatim.
return [
    'pages' => [
        [
            'uid' => '2200',
            'pid' => '0',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Rootpage',
            'is_siteroot' => '1',
        ],
        [
            'uid' => '10',
            'pid' => '2200',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'title' => 'Event storage folder',
        ],
        [
            'uid' => '20',
            'pid' => '2200',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'title' => 'Category storage folder',
        ],
        [
            'uid' => '30',
            'pid' => '2200',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'title' => 'Keyword storage folder',
        ],
    ],
    'sys_category' => [
        [
            'uid' => '100',
            'pid' => '20',
            'parent' => '0',
            'title' => 'Events',
        ],
        [
            'uid' => '200',
            'pid' => '30',
            'parent' => '0',
            'title' => 'Keywords',
        ],
    ],
    'tx_events_domain_model_event' => [
        [
            'uid' => '1',
            'pid' => '10',
            'sys_language_uid' => '0',
            'remote_id' => 'https://cdb.int.thuecat.org/api/resources/e_19542-keywords',
            'title' => 'Konzert des Dresdner Kreuzchores',
            'keywords' => 'Vorbestehend, aus anderer Quelle',
        ],
    ],
    'tx_thuecat_import_configuration' => [
        [
            'uid' => '1',
            'pid' => '0',
            'disable' => '0',
            'title' => 'Event keyword import',
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
            'url' => 'https://cdb.int.thuecat.org/api/resources/e_19542-keywords',
        ],
    ],
];
