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
            // Erfurt, pets allowed, free, public; category Museum
            'uid' => '1',
            'pid' => '11',
            'title' => 'Stadtmuseum Erfurt',
            'town' => '1',
            'pets_allowed' => 'true',
            'is_accessible_for_free' => 'true',
            'public_access' => 'true',
            'categories' => '1',
            'keywords' => '1',
        ],
        [
            // Erfurt, no pets, not free, not public; category Kirche
            'uid' => '2',
            'pid' => '11',
            'title' => 'Domberg Erfurt',
            'town' => '1',
            'pets_allowed' => 'false',
            'is_accessible_for_free' => 'false',
            'public_access' => 'false',
            'categories' => '1',
            'keywords' => '1',
        ],
        [
            // Weimar, pets allowed, unset others; category Haus
            'uid' => '3',
            'pid' => '11',
            'title' => 'Goethehaus Weimar',
            'town' => '2',
            'pets_allowed' => 'true',
            'is_accessible_for_free' => '',
            'public_access' => '',
            'categories' => '1',
        ],
    ],
    'sys_category' => [
        // The category anchor, mirroring the keyword one below: a container the
        // import writes beneath, never offered as a filter option itself.
        [
            'uid' => 300,
            'pid' => '11',
            'parent' => '0',
            'title' => 'Kategorien',
        ],
        // Parent 100 groups building types; Burg (13) is a child but unused.
        [
            'uid' => '100',
            'pid' => '11',
            'parent' => 300,
            'title' => 'Gebäudetyp',
        ],
        [
            'uid' => '10',
            'pid' => '11',
            'parent' => '100',
            'title' => 'Museum',
        ],
        [
            'uid' => '11',
            'pid' => '11',
            'parent' => '100',
            'title' => 'Kirche',
        ],
        [
            'uid' => '13',
            'pid' => '11',
            'parent' => '100',
            'title' => 'Burg',
        ],
        // Third level, to prove the tree is not depth-capped.
        [
            'uid' => '15',
            'pid' => '11',
            'parent' => '10',
            'title' => 'Freilichtmuseum',
        ],
        // Haus (12) is a set of its own directly below the anchor; Region (200)
        // is a foreign branch outside it, so neither it nor its child
        // Innenstadt (14) may appear as an option.
        [
            'uid' => '12',
            'pid' => '11',
            'parent' => 300,
            'title' => 'Haus',
        ],
        [
            'uid' => '200',
            'pid' => '11',
            'parent' => '0',
            'title' => 'Region',
        ],
        [
            'uid' => '14',
            'pid' => '11',
            'parent' => '200',
            'title' => 'Innenstadt',
        ],
        // Keywords
        [
            'uid' => 500,
            'pid' => 11,
            'parent' => 0,
            'title' => 'Keywords',
        ],
        [
            'uid' => 501,
            'pid' => 11,
            'parent' => 500,
            'title' => 'Ambiente',
        ],
        [
            'uid' => 502,
            'pid' => 11,
            'parent' => 501,
            'title' => 'romantisch',
        ],
        [
            'uid' => 503,
            'pid' => 11,
            'parent' => 501,
            'title' => 'modern',
        ],
        [
            'uid' => 504,
            'pid' => 11,
            'parent' => 500,
            'title' => 'Hauptstadt',
        ],
        [
            'uid' => 505,
            'pid' => 11,
            'parent' => 504,
            'title' => 'Erfurt',
        ],
    ],
    'sys_category_record_mm' => [
        [
            'uid_local' => '10',
            'uid_foreign' => '1',
            'tablenames' => 'tx_thuecat_tourist_attraction',
            'fieldname' => 'categories',
            'sorting_foreign' => '1',
        ],
        [
            'uid_local' => '11',
            'uid_foreign' => '2',
            'tablenames' => 'tx_thuecat_tourist_attraction',
            'fieldname' => 'categories',
            'sorting_foreign' => '1',
        ],
        [
            'uid_local' => '12',
            'uid_foreign' => '3',
            'tablenames' => 'tx_thuecat_tourist_attraction',
            'fieldname' => 'categories',
            'sorting_foreign' => '1',
        ],
        // Records carry the TERMS, never the sets above them: upstream relates
        // an object to a leaf, and the sets exist to group the vocabulary.
        [
            'uid_local' => 502,
            'uid_foreign' => 1,
            'tablenames' => 'tx_thuecat_tourist_attraction',
            'fieldname' => 'keywords',
        ],
        [
            'uid_local' => 505,
            'uid_foreign' => 2,
            'tablenames' => 'tx_thuecat_tourist_attraction',
            'fieldname' => 'keywords',
        ],
    ],
];
