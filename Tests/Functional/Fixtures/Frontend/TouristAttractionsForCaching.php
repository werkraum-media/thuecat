<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

// Two list plugins over one storage, differing only in the detail page their
// teasers link to: page 10 -> detail 20, page 13 -> detail 21. Both show
// attractions 10 and 11.

$detailPageFlexform = static function (int $detailPageUid): string {
    return '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
<T3FlexForms>
    <data>
        <sheet index="sDEF">
            <language index="lDEF">
                <field index="settings.page.pid.thuecat_attraction_show">
                    <value index="vDEF">' . $detailPageUid . '</value>
                </field>
            </language>
        </sheet>
    </data>
</T3FlexForms>';
};

$selectedListFlexform = static function (int $detailPageUid, string $selectedRecords): string {
    return '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
<T3FlexForms>
    <data>
        <sheet index="sDEF">
            <language index="lDEF">
                <field index="settings.page.pid.thuecat_attraction_show">
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

$list = static function (int $uid, int $pid, string $flexform, string $storagePages = '11'): array {
    return [
        'uid' => (string)$uid,
        'pid' => (string)$pid,
        'hidden' => '0',
        'sorting' => '1',
        'CType' => 'werkraummedia_thuecatattractionlist',
        'header' => 'Attraction List',
        'deleted' => '0',
        'starttime' => '0',
        'endtime' => '0',
        'colPos' => '0',
        'sys_language_uid' => '0',
        'pages' => $storagePages,
        'recursive' => '0',
        'pi_flexform' => $flexform,
    ];
};

$attraction = static function (int $uid, string $title, string $description, int $pid = 11): array {
    return [
        'uid' => (string)$uid,
        'pid' => (string)$pid,
        'title' => $title,
        'description' => $description,
        'town' => '0',
        'media' => '',
        'address' => '',
        'url' => '',
        'offers' => '',
        'opening_hours' => '',
        'special_opening_hours' => '',
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
        $page(10, '/list/', 'List Page'),
        $page(11, '/storage/', 'Storage for Attractions', PageRepository::DOKTYPE_SYSFOLDER),
        $page(12, '/more-storage/', 'Further Attractions', PageRepository::DOKTYPE_SYSFOLDER),
        $page(13, '/second-list/', 'Second List Page'),
        $page(14, '/wider-list/', 'Wider List Page'),
        $page(15, '/curated-list/', 'Curated List Page'),
        $page(20, '/detail/', 'Detail Page'),
        $page(21, '/other-detail/', 'Other Detail Page'),
    ],
    'tt_content' => [
        $list(10, 10, $detailPageFlexform(20)),
        $list(11, 13, $detailPageFlexform(21)),
        // Same detail page as CE 10, so only uid 12 is a new teaser.
        $list(12, 14, $detailPageFlexform(20), '11,12'),
        // Curated: same records and detail page as CE 10, so every teaser it
        // needs already exists.
        [
            'uid' => '13',
            'pid' => '15',
            'hidden' => '0',
            'sorting' => '1',
            'CType' => 'werkraummedia_thuecatattractionlistselected',
            'header' => 'Curated Attraction List',
            'deleted' => '0',
            'starttime' => '0',
            'endtime' => '0',
            'colPos' => '0',
            'sys_language_uid' => '0',
            'pi_flexform' => $selectedListFlexform(20, '10,11'),
        ],
    ],
    'tx_thuecat_tourist_attraction' => [
        $attraction(10, 'Stadtmuseum Erfurt', 'Beschreibung des Stadtmuseums'),
        $attraction(11, 'Domberg Erfurt', 'Beschreibung des Dombergs'),
        $attraction(12, 'Krämerbrücke Erfurt', 'Beschreibung der Krämerbrücke', 12),
    ],
];
