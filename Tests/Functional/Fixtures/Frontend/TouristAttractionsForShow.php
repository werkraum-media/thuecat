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
            'title' => 'Show Page',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'slug' => '/show/',
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
            'CType' => 'thuecat_tourist_attraction_show',
            'header' => 'Show Plugin',
            'colPos' => '0',
            'sorting' => '256',
            'sys_language_uid' => '0',
        ],
    ],
    'tx_thuecat_tourist_attraction' => [
        [
            'uid' => '20',
            'pid' => '11',
            'disable' => '1',
            'title' => 'Verstecktes Stadtmuseum',
            'description' => 'Beschreibung des versteckten Stadtmuseums',
            'town' => '0',
            'media' => '',
            'address' => '',
            'url' => '',
            'offers' => '',
            'opening_hours' => '',
            'special_opening_hours' => '',
        ],
        [
            'uid' => '21',
            'pid' => '11',
            'disable' => '0',
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
