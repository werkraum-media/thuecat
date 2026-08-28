<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

/*
 * Every row here exists to make one option either appear or drop out:
 * visibility of the record, visibility of the option record, multi-value
 * columns, the 0 placeholder, and two fields sharing one MM table.
 */
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
            'title' => 'Storage outside the scope',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'sorting' => '512',
            'deleted' => '0',
        ],
        // A second site. Its records are never in scope, and a value living
        // here must not be offered even when something points at it.
        [
            'uid' => '90',
            'pid' => '0',
            'title' => 'Other Site Root',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'slug' => '/other',
            'sorting' => '1024',
            'deleted' => '0',
        ],
        [
            'uid' => '91',
            'pid' => '90',
            'title' => 'Storage of the other site',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'sorting' => '128',
            'deleted' => '0',
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
            'uid' => '3',
            'pid' => '11',
            'title' => 'Jena',
        ],
        // Related by a visible record, but deleted itself.
        [
            'uid' => '4',
            'pid' => '11',
            'title' => 'Gelöschte Stadt',
            'deleted' => '1',
        ],
        // Related by a visible record, but disabled itself.
        [
            'uid' => '5',
            'pid' => '11',
            'title' => 'Versteckte Stadt',
            'disable' => '1',
        ],
        // Only ever related by records that are themselves invisible.
        [
            'uid' => '6',
            'pid' => '11',
            'title' => 'Stadt ohne sichtbare Records',
        ],
        // Outside the scoped storage page, but pointed at by a record inside
        // it: a filter value is site-bound, not storage-bound, so it is offered.
        [
            'uid' => '7',
            'pid' => '12',
            'title' => 'Stadt im fremden Ordner',
        ],
        // In another site, pointed at by an in-scope record. Related to a
        // delivered record, yet dropped: the site bound is its own rule.
        [
            'uid' => '8',
            'pid' => '91',
            'title' => 'Stadt der anderen Site',
        ],
    ],
    'tx_thuecat_tourist_attraction' => [
        [
            'uid' => '1',
            'pid' => '11',
            'title' => 'Stadtmuseum Erfurt',
            'town' => '1',
        ],
        // Several towns in one column: the deleted and disabled ones drop out,
        // town 7 stays although it lives in another folder.
        [
            'uid' => '2',
            'pid' => '11',
            'title' => 'Attraktion in mehreren Städten',
            'town' => '2,3,4,5,7,8',
        ],
        // No town at all: the placeholder must not become an option.
        [
            'uid' => '3',
            'pid' => '11',
            'title' => 'Attraktion ohne Stadt',
            'town' => '0',
        ],
        [
            'uid' => '4',
            'pid' => '11',
            'title' => 'Gelöschte Attraktion',
            'town' => '6',
            'deleted' => '1',
        ],
        [
            'uid' => '5',
            'pid' => '11',
            'title' => 'Versteckte Attraktion',
            'town' => '6',
            'disable' => '1',
        ],
        // Outside the scoped storage page.
        [
            'uid' => '6',
            'pid' => '12',
            'title' => 'Attraktion im fremden Ordner',
            'town' => '7',
        ],
    ],
    'sys_category' => [
        [
            'uid' => '300',
            'pid' => '11',
            'parent' => '0',
            'title' => 'Kategorien',
        ],
        [
            'uid' => '301',
            'pid' => '11',
            'parent' => '300',
            'title' => 'Museum',
        ],
        [
            'uid' => '302',
            'pid' => '11',
            'parent' => '300',
            'title' => 'Kirche',
        ],
        [
            'uid' => '303',
            'pid' => '11',
            'parent' => '300',
            'title' => 'Gelöschte Kategorie',
            'deleted' => '1',
        ],
        // Below a used set, but hidden: the tree must not offer it.
        [
            'uid' => '304',
            'pid' => '11',
            'parent' => '301',
            'title' => 'Versteckte Kategorie',
            'hidden' => '1',
        ],
        // In another site, but parented into our tree, so it climbs to our
        // anchor like any local set. Only the site bound drops it.
        [
            'uid' => '307',
            'pid' => '91',
            'parent' => '301',
            'title' => 'Kategorie der anderen Site',
        ],
        // A parent cycle, 305 <-> 306, reachable from a used set. Without a
        // depth bound in the recursive term the query never terminates.
        [
            'uid' => '305',
            'pid' => '11',
            'parent' => '306',
            'title' => 'ZyklusA',
        ],
        [
            'uid' => '306',
            'pid' => '11',
            'parent' => '305',
            'title' => 'ZyklusB',
        ],
        [
            'uid' => '500',
            'pid' => '11',
            'parent' => '0',
            'title' => 'Keywords',
        ],
        [
            'uid' => '501',
            'pid' => '11',
            'parent' => '500',
            'title' => 'romantisch',
        ],
    ],
    'sys_category_record_mm' => [
        [
            'uid_local' => '301',
            'uid_foreign' => '1',
            'tablenames' => 'tx_thuecat_tourist_attraction',
            'fieldname' => 'categories',
            'sorting_foreign' => '1',
        ],
        // One record carrying two categories.
        [
            'uid_local' => '301',
            'uid_foreign' => '2',
            'tablenames' => 'tx_thuecat_tourist_attraction',
            'fieldname' => 'categories',
            'sorting_foreign' => '1',
        ],
        [
            'uid_local' => '302',
            'uid_foreign' => '2',
            'tablenames' => 'tx_thuecat_tourist_attraction',
            'fieldname' => 'categories',
            'sorting_foreign' => '2',
        ],
        // Deleted option record, related by a visible attraction.
        [
            'uid_local' => '303',
            'uid_foreign' => '1',
            'tablenames' => 'tx_thuecat_tourist_attraction',
            'fieldname' => 'categories',
            'sorting_foreign' => '3',
        ],
        // Related only by a deleted attraction.
        [
            'uid_local' => '302',
            'uid_foreign' => '4',
            'tablenames' => 'tx_thuecat_tourist_attraction',
            'fieldname' => 'categories',
            'sorting_foreign' => '1',
        ],
        // Same MM table, other field: must never surface among categories.
        [
            'uid_local' => '501',
            'uid_foreign' => '1',
            'tablenames' => 'tx_thuecat_tourist_attraction',
            'fieldname' => 'keywords',
            'sorting_foreign' => '1',
        ],
    ],
];
