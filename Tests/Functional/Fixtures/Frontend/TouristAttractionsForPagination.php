<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

// Odd-numbered attractions carry keyword 501, so a keyword-filtered list still
// spans more than one page and pagination can be observed under a filter.
$attractions = [];
$keywordRelations = [];
for ($i = 1; $i <= 25; $i++) {
    $attractions[] = [
        'uid' => (string)$i,
        'pid' => '11',
        'title' => sprintf('Attraction %02d', $i),
        'description' => '',
        'town' => '0',
        'media' => '',
        'address' => '',
        'url' => '',
        'offers' => '',
        'opening_hours' => '',
        'special_opening_hours' => '',
        'keywords' => $i % 2 === 1 ? '1' : '0',
    ];

    if ($i % 2 === 1) {
        $keywordRelations[] = [
            'uid_local' => 501,
            'uid_foreign' => $i,
            'tablenames' => 'tx_thuecat_tourist_attraction',
            'fieldname' => 'keywords',
        ];
    }
}

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
        [
            'uid' => '10',
            'pid' => '1',
            'title' => 'List Page',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'slug' => '/list/',
            'sorting' => '256',
            'deleted' => '0',
        ],
        [
            'uid' => '11',
            'pid' => '1',
            'title' => 'Storage for Attractions',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'sorting' => '256',
            'deleted' => '0',
        ],
    ],
    'tt_content' => [
        [
            'uid' => '10',
            'pid' => '10',
            'hidden' => '0',
            'sorting' => '1',
            'CType' => 'werkraummedia_thuecatattractionlist',
            'header' => 'Attraction List',
            'deleted' => '0',
            'starttime' => '0',
            'endtime' => '0',
            'colPos' => '0',
            'sys_language_uid' => '0',
            'pages' => '11',
            'recursive' => '0',
        ],
    ],
    'tx_thuecat_tourist_attraction' => $attractions,
    'sys_category' => [
        // The keyword anchor and the one set below it the filter uses.
        [
            'uid' => 500,
            'pid' => '11',
            'parent' => '0',
            'title' => 'Keywords',
        ],
        [
            'uid' => 501,
            'pid' => '11',
            'parent' => 500,
            'title' => 'Ambiente',
        ],
    ],
    'sys_category_record_mm' => $keywordRelations,
];
