<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

// Pages 20-23: filtered plugin with storagePid/filter on/off. "Other Pid"
// (pid 12) tells the unrestricted cases apart from the restricted ones.

$townFlexform = '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
<T3FlexForms>
    <data>
        <sheet index="sDEF">
            <language index="lDEF">
                <field index="settings.towns">
                    <value index="vDEF">1</value>
                </field>
            </language>
        </sheet>
    </data>
</T3FlexForms>';

$plugin = static function (int $uid, int $pid, string $pages, string $flexform = ''): array {
    return [
        'uid' => (string)$uid,
        'pid' => (string)$pid,
        'CType' => 'werkraummedia_thuecatattractionlistfiltered',
        'header' => 'Attraction List',
        'colPos' => '0',
        'sys_language_uid' => '0',
        'pages' => $pages,
        'recursive' => '0',
        'pi_flexform' => $flexform,
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
        [
            'uid' => '20',
            'pid' => '1',
            'title' => 'Storage + no filter',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'slug' => '/storage-nofilter/',
            'sorting' => '256',
            'deleted' => '0',
        ],
        [
            'uid' => '21',
            'pid' => '1',
            'title' => 'No storage + no filter',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'slug' => '/nostorage-nofilter/',
            'sorting' => '257',
            'deleted' => '0',
        ],
        [
            'uid' => '22',
            'pid' => '1',
            'title' => 'Storage + filter',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'slug' => '/storage-filter/',
            'sorting' => '258',
            'deleted' => '0',
        ],
        [
            'uid' => '23',
            'pid' => '1',
            'title' => 'No storage + filter',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'slug' => '/nostorage-filter/',
            'sorting' => '259',
            'deleted' => '0',
        ],
        [
            'uid' => '11',
            'pid' => '1',
            'title' => 'Storage for Attractions',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'sorting' => '260',
            'deleted' => '0',
        ],
        [
            'uid' => '12',
            'pid' => '1',
            'title' => 'Other Storage',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'sorting' => '261',
            'deleted' => '0',
        ],
    ],
    'tt_content' => [
        $plugin(20, 20, '11'),
        $plugin(21, 21, ''),
        $plugin(22, 22, '11', $townFlexform),
        $plugin(23, 23, '', $townFlexform),
    ],
    'tx_thuecat_town' => [
        ['uid' => '1', 'pid' => '11', 'title' => 'Erfurt'],
        ['uid' => '2', 'pid' => '11', 'title' => 'Weimar'],
    ],
    'tx_thuecat_tourist_attraction' => [
        ['uid' => '1', 'pid' => '11', 'title' => 'Stadtmuseum Erfurt', 'town' => '1'],
        ['uid' => '2', 'pid' => '11', 'title' => 'Goethehaus Weimar', 'town' => '2'],
        ['uid' => '3', 'pid' => '12', 'title' => 'Other Pid Erfurt', 'town' => '1'],
    ],
];
