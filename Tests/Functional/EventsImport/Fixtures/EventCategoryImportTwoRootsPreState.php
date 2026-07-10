<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

// Cross-root dedup: a config with TWO event URLs (Kreuzchor + Distel) that both
// map to "Kulturveranstaltung" (type:thuecat:CultureEvent). resolve() runs once
// per URL, so without a run-scoped dedup map the shared category would be staged
// twice → duplicate sys_category rows. This is the production bug the earlier
// single-URL tests never exercised.
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
    ],
    'tx_thuecat_import_configuration' => [
        [
            'uid' => '1',
            'pid' => '0',
            'disable' => '0',
            'title' => 'Two-root event import',
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
                                    <field index="evt-distel">
                                        <value index="url">
                                            <el>
                                                <field index="url">
                                                    <value index="vDEF">https://cdb.int.thuecat.org/api/resources/e_100771372-hubev</value>
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
