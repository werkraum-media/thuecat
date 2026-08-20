<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

// Two attraction roots, in this order: the first carries a schema:containedInPlace
// pointing at the second, so the second is fetched and staged as a depth-1
// relation before its own root turn arrives. Both @types of the second map to
// categories, so a loss shows up as a missing sys_category relation.
//
// Page tree: 3000 (siteroot) → 10 attraction storage, 20 category storage.
return [
    'pages' => [
        [
            'uid' => '3000',
            'pid' => '0',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Rootpage',
            'is_siteroot' => '1',
        ],
        [
            'uid' => '10',
            'pid' => '3000',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'title' => 'Attraction storage folder',
        ],
        [
            'uid' => '20',
            'pid' => '3000',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'title' => 'Category storage folder',
        ],
    ],
    'sys_category' => [
        [
            'uid' => '100',
            'pid' => '20',
            'parent' => '0',
            'title' => 'POIs',
        ],
    ],
    'tx_thuecat_import_configuration' => [
        [
            'uid' => '1',
            'pid' => '0',
            'disable' => '0',
            'title' => 'Attraction sighted as relation before its own root',
            'type' => 'static',
            'configuration' => '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
            <T3FlexForms>
                <data>
                    <sheet index="sDEF">
                        <language index="lDEF">
                            <field index="storagePid">
                                <value index="vDEF">10</value>
                            </field>
                            <field index="categoryStoragePid">
                                <value index="vDEF">20</value>
                            </field>
                            <field index="categoryParent">
                                <value index="vDEF">100</value>
                            </field>
                            <field index="urls">
                                <el index="el">
                                    <field index="referencing-root">
                                        <value index="url">
                                            <el>
                                                <field index="url">
                                                    <value index="vDEF">https://thuecat.org/resources/attraction-referencing-later-root</value>
                                                </field>
                                            </el>
                                        </value>
                                        <value index="_TOGGLE">0</value>
                                    </field>
                                    <field index="sighted-as-relation-first">
                                        <value index="url">
                                            <el>
                                                <field index="url">
                                                    <value index="vDEF">https://thuecat.org/resources/attraction-sighted-as-relation-first</value>
                                                </field>
                                            </el>
                                        </value>
                                        <value index="_TOGGLE">0</value>
                                    </field>
                                </el>
                            </field>
                        </language>
                    </sheet>
                </data>
            </T3FlexForms>',
        ],
    ],
];
