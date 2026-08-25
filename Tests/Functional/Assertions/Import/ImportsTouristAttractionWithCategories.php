<?php

declare(strict_types=1);

// Categories are wired onto the default-language attraction only (l10n_mode=
// exclude): two mapped @types become sys_category rows, each related via
// sys_category_record_mm. Museum's ancestor is created alongside them without
// being related — it gives the tree a level, nothing more. The unmapped
// thuecat:Building and the ignored structural types produce no row.
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
        // schema:Museum's ancestor, created to give the tree its level. The
        // record never names it, so it carries no relation.
        1 => [
            'uid' => '101',
            'pid' => '20',
            'parent' => '100',
            'title' => 'Öffentliches Bauwerk',
            'remote_id' => 'type:schema:CivicStructure',
        ],
        2 => [
            'uid' => '102',
            'pid' => '20',
            'parent' => '101',
            'title' => 'Museum',
            'remote_id' => 'type:schema:Museum',
        ],
        // The seeded vocabulary knows no chain for Synagogue, so it stays a
        // root of its own beneath the anchor.
        3 => [
            'uid' => '103',
            'pid' => '20',
            'parent' => '100',
            'title' => 'Synagoge',
            'remote_id' => 'type:schema:Synagogue',
        ],
    ],
    'sys_category_record_mm' => [
        0 => [
            'uid_local' => '102',
            'uid_foreign' => '1',
            'tablenames' => 'tx_thuecat_tourist_attraction',
            'fieldname' => 'categories',
        ],
        1 => [
            'uid_local' => '103',
            'uid_foreign' => '1',
            'tablenames' => 'tx_thuecat_tourist_attraction',
            'fieldname' => 'categories',
        ],
    ],
];
