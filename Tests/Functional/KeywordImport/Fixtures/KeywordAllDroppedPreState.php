<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

// Prior import left the POI with two keyword relations; upstream now supplies
// none at all.
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
            'title' => 'Record storage folder',
        ],
        [
            'uid' => '20',
            'pid' => '1',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'title' => 'Category storage folder',
        ],
        [
            'uid' => '30',
            'pid' => '1',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'title' => 'Keyword storage folder',
        ],
    ],
    'sys_category' => [
        [
            'uid' => '100',
            'pid' => '20',
            'parent' => '0',
            'title' => 'Categories',
        ],
        [
            'uid' => '200',
            'pid' => '30',
            'parent' => '0',
            'title' => 'Keywords',
        ],
        [
            'uid' => '201',
            'pid' => '30',
            'parent' => '200',
            'title' => 'Landkreise',
            'remote_id' => 'keyword:https://thuecat.org/resources/155933862969-mofh',
        ],
        [
            'uid' => '202',
            'pid' => '30',
            'parent' => '201',
            'title' => 'Nicht mehr geliefert',
            'remote_id' => 'keyword:https://thuecat.org/resources/stale-term',
        ],
        [
            'uid' => '203',
            'pid' => '30',
            'parent' => '201',
            'title' => 'Landeshauptstadt Erfurt',
            'remote_id' => 'keyword:https://thuecat.org/resources/475728955106-qdcc',
        ],
    ],
    'tx_thuecat_tourist_attraction' => [
        [
            'uid' => '1',
            'pid' => '10',
            'sys_language_uid' => '0',
            'remote_id' => 'https://thuecat.org/resources/poi-without-keywords',
            'title' => 'Weingut Zahn',
            'keywords' => '2',
        ],
        [
            'uid' => '2',
            'pid' => '10',
            'sys_language_uid' => '0',
            'remote_id' => 'https://thuecat.org/resources/other-poi',
            'title' => 'Anderer POI',
            'keywords' => '1',
        ],
    ],
    'sys_category_record_mm' => [
        [
            'uid_local' => '203',
            'uid_foreign' => '1',
            'tablenames' => 'tx_thuecat_tourist_attraction',
            'fieldname' => 'keywords',
            'sorting_foreign' => '1',
        ],
        [
            'uid_local' => '202',
            'uid_foreign' => '1',
            'tablenames' => 'tx_thuecat_tourist_attraction',
            'fieldname' => 'keywords',
            'sorting_foreign' => '2',
        ],
        [
            'uid_local' => '203',
            'uid_foreign' => '2',
            'tablenames' => 'tx_thuecat_tourist_attraction',
            'fieldname' => 'keywords',
            'sorting_foreign' => '1',
        ],
    ],
    'tx_thuecat_import_configuration' => [
        [
            'uid' => '1',
            'pid' => '0',
            'disable' => '0',
            'title' => 'Keyword all-dropped import',
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
                            <field index="keywordStoragePid">
                                <value index="vDEF">30</value>
                            </field>
                            <field index="keywordParent">
                                <value index="vDEF">200</value>
                            </field>
                            <field index="urls">
                                <el index="el">
                                    <field index="poi-keyword">
                                        <value index="url">
                                            <el>
                                                <field index="url">
                                                    <value index="vDEF">https://thuecat.org/resources/poi-without-keywords</value>
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
