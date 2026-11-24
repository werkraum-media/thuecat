<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

// Pre-state for the POI category import test. Static attraction config extended
// with categoryParent (uid 100) and categoryStoragePid (page 20), mirroring the
// event category pre-state. The attraction fixture carries two mapped @types
// (Museum, Synagogue) and one unmapped (Building); no category pre-exists — the
// happy path creates both fresh as direct children of the parent at pid 20.
//
// Page tree: 1 (siteroot) → 10 attraction storage folder, 20 category storage
// folder. Both sit inside the site so the resolver's site-scope guard passes.
return [
    'pages' => [
        [
            'uid' => '1',
            'pid' => '0',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Rootpage',
            'is_siteroot' => '1',
            // Import fixtures never render, so they carry no slug; without one
            // on the root, no page below it resolves.
            'slug' => '/',
        ],
        [
            'uid' => '10',
            'pid' => '1',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'title' => 'Attraction storage folder',
        ],
        [
            'uid' => '20',
            'pid' => '1',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'title' => 'Category storage folder',
        ],
        [
            'uid' => '30',
            'pid' => '1',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'List Page',
            'slug' => '/list/',
        ],
    ],
    'tt_content' => [
        [
            'uid' => 10,
            'pid' => 30,
            'CType' => 'werkraummedia_thuecatattractionlist',
            'header' => 'Attraction List',
            'colPos' => 0,
            'sorting' => 1,
            'sys_language_uid' => 0,
            'pages' => '10',
            'recursive' => 0,
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
            'title' => 'Tourist Attraction with categories',
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
            'url' => 'https://thuecat.org/resources/attraction-with-category',
        ],
    ],
];
