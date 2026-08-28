<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

// Trail 10 and attraction 10 share a uid on purpose: a teaser identity ignoring
// the table would serve one record's markup for the other.

$selectedListFlexform = static function (string $detailPageField, int $detailPageUid, string $selectedRecords): string {
    return '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
<T3FlexForms>
    <data>
        <sheet index="sDEF">
            <language index="lDEF">
                <field index="settings.page.pid.' . $detailPageField . '">
                    <value index="vDEF">' . $detailPageUid . '</value>
                </field>
                <field index="settings.selectedRecords">
                    <value index="vDEF">' . $selectedRecords . '</value>
                </field>
            </language>
        </sheet>
    </data>
</T3FlexForms>';
};

$page = static function (int $uid, string $slug, string $title, int $doktype = PageRepository::DOKTYPE_DEFAULT): array {
    return [
        'uid' => (string)$uid,
        'pid' => '1',
        'title' => $title,
        'doktype' => (string)$doktype,
        'slug' => $slug,
        'sorting' => '256',
        'deleted' => '0',
    ];
};

$list = static function (int $uid, int $pid, string $cType, string $flexform): array {
    return [
        'uid' => (string)$uid,
        'pid' => (string)$pid,
        'hidden' => '0',
        'sorting' => '1',
        'CType' => $cType,
        'header' => 'Selected List',
        'deleted' => '0',
        'starttime' => '0',
        'endtime' => '0',
        'colPos' => '0',
        'sys_language_uid' => '0',
        'pages' => '11',
        'recursive' => '0',
        'pi_flexform' => $flexform,
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
            'sorting' => '128',
            'deleted' => '0',
        ],
        $page(10, '/trails/', 'Trail List Page'),
        $page(13, '/attractions/', 'Attraction List Page'),
        $page(20, '/trail-detail/', 'Trail Detail Page'),
        $page(21, '/other-trail-detail/', 'Other Trail Detail Page'),
        $page(11, '/storage/', 'Storage', PageRepository::DOKTYPE_SYSFOLDER),
    ],
    'tt_content' => [
        $list(10, 10, 'werkraummedia_thuecattraillistselected', $selectedListFlexform('thuecat_trail_show', 20, '10,11')),
        $list(13, 13, 'werkraummedia_thuecatattractionlistselected', $selectedListFlexform('thuecat_attraction_show', 20, '10')),
    ],
    'tx_thuecat_trail' => [
        [
            'uid' => '10',
            'pid' => '11',
            'title' => 'Goethe-Erlebnisweg',
            'description' => 'Beschreibung des Goethe-Erlebniswegs',
        ],
        [
            'uid' => '11',
            'pid' => '11',
            'title' => 'Lutherweg Thüringen',
            'description' => 'Beschreibung des Lutherwegs',
        ],
    ],
    'tx_thuecat_tourist_attraction' => [
        [
            'uid' => '10',
            'pid' => '11',
            'title' => 'Stadtmuseum Erfurt',
            'description' => 'Beschreibung des Stadtmuseums',
            'town' => '0',
            'media' => '',
            'address' => '',
            'url' => '',
            'offers' => '',
            'opening_hours' => '',
            'special_opening_hours' => '',
        ],
    ],
];
