<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

$flexform = static function (string ...$settings): string {
    $fields = '';
    foreach ($settings as $name => $value) {
        $fields .= '
                <field index="settings.' . $name . '">
                    <value index="vDEF">' . $value . '</value>
                </field>';
    }

    return '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
<T3FlexForms>
    <data>
        <sheet index="sDEF">
            <language index="lDEF">' . $fields . '
            </language>
        </sheet>
    </data>
</T3FlexForms>';
};

$page = static function (int $uid, string $title, string $slug): array {
    return [
        'uid' => (string)$uid,
        'pid' => '1',
        'title' => $title,
        'doktype' => PageRepository::DOKTYPE_DEFAULT,
        'slug' => $slug,
    ];
};

$plugin = static function (int $uid, string $flexform): array {
    return [
        'uid' => (string)$uid,
        'pid' => (string)$uid,
        'CType' => 'werkraummedia_thuecatattractionlist',
        'header' => 'Attraction List',
        'colPos' => '0',
        'sys_language_uid' => '0',
        'pages' => '11',
        'recursive' => '0',
        'pi_flexform' => $flexform,
    ];
};

$attraction = static function (int $uid, string $title, string $type, array $venueFields = []): array {
    return [
        'uid' => (string)$uid,
        'pid' => '11',
        'type' => $type,
        'title' => $title,
        'description' => '',
        'town' => '',
        'url' => '',
        'offers' => '',
        'sys_language_uid' => '0',
        ...$venueFields,
    ];
};

return [
    'pages' => [
        [
            'uid' => '1',
            'pid' => '0',
            'title' => 'Root',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'slug' => '/',
        ],
        $page(10, 'Every Type', '/every-type/'),
        $page(20, 'Meeting Venues', '/meeting-venues/'),
        $page(30, 'Every Type Ticked', '/every-type-ticked/'),
        [
            'uid' => '11',
            'pid' => '1',
            'title' => 'Storage',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
        ],
    ],
    'tt_content' => [
        $plugin(10, $flexform()),
        $plugin(20, $flexform(recordTypes: 'meeting_venue')),
        $plugin(30, $flexform(recordTypes: 'attraction,meeting_venue')),
    ],
    'tx_thuecat_tourist_attraction' => [
        $attraction(1, 'Stadtmuseum Erfurt', 'attraction'),
        $attraction(2, 'Tagungshotel am Dom', 'meeting_venue', ['maximum_attendee_capacity' => '250']),
    ],
];
