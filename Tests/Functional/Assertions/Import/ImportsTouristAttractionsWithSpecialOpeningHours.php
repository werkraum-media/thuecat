<?php

declare(strict_types=1);

return [
    'tx_thuecat_tourist_attraction' => [
        0 => [
            'uid' => '1',
            'pid' => '10',
            'sys_language_uid' => '0',
            'l18n_parent' => '0',
            'l10n_source' => '0',
            'remote_id' => 'https://thuecat.org/resources/835224016581-dara',
            'title' => 'Dom St. Marien',
            'opening_hours_inline' => 0,
            'special_opening_hours_inline' => 2,
        ],
        1 => [
            'uid' => '2',
            'pid' => '10',
            'sys_language_uid' => '1',
            'l18n_parent' => '1',
            'l10n_source' => '1',
            'remote_id' => 'https://thuecat.org/resources/835224016581-dara',
            'title' => 'Cathedral of St. Mary',
            'opening_hours_inline' => 0,
            'special_opening_hours_inline' => 2,
        ],
    ],
    // Both special specs are kept (lossless import); they share day + opens and
    // differ only by validity window. Each language version of the attraction
    // (de, en) carries its own inline children → 4 live rows. Soft delete is
    // enabled on the table, so re-imported rows leave tombstones (deleted=1);
    // those are acknowledged by existence only (uids are auto-increment and
    // shift), while the live rows are matched on their business fields.
    'tx_thuecat_opening_hours' => [
        // Soft-deleted tombstones from the re-import; existence only.
        ['deleted' => '1'],
        ['deleted' => '1'],
        // Live rows: de (parentid 1) + en (parentid 2), each window once.
        [
            'deleted' => '0',
            'parentid' => '1',
            'parenttable' => 'tx_thuecat_tourist_attraction',
            'specification_type' => 'special',
            'day_of_week' => 'Saturday',
            'opens' => '10:00:00',
            'closes' => '14:00:00',
            'valid_from' => '2050-12-31',
            'valid_through' => '2050-12-31',
        ],
        [
            'deleted' => '0',
            'parentid' => '1',
            'parenttable' => 'tx_thuecat_tourist_attraction',
            'specification_type' => 'special',
            'day_of_week' => 'Saturday',
            'opens' => '10:00:00',
            'closes' => '14:00:00',
            'valid_from' => '2021-12-31',
            'valid_through' => '2021-12-31',
        ],
        [
            'deleted' => '0',
            'parentid' => '2',
            'parenttable' => 'tx_thuecat_tourist_attraction',
            'specification_type' => 'special',
            'day_of_week' => 'Saturday',
            'opens' => '10:00:00',
            'closes' => '14:00:00',
            'valid_from' => '2021-12-31',
            'valid_through' => '2021-12-31',
        ],
        [
            'deleted' => '0',
            'parentid' => '2',
            'parenttable' => 'tx_thuecat_tourist_attraction',
            'specification_type' => 'special',
            'day_of_week' => 'Saturday',
            'opens' => '10:00:00',
            'closes' => '14:00:00',
            'valid_from' => '2050-12-31',
            'valid_through' => '2050-12-31',
        ],
    ],
];
