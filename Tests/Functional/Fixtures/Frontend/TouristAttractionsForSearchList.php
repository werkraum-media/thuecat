<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

$attractions = [];
// 15 free Erfurt attractions -> filtered result still spans multiple pages.
for ($i = 1; $i <= 15; $i++) {
    $attractions[] = [
        'uid' => (string)$i,
        'pid' => '11',
        'title' => sprintf('Erfurt Frei %02d', $i),
        'town' => '1',
        'is_accessible_for_free' => 'true',
        'pets_allowed' => 'false',
        'public_access' => 'false',
    ];
}
// Control records that must be filtered out.
$attractions[] = [
    'uid' => '20',
    'pid' => '11',
    'title' => 'Erfurt Kostenpflichtig',
    'town' => '1',
    'is_accessible_for_free' => 'false',
    'pets_allowed' => 'false',
    'public_access' => 'false',
];
$attractions[] = [
    'uid' => '21',
    'pid' => '11',
    'title' => 'Weimar Frei',
    'town' => '2',
    'is_accessible_for_free' => 'true',
    'pets_allowed' => 'false',
    'public_access' => 'false',
];

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
            'title' => 'Search + List Page',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'slug' => '/search-results/',
            'sorting' => '256',
            'deleted' => '0',
        ],
        [
            'uid' => '11',
            'pid' => '1',
            'title' => 'Storage for Attractions',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'sorting' => '512',
            'deleted' => '0',
        ],
    ],
    'tt_content' => [
        [
            'uid' => '10',
            'pid' => '10',
            'CType' => 'werkraummedia_thuecatattractionsearch',
            'header' => 'Attraction Search',
            'colPos' => '0',
            'sorting' => '1',
            'sys_language_uid' => '0',
        ],
        [
            'uid' => '11',
            'pid' => '10',
            'CType' => 'werkraummedia_thuecatattractionlist',
            'header' => 'Attraction List',
            'colPos' => '0',
            'sorting' => '2',
            'sys_language_uid' => '0',
            'pages' => '11',
            'recursive' => '0',
        ],
    ],
    'tx_thuecat_town' => [
        [
            'uid' => '1',
            'pid' => '11',
            'title' => 'Erfurt',
        ],
        [
            'uid' => '2',
            'pid' => '11',
            'title' => 'Weimar',
        ],
    ],
    'tx_thuecat_tourist_attraction' => $attractions,
];
