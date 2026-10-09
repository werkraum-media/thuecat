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

$plugin = static function (int $uid, string $cType, string $pages, string $flexform): array {
    return [
        'uid' => (string)$uid,
        'pid' => (string)$uid,
        'CType' => $cType,
        'header' => 'Attraction List',
        'colPos' => '0',
        'sys_language_uid' => '0',
        'pages' => $pages,
        'recursive' => '0',
        'pi_flexform' => $flexform,
    ];
};

// Folder 11: backend order differs from both title and uid order.
return [
    'pages' => [
        [
            'uid' => '1',
            'pid' => '0',
            'title' => 'Root',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'slug' => '/',
        ],
        $page(10, 'Title Order', '/title-order/'),
        $page(20, 'Backend Order', '/backend-order/'),
        $page(30, 'Unknown Order', '/unknown-order/'),
        $page(40, 'Filtered Backend Order', '/filtered-backend-order/'),
        $page(50, 'Never Arranged', '/never-arranged/'),
        $page(60, 'Paginated Backend Order', '/paginated-backend-order/'),
        $page(70, 'Paginated Title Order', '/paginated-title-order/'),
        [
            'uid' => '11',
            'pid' => '1',
            'title' => 'Storage arranged',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
        ],
        [
            'uid' => '12',
            'pid' => '1',
            'title' => 'Storage never arranged',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
        ],
        [
            'uid' => '13',
            'pid' => '1',
            'title' => 'Storage to rearrange',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
        ],
    ],
    'tt_content' => [
        $plugin(10, 'werkraummedia_thuecatattractionlist', '11', $flexform()),
        $plugin(20, 'werkraummedia_thuecatattractionlist', '11', $flexform(sortBy: 'sorting')),
        $plugin(30, 'werkraummedia_thuecatattractionlist', '11', $flexform(sortBy: 'test:unknown')),
        $plugin(40, 'werkraummedia_thuecatattractionlistfiltered', '11', $flexform(sortBy: 'sorting')),
        $plugin(50, 'werkraummedia_thuecatattractionlist', '12', $flexform(sortBy: 'sorting', itemsPerPage: '2')),
        $plugin(60, 'werkraummedia_thuecatattractionlist', '13', $flexform(sortBy: 'sorting', itemsPerPage: '2')),
        $plugin(70, 'werkraummedia_thuecatattractionlist', '13', $flexform(itemsPerPage: '2')),
    ],
    'tx_thuecat_tourist_attraction' => [
        [
            'uid' => '1',
            'pid' => '11',
            'title' => 'Alpha Museum',
            'sorting' => '300',
        ],
        [
            'uid' => '2',
            'pid' => '11',
            'title' => 'Beta Park',
            'sorting' => '100',
        ],
        [
            'uid' => '3',
            'pid' => '11',
            'title' => 'Gamma Garten',
            'sorting' => '200',
        ],
        [
            'uid' => '4',
            'pid' => '12',
            'title' => 'Zeta Turm',
            'sorting' => '0',
        ],
        [
            'uid' => '5',
            'pid' => '12',
            'title' => 'Eta Brücke',
            'sorting' => '0',
        ],
        [
            'uid' => '6',
            'pid' => '12',
            'title' => 'Theta Tor',
            'sorting' => '0',
        ],
        [
            'uid' => '7',
            'pid' => '13',
            'title' => 'Iota Insel',
            'sorting' => '100',
        ],
        [
            'uid' => '8',
            'pid' => '13',
            'title' => 'Kappa Kapelle',
            'sorting' => '200',
        ],
        [
            'uid' => '9',
            'pid' => '13',
            'title' => 'Lambda Linde',
            'sorting' => '300',
        ],
    ],
];
