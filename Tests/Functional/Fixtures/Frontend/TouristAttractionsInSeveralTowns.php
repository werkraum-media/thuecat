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
        [
            // Offered by no attraction: proves the facet reports what records
            // carry, not every stored town.
            'uid' => '3',
            'pid' => '11',
            'title' => 'Jena',
        ],
    ],
    'tx_thuecat_organisation' => [
        [
            'uid' => '1',
            'pid' => '11',
            'title' => 'Erfurt Tourismus GmbH',
        ],
    ],
    'tx_thuecat_tourist_attraction' => [
        [
            'uid' => '1',
            'pid' => '11',
            'title' => 'Stadtmuseum Erfurt',
            'town' => '1',
            // Contained in an organisation and in another attraction (uid 3).
            'contained_in_organisation' => '1',
            'contained_in_attraction' => '3',
        ],
        [
            'uid' => '2',
            'pid' => '11',
            'title' => 'Goethehaus Weimar',
            'town' => '2',
        ],
        [
            // The airport-serving-two-cities case a single-valued town field
            // could not express.
            'uid' => '3',
            'pid' => '11',
            'title' => 'Flughafen Erfurt-Weimar',
            'town' => '1,2',
        ],
        [
            // No town at all: contributes nothing to the facet.
            'uid' => '4',
            'pid' => '11',
            'title' => 'Ort ohne Stadt',
            'town' => '',
        ],
    ],
];
