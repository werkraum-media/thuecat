<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

// Combined pages exercising AttractionListOnPageResolver through the search form:
//  page 10 search + FILTERED list (towns=1) -> town hidden+locked, stays on page
//  page 20 search + PLAIN list             -> town selectable, stays on page
//  page 30 search only                      -> form targets central search page 40
//  page 50 search + filtered list translated to a DIFFERENT town (en, towns=2)

$townFlexform = static function (string $townUids): string {
    return '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
<T3FlexForms>
    <data>
        <sheet index="sDEF">
            <language index="lDEF">
                <field index="settings.towns">
                    <value index="vDEF">' . $townUids . '</value>
                </field>
            </language>
        </sheet>
    </data>
</T3FlexForms>';
};

$search = static function (int $uid, int $pid, int $sorting = 1, int $language = 0, int $l10nParent = 0): array {
    return [
        'uid' => (string)$uid,
        'pid' => (string)$pid,
        'CType' => 'werkraummedia_thuecatattractionsearch',
        'header' => 'Attraction Search',
        'colPos' => '0',
        'sorting' => (string)$sorting,
        'sys_language_uid' => (string)$language,
        'l18n_parent' => (string)$l10nParent,
    ];
};

$list = static function (int $uid, int $pid, string $cType, string $flexform = '', int $sorting = 2, int $language = 0, int $l10nParent = 0): array {
    return [
        'uid' => (string)$uid,
        'pid' => (string)$pid,
        'CType' => $cType,
        'header' => 'Attraction List',
        'colPos' => '0',
        'sorting' => (string)$sorting,
        'sys_language_uid' => (string)$language,
        'l18n_parent' => (string)$l10nParent,
        'l10n_source' => (string)$l10nParent,
        'pages' => '11',
        'recursive' => '0',
        'pi_flexform' => $flexform,
    ];
};

$page = static function (int $uid, string $slug, string $title, int $language = 0, int $l10nParent = 0): array {
    return [
        'uid' => (string)$uid,
        'pid' => '1',
        'title' => $title,
        'doktype' => PageRepository::DOKTYPE_DEFAULT,
        'slug' => $slug,
        'sorting' => (string)($uid * 10),
        'deleted' => '0',
        'sys_language_uid' => (string)$language,
        'l10n_parent' => (string)$l10nParent,
        'l10n_source' => (string)$l10nParent,
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
        $page(10, '/filtered-combined/', 'Search + filtered list'),
        $page(20, '/plain-combined/', 'Search + plain list'),
        $page(30, '/search-only/', 'Search only'),
        $page(40, '/central-search/', 'Central search target'),
        $page(50, '/filtered-translated/', 'Search + filtered list (translated)'),
        $page(51, '/filtered-translated/', 'Search + filtered list (translated EN)', 1, 50),
        $page(60, '/filtered-two-towns/', 'Search + filtered list (two towns)'),
        $page(70, '/storage-scoped/', 'Search + list restricted to one storage folder'),
        [
            'uid' => '11',
            'pid' => '1',
            'title' => 'Storage for Attractions',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'sorting' => '512',
            'deleted' => '0',
        ],
        [
            'uid' => '12',
            'pid' => '1',
            'title' => 'Other storage folder',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'sorting' => '513',
            'deleted' => '0',
        ],
    ],
    'tt_content' => [
        $search(101, 10, 1),
        $list(102, 10, 'werkraummedia_thuecatattractionlistfiltered', $townFlexform('1'), 2),

        $search(201, 20, 1),
        $list(202, 20, 'werkraummedia_thuecatattractionlist', '', 2),

        $search(301, 30, 1),

        // page 50: default-language filtered list locks Erfurt (1); the en overlay
        // locks Weimar (2) so the rendered form proves the overlaid preset is read.
        $search(501, 50, 1),
        $search(504, 50, 1, 1, 501),
        $list(502, 50, 'werkraummedia_thuecatattractionlistfiltered', $townFlexform('1'), 2),
        $list(503, 50, 'werkraummedia_thuecatattractionlistfiltered', $townFlexform('2'), 2, 1, 502),

        $search(601, 60, 1),
        $list(602, 60, 'werkraummedia_thuecatattractionlistfiltered', $townFlexform('1,2'), 2),

        // page 70: plain list restricted to storage folder 11 only.
        $search(701, 70, 1),
        $list(702, 70, 'werkraummedia_thuecatattractionlist', '', 2),
    ],
    'tx_thuecat_town' => [
        ['uid' => '1', 'pid' => '11', 'title' => 'Erfurt'],
        ['uid' => '2', 'pid' => '11', 'title' => 'Weimar'],
        // In folder 11 but no attraction references it -> must not be offered.
        ['uid' => '4', 'pid' => '11', 'title' => 'Jena'],
        // Lives in the other folder (12) -> outside a list restricted to 11.
        ['uid' => '3', 'pid' => '12', 'title' => 'Gera'],
    ],
    'tx_thuecat_tourist_attraction' => [
        ['uid' => '1', 'pid' => '11', 'title' => 'Stadtmuseum Erfurt', 'town' => '1'],
        ['uid' => '2', 'pid' => '11', 'title' => 'Goethehaus Weimar', 'town' => '2'],
        ['uid' => '3', 'pid' => '12', 'title' => 'Gera Museum', 'town' => '3'],
    ],
];
