<?php

declare(strict_types=1);

// Categories are wired onto the default-language attraction only (l10n_mode=
// exclude): two mapped @types become sys_category rows under the configured
// parent (uid 100, pid 20), each related via sys_category_record_mm. The
// unmapped thuecat:Building and the ignored structural types produce no row.
return [
    'tx_thuecat_tourist_attraction' => [
        0 => [
            'uid' => '1',
            'pid' => '10',
            'sys_language_uid' => '0',
            'l18n_parent' => '0',
            'l10n_source' => '0',
            'remote_id' => 'https://thuecat.org/resources/attraction-with-category',
            'title' => 'Museum mit Kategorien',
        ],
    ],
    'sys_category' => [
        0 => [
            'uid' => '100',
            'pid' => '20',
            'parent' => '0',
            'title' => 'POIs',
        ],
        1 => [
            'uid' => '101',
            'pid' => '20',
            'parent' => '100',
            'title' => 'Museum',
            'remote_id' => 'type:schema:Museum',
        ],
        2 => [
            'uid' => '102',
            'pid' => '20',
            'parent' => '100',
            'title' => 'Synagoge',
            'remote_id' => 'type:schema:Synagogue',
        ],
    ],
    'sys_category_record_mm' => [
        0 => [
            'uid_local' => '101',
            'uid_foreign' => '1',
            'tablenames' => 'tx_thuecat_tourist_attraction',
            'fieldname' => 'categories',
        ],
        1 => [
            'uid_local' => '102',
            'uid_foreign' => '1',
            'tablenames' => 'tx_thuecat_tourist_attraction',
            'fieldname' => 'categories',
        ],
    ],
];
