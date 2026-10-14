<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

$selectedListFlexform = static function (string $selectedRecords, string $showPage = ''): string {
    return '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
<T3FlexForms>
    <data>
        <sheet index="sDEF">
            <language index="lDEF">
                <field index="settings.selectedRecords">
                    <value index="vDEF">' . $selectedRecords . '</value>
                </field>
                <field index="settings.page.pid.thuecat_attraction_show">
                    <value index="vDEF">' . $showPage . '</value>
                </field>
            </language>
        </sheet>
    </data>
</T3FlexForms>';
};

// The role is l10n_mode exclude, which DataHandler copies into translations.
// The data set bypasses DataHandler, so translated pages carry it explicitly.
return [
    'pages' => [
        [
            'uid' => '1',
            'pid' => '0',
            'title' => 'Root',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'slug' => '/',
            'sorting' => '128',
        ],
        [
            'uid' => '10',
            'pid' => '1',
            'title' => 'Attraktion',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'slug' => '/show',
            'sorting' => '256',
            'tx_thuecat_page_role' => 'thuecat_touristattractionshow',
        ],
        [
            'uid' => '110',
            'pid' => '1',
            'title' => 'Attraction',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'slug' => '/show',
            'sorting' => '256',
            'sys_language_uid' => '1',
            'l10n_parent' => '10',
            'tx_thuecat_page_role' => 'thuecat_touristattractionshow',
        ],
        [
            'uid' => '11',
            'pid' => '1',
            'title' => 'Storage',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'sorting' => '512',
        ],
        [
            'uid' => '111',
            'pid' => '1',
            'title' => 'Storage EN',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'sorting' => '512',
            'sys_language_uid' => '1',
            'l10n_parent' => '11',
        ],
        [
            'uid' => '30',
            'pid' => '1',
            'title' => 'Wanderweg',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'slug' => '/trail',
            'sorting' => '768',
            'tx_thuecat_page_role' => 'thuecat_trailshow',
        ],
        [
            'uid' => '40',
            'pid' => '1',
            'title' => 'Liste',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'slug' => '/list',
            'sorting' => '1024',
            'tx_thuecat_page_role' => 'thuecat_touristattractionlist',
        ],
        [
            'uid' => '140',
            'pid' => '1',
            'title' => 'List',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'slug' => '/list',
            'sorting' => '1024',
            'sys_language_uid' => '1',
            'l10n_parent' => '40',
            'tx_thuecat_page_role' => 'thuecat_touristattractionlist',
        ],
        // Shows a page without role: links to it keep the query-string form.
        [
            'uid' => '50',
            'pid' => '1',
            'title' => 'Attraktion ohne Rolle',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'slug' => '/show-without-role',
            'sorting' => '1280',
        ],
        [
            'uid' => '51',
            'pid' => '1',
            'title' => 'Auswahl',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'slug' => '/selection',
            'sorting' => '1536',
        ],
        [
            'uid' => '2000',
            'pid' => '0',
            'title' => 'Second Root',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'slug' => '/',
            'is_siteroot' => '1',
            'sorting' => '2048',
        ],
        [
            'uid' => '2001',
            'pid' => '2000',
            'title' => 'Second Storage',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'sorting' => '256',
        ],
    ],
    'tt_content' => [
        [
            'uid' => '10',
            'pid' => '10',
            'CType' => 'werkraummedia_thuecatattractionshow',
            'colPos' => '0',
            'sorting' => '256',
            'sys_language_uid' => '0',
        ],
        [
            'uid' => '110',
            'pid' => '10',
            'CType' => 'werkraummedia_thuecatattractionshow',
            'colPos' => '0',
            'sorting' => '256',
            'sys_language_uid' => '1',
            'l18n_parent' => '10',
        ],
        [
            'uid' => '30',
            'pid' => '30',
            'CType' => 'werkraummedia_thuecattrailshow',
            'colPos' => '0',
            'sorting' => '256',
            'sys_language_uid' => '0',
        ],
        [
            'uid' => '40',
            'pid' => '40',
            'CType' => 'werkraummedia_thuecatattractionlist',
            'colPos' => '0',
            'sorting' => '256',
            'sys_language_uid' => '0',
        ],
        [
            'uid' => '140',
            'pid' => '40',
            'CType' => 'werkraummedia_thuecatattractionlist',
            'colPos' => '0',
            'sorting' => '256',
            'sys_language_uid' => '1',
            'l18n_parent' => '40',
        ],
        [
            'uid' => '41',
            'pid' => '40',
            'CType' => 'werkraummedia_thuecattraillistselected',
            'colPos' => '0',
            'sorting' => '512',
            'sys_language_uid' => '0',
            'pi_flexform' => $selectedListFlexform('50'),
        ],
        [
            'uid' => '51',
            'pid' => '51',
            'CType' => 'werkraummedia_thuecatattractionlistselected',
            'colPos' => '0',
            'sorting' => '256',
            'sys_language_uid' => '0',
            'pi_flexform' => $selectedListFlexform('24', '50'),
        ],
        [
            'uid' => '50',
            'pid' => '50',
            'CType' => 'werkraummedia_thuecatattractionshow',
            'colPos' => '0',
            'sorting' => '256',
            'sys_language_uid' => '0',
        ],
    ],
    // Three default-language records at two per page: the list has two pages.
    'tx_thuecat_tourist_attraction' => [
        [
            'uid' => '21',
            'pid' => '11',
            'title' => 'Gemeindebrunnen Weimar',
            'slug' => 'gemeindebrunnen-weimar',
            'description' => '',
            'town' => '0',
            'url' => '',
            'offers' => '',
            'sys_language_uid' => '0',
        ],
        [
            'uid' => '22',
            'pid' => '11',
            'title' => 'Community Fountain Weimar',
            'slug' => 'community-fountain-weimar',
            'description' => '',
            'town' => '0',
            'url' => '',
            'offers' => '',
            'sys_language_uid' => '1',
            'l18n_parent' => '21',
        ],
        [
            'uid' => '23',
            'pid' => '11',
            'title' => 'Goethehaus Weimar',
            'slug' => 'goethehaus-weimar',
            'description' => '',
            'town' => '0',
            'url' => '',
            'offers' => '',
            'sys_language_uid' => '0',
        ],
        [
            'uid' => '24',
            'pid' => '11',
            'title' => 'Herderkirche Weimar',
            'slug' => 'herderkirche-weimar',
            'description' => '',
            'town' => '0',
            'url' => '',
            'offers' => '',
            'sys_language_uid' => '0',
        ],
        // Stored in the second site: its slug is foreign to the first site.
        [
            'uid' => '25',
            'pid' => '2001',
            'title' => 'Wartburg',
            'slug' => 'wartburg',
            'description' => '',
            'town' => '0',
            'url' => '',
            'offers' => '',
            'sys_language_uid' => '0',
        ],
    ],
    'tx_thuecat_trail' => [
        [
            'uid' => '50',
            'pid' => '11',
            'title' => 'Rennsteig',
            'slug' => 'rennsteig',
            'sys_language_uid' => '0',
        ],
    ],
];
