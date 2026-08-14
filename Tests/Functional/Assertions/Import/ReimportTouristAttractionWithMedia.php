<?php

declare(strict_types=1);

// Re-import is idempotent: the reference set is identical to the seeded
// state — same rows, same uids, default-language refs plus their synced
// translation copies. No doubling.
return [
    'tx_thuecat_tourist_attraction' => [
        0 => [
            'uid' => '1',
            'pid' => '10',
            'sys_language_uid' => '0',
            'remote_id' => 'https://thuecat.org/resources/attraction-with-media',
            'title' => 'Attraktion mit Bildern',
            'media_files' => '4',
        ],
        1 => [
            'uid' => '2',
            'pid' => '10',
            'sys_language_uid' => '1',
            'l18n_parent' => '1',
            'remote_id' => 'https://thuecat.org/resources/attraction-with-media',
            'title' => 'Attraction with media',
            'media_files' => '4',
        ],
    ],
    'sys_file' => [
        0 => [
            'uid' => '2',
            'identifier' => '/thuecat/image_6ab24be70ef3f2e8.jpg',
        ],
        1 => [
            'uid' => '3',
            'identifier' => '/thuecat/image_89d8f4e95612e13d.jpg',
        ],
        2 => [
            'uid' => '4',
            'identifier' => '/thuecat/image_718be403bf38b616.jpg',
        ],
        3 => [
            'uid' => '5',
            'identifier' => '/thuecat/image_1bd2daee00b7ee9c.jpg',
        ],
    ],
    'sys_file_reference' => [
        0 => [
            'uid' => '1',
            'uid_local' => '2',
            'uid_foreign' => '1',
            'fieldname' => 'media_files',
            'sys_language_uid' => '0',
            'l10n_parent' => '0',
        ],
        1 => [
            'uid' => '2',
            'uid_local' => '3',
            'uid_foreign' => '1',
            'fieldname' => 'media_files',
            'sys_language_uid' => '0',
            'l10n_parent' => '0',
        ],
        2 => [
            'uid' => '3',
            'uid_local' => '4',
            'uid_foreign' => '1',
            'fieldname' => 'media_files',
            'sys_language_uid' => '0',
            'l10n_parent' => '0',
        ],
        3 => [
            'uid' => '4',
            'uid_local' => '5',
            'uid_foreign' => '1',
            'fieldname' => 'media_files',
            'sys_language_uid' => '0',
            'l10n_parent' => '0',
        ],
        4 => [
            'uid' => '5',
            'uid_local' => '2',
            'uid_foreign' => '2',
            'fieldname' => 'media_files',
            'sys_language_uid' => '1',
            'l10n_parent' => '1',
        ],
        5 => [
            'uid' => '6',
            'uid_local' => '3',
            'uid_foreign' => '2',
            'fieldname' => 'media_files',
            'sys_language_uid' => '1',
            'l10n_parent' => '2',
        ],
        6 => [
            'uid' => '7',
            'uid_local' => '4',
            'uid_foreign' => '2',
            'fieldname' => 'media_files',
            'sys_language_uid' => '1',
            'l10n_parent' => '3',
        ],
        7 => [
            'uid' => '8',
            'uid_local' => '5',
            'uid_foreign' => '2',
            'fieldname' => 'media_files',
            'sys_language_uid' => '1',
            'l10n_parent' => '4',
        ],
    ],
];
