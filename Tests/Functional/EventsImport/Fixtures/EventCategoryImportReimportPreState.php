<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

// Re-import idempotency: the previous import already created the category
// (uid 101, remote_id "type:thuecat:CultureEvent", direct child of parent 100),
// imported the event (uid 1) and wired the sys_category_record_mm relation.
// Running the import once more must NOT duplicate the category or the MM row.
// Mirrors the pre-state re-import pattern used by MediaImportTest rather than
// importing twice in one process.
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
        [
            'uid' => '101',
            'pid' => '20',
            'parent' => '100',
            'title' => 'Kulturveranstaltung',
            'remote_id' => 'type:thuecat:CultureEvent',
        ],
    ],
    'tx_events_domain_model_event' => [
        [
            'uid' => '1',
            'pid' => '10',
            'remote_id' => 'https://int.thuecat.org/resources/e_19542-hubev',
            'title' => 'Konzert des Dresdner Kreuzchores',
            'categories' => '1',
        ],
    ],
    'sys_category_record_mm' => [
        [
            'uid_local' => '101',
            'uid_foreign' => '1',
            'tablenames' => 'tx_events_domain_model_event',
            'fieldname' => 'categories',
            'sorting_foreign' => '1',
        ],
    ],
    'tx_thuecat_import_configuration' => [
        [
            'uid' => '1',
            'pid' => '0',
            'disable' => '0',
            'title' => 'Kreuzchor event import',
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
    ],
];
