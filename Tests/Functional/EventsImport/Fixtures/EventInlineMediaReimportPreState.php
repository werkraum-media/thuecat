<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

// Pre-state for the event media import tests. Carries a fileFolder so the
// real FAL path runs; the per-test fixture decides which event is fetched.
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
            'title' => 'Event inline media reimport',
            'type' => 'static',
            'storage_pid' => '10',
            'file_folder' => '1:/thuecat/',
        ],
    ],
    'tx_events_domain_model_event' => [
        0 => [
            'uid' => '1',
            'pid' => '10',
            'tstamp' => 1613400587,
            'crdate' => 1613400558,
            'remote_id' => 'https://thuecat.org/resources/e_inline_media-tdm',
            'title' => 'Inline Media Event',
            'images' => '1',
        ],
    ],
    'sys_file' => [
        0 => [
            'uid' => '2',
            'storage' => '1',
            'type' => '2',
            'extension' => 'jpg',
            'mime_type' => 'image/jpeg',
            'name' => 'c4915c4a-9a68-4a51-8d4e-782158f6887d_31222b4549bdcfaa.jpg',
            'identifier' => '/thuecat/c4915c4a-9a68-4a51-8d4e-782158f6887d_31222b4549bdcfaa.jpg',
            'identifier_hash' => '15c71f28533818439f319329f3fd9c58b3ea9e25',
            'folder_hash' => '7cd26a2efdc70daaac29904c75bc135bb21e3506',
        ],
    ],
    'sys_file_reference' => [
        0 => [
            'uid' => '1',
            'pid' => '10',
            'uid_local' => '2',
            'uid_foreign' => '1',
            'tablenames' => 'tx_events_domain_model_event',
            'fieldname' => 'images',
            'sorting_foreign' => '1',
        ],
    ],
    'tx_thuecat_import_configuration_url' => [
        [
            'uid' => '101',
            'pid' => '0',
            'configuration' => '1',
            'sorting' => '1',
            'title' => '',
            'url' => 'https://cdb.int.thuecat.org/api/resources/e_inline_media-tdm',
        ],
    ],
];
