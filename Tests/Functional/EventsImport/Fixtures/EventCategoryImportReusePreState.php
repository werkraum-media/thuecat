<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

// Reuse case: a category with remote_id "type:thuecat:CultureEvent" already
// exists as a GRANDCHILD of the configured parent (100 → 102 "Sub" → 101). The
// import must reuse uid 101 (rootline contains 100, though it is not a direct
// child, and matching is by remote_id) and create NO new category.
return [
    'pages' => [
        [
            'uid' => '1',
            'pid' => '0',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Rootpage',
            'is_siteroot' => '1',
        ],
        [
            'uid' => '10',
            'pid' => '1',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'title' => 'Event storage folder',
        ],
        [
            'uid' => '20',
            'pid' => '1',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'title' => 'Category storage folder',
        ],
    ],
    'sys_category' => [
        [
            'uid' => '100',
            'pid' => '20',
            'parent' => '0',
            'title' => 'Events',
        ],
        [
            'uid' => '102',
            'pid' => '20',
            'parent' => '100',
            'title' => 'Sub',
        ],
        [
            'uid' => '101',
            'pid' => '20',
            'parent' => '102',
            'title' => 'Kulturveranstaltung',
            'remote_id' => 'type:thuecat:CultureEvent',
        ],
    ],
    'tx_thuecat_import_configuration' => [
        [
            'uid' => '1',
            'pid' => '0',
            'disable' => '0',
            'title' => 'Kreuzchor event import',
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
                                    <field index="evt-kreuzchor">
                                        <value index="url">
                                            <el>
                                                <field index="url">
                                                    <value index="vDEF">https://cdb.int.thuecat.org/api/resources/e_19542-hubev</value>
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
