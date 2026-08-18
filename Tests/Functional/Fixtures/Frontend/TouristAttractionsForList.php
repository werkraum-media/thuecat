<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

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
        [
            'uid' => '12',
            'pid' => '1',
            'title' => 'Search Form Page',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'slug' => '/search-form/',
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
        [
            'uid' => '12',
            'pid' => '12',
            'hidden' => '0',
            'sorting' => '1',
            'CType' => 'werkraummedia_thuecatattractionsearch',
            'header' => 'Attraction Search',
            'deleted' => '0',
            'starttime' => '0',
            'endtime' => '0',
            'colPos' => '0',
            'sys_language_uid' => '0',
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
        [
            'uid' => '11',
            'pid' => '11',
            'title' => 'Domberg Erfurt',
            'description' => 'Beschreibung des Dombergs',
            'town' => '0',
            'media' => '',
            'address' => '',
            'url' => '',
            'offers' => '',
            'opening_hours' => '',
            'special_opening_hours' => '',
            'keywords' => '1',
        ],
    ],
    // Keyword tree under anchor 500, matching the site settings. Stadtmuseum
    // carries no keyword, so the mask must still offer the whole set.
    'sys_category' => [
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
        [
            'uid' => 502,
            'pid' => '11',
            'parent' => 501,
            'title' => 'romantisch',
        ],
    ],
    'sys_category_record_mm' => [
        [
            'uid_local' => 501,
            'uid_foreign' => 11,
            'tablenames' => 'tx_thuecat_tourist_attraction',
            'fieldname' => 'keywords',
        ],
    ],
];
