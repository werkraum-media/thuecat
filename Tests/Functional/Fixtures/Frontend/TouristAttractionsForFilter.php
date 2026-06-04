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
    'tx_thuecat_tourist_attraction' => [
        [
            // Erfurt, pets allowed, free, public
            'uid' => '1',
            'pid' => '11',
            'title' => 'Stadtmuseum Erfurt',
            'town' => '1',
            'pets_allowed' => 'true',
            'is_accessible_for_free' => 'true',
            'public_access' => 'true',
        ],
        [
            // Erfurt, no pets, not free, not public
            'uid' => '2',
            'pid' => '11',
            'title' => 'Domberg Erfurt',
            'town' => '1',
            'pets_allowed' => 'false',
            'is_accessible_for_free' => 'false',
            'public_access' => 'false',
        ],
        [
            // Weimar, pets allowed, unset others
            'uid' => '3',
            'pid' => '11',
            'title' => 'Goethehaus Weimar',
            'town' => '2',
            'pets_allowed' => 'true',
            'is_accessible_for_free' => '',
            'public_access' => '',
        ],
    ],
];
