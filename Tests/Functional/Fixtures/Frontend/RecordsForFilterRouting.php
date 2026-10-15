<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

$lockedTownFlexform = '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
<T3FlexForms>
    <data>
        <sheet index="sDEF">
            <language index="lDEF">
                <field index="settings.towns">
                    <value index="vDEF">1</value>
                </field>
            </language>
        </sheet>
    </data>
</T3FlexForms>';

$attraction = static function (
    int $uid,
    string $title,
    int $town,
    int $languageId = 0,
    int $l18nParent = 0,
    string $flag = 'false'
): array {
    return [
        'uid' => (string)$uid,
        'pid' => '11',
        'pets_allowed' => $flag,
        'is_accessible_for_free' => $flag,
        'public_access' => $flag,
        'title' => $title,
        'description' => '',
        'town' => (string)$town,
        'url' => '',
        'offers' => '',
        'categories' => '1',
        'sys_language_uid' => (string)$languageId,
        'l18n_parent' => (string)$l18nParent,
    ];
};

$category = static function (int $uid, int $attraction): array {
    return [
        'uid_local' => (string)$uid,
        'uid_foreign' => (string)$attraction,
        'tablenames' => 'tx_thuecat_tourist_attraction',
        'fieldname' => 'categories',
        'sorting_foreign' => '1',
    ];
};

// The role is l10n_mode exclude, which DataHandler copies into translations.
// The data set bypasses DataHandler, so translated pages carry it explicitly.
return [
    'pages' => [
        [
            'uid' => '1',
            'pid' => '0',
            'title' => 'Root',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'slug' => '/',
            'sorting' => '128',
        ],
        [
            'uid' => '10',
            'pid' => '1',
            'title' => 'Liste',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'slug' => '/liste',
            'sorting' => '256',
            'tx_thuecat_page_role' => 'thuecat_touristattractionlist',
        ],
        [
            'uid' => '110',
            'pid' => '1',
            'title' => 'List',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'slug' => '/list',
            'sorting' => '256',
            'sys_language_uid' => '1',
            'l10n_parent' => '10',
            'tx_thuecat_page_role' => 'thuecat_touristattractionlist',
        ],
        [
            'uid' => '11',
            'pid' => '1',
            'title' => 'Storage',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'sorting' => '512',
        ],
        [
            'uid' => '111',
            'pid' => '1',
            'title' => 'Storage EN',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'sorting' => '512',
            'sys_language_uid' => '1',
            'l10n_parent' => '11',
        ],
        [
            'uid' => '12',
            'pid' => '1',
            'title' => 'Erfurt',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'slug' => '/erfurt-liste',
            'sorting' => '768',
            'tx_thuecat_page_role' => 'thuecat_touristattractionlist',
        ],
    ],
    'tt_content' => [
        [
            'uid' => '10',
            'pid' => '10',
            'CType' => 'werkraummedia_thuecatattractionlist',
            'colPos' => '0',
            'sorting' => '256',
            'sys_language_uid' => '0',
            'pages' => '11',
        ],
        [
            'uid' => '110',
            'pid' => '10',
            'CType' => 'werkraummedia_thuecatattractionlist',
            'colPos' => '0',
            'sorting' => '256',
            'sys_language_uid' => '1',
            'l18n_parent' => '10',
            'pages' => '11',
        ],
        [
            'uid' => '12',
            'pid' => '12',
            'CType' => 'werkraummedia_thuecatattractionlist',
            'colPos' => '0',
            'sorting' => '256',
            'sys_language_uid' => '0',
            'pages' => '11',
            'pi_flexform' => $lockedTownFlexform,
        ],
    ],
    'tx_thuecat_town' => [
        [
            'uid' => '1',
            'pid' => '11',
            'title' => 'Erfurt',
            'slug' => 'erfurt',
        ],
        [
            'uid' => '2',
            'pid' => '11',
            'title' => 'Weimar',
            'slug' => 'weimar',
        ],
    ],
    'tx_thuecat_tourist_attraction' => [
        $attraction(1, 'Stadtmuseum Erfurt', 1, flag: 'true'),
        $attraction(2, 'Domberg Erfurt', 1, flag: 'true'),
        $attraction(102, 'Erfurt Cathedral', 1, 1, 2, 'true'),
        $attraction(3, 'Goethehaus Weimar', 2),
        $attraction(4, 'Predigerkirche Erfurt', 1, flag: 'true'),
        $attraction(104, 'Preachers Church Erfurt', 1, 1, 4, 'true'),
        $attraction(5, 'Angermuseum Erfurt', 1),
    ],
    'sys_category' => [
        [
            'uid' => '300',
            'pid' => '11',
            'parent' => '0',
            'title' => 'Kategorien',
            'slug' => 'kategorien',
        ],
        [
            'uid' => '10',
            'pid' => '11',
            'parent' => '300',
            'title' => 'Museum',
            'slug' => 'museum',
        ],
        [
            'uid' => '11',
            'pid' => '11',
            'parent' => '300',
            'title' => 'Kirche',
            'slug' => 'kirche',
        ],
        [
            'uid' => '1011',
            'pid' => '11',
            'parent' => '300',
            'title' => 'Church',
            'slug' => 'church',
            'sys_language_uid' => '1',
            'l10n_parent' => '11',
        ],
    ],
    'sys_category_record_mm' => [
        $category(10, 1),
        $category(11, 2),
        $category(11, 102),
        $category(10, 3),
        $category(11, 4),
        $category(11, 104),
        $category(10, 5),
    ],
];
