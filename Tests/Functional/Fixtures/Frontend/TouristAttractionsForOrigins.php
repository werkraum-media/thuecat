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

$page = (static fn (int $uid, string $title, string $slug): array => [
    'uid' => (string)$uid,
    'pid' => '1',
    'title' => $title,
    'doktype' => PageRepository::DOKTYPE_DEFAULT,
    'slug' => $slug,
]);

$plugin = (static fn (int $uid, string $flexform, string $cType = 'werkraummedia_thuecatattractionlist'): array => [
    'uid' => (string)$uid,
    'pid' => (string)($uid - $uid % 10),
    'CType' => $cType,
    'header' => 'Attraction Plugin',
    'colPos' => '0',
    'sorting' => (string)$uid,
    'sys_language_uid' => '0',
    'pages' => '11',
    'recursive' => '0',
    'pi_flexform' => $flexform,
]);

$attraction = (static fn (int $uid, string $title, string $originTitle): array => [
    'uid' => (string)$uid,
    'pid' => '11',
    'type' => 'attraction',
    'title' => $title,
    'description' => '',
    'town' => '',
    'url' => '',
    'offers' => '',
    'sys_language_uid' => '0',
    'origin_configuration_uid' => $originTitle === '' ? '0' : (string)(30 + $uid),
    'origin_configuration_title' => $originTitle,
]);

return [
    'pages' => [
        [
            'uid' => '1',
            'pid' => '0',
            'title' => 'Root',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'slug' => '/',
        ],
        $page(10, 'Every Origin', '/every-origin/'),
        $page(20, 'Weimar', '/weimar/'),
        $page(30, 'Weimar and Erfurt', '/weimar-and-erfurt/'),
        $page(40, 'Search Weimar', '/search-weimar/'),
        [
            'uid' => '11',
            'pid' => '1',
            'title' => 'Storage',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
        ],
    ],
    'tt_content' => [
        $plugin(10, $flexform()),
        $plugin(20, $flexform(origins: 'Weimar POIs')),
        $plugin(30, $flexform(origins: 'Weimar POIs,Erfurt POIs')),
        $plugin(40, $flexform(), 'werkraummedia_thuecatattractionsearch'),
        $plugin(41, $flexform(origins: 'Weimar POIs')),
    ],
    'tx_thuecat_tourist_attraction' => [
        $attraction(1, 'Museum Weimar', 'Weimar POIs'),
        $attraction(2, 'Museum Erfurt', 'Erfurt POIs'),
        $attraction(3, 'Museum ohne Herkunft', ''),
    ],
];
