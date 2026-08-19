<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

// Two sites, each with its own storage folders and its own import
// configuration, both pointing at the same upstream town. Configuration 1
// belongs to site 4000, configuration 2 to site 5000.
return [
    'pages' => [
        [
            'uid' => '4000',
            'pid' => '0',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'First site root',
            'is_siteroot' => '1',
        ],
        [
            'uid' => '4010',
            'pid' => '4000',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'title' => 'First site record storage',
        ],
        [
            'uid' => '4020',
            'pid' => '4000',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'title' => 'First site category storage',
        ],
        [
            'uid' => '4030',
            'pid' => '4000',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'title' => 'First site keyword storage',
        ],
        [
            // Carries no configured role, so a test may hide or delete it
            // without invalidating the import configuration itself.
            'uid' => '4040',
            'pid' => '4000',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'title' => 'First site spare folder',
        ],
        [
            'uid' => '5000',
            'pid' => '0',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Second site root',
            'is_siteroot' => '1',
        ],
        [
            'uid' => '5010',
            'pid' => '5000',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'title' => 'Second site record storage',
        ],
        [
            'uid' => '5020',
            'pid' => '5000',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'title' => 'Second site category storage',
        ],
        [
            'uid' => '5030',
            'pid' => '5000',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'title' => 'Second site keyword storage',
        ],
    ],
    'sys_category' => [
        [
            'uid' => '4100',
            'pid' => '4020',
            'parent' => '0',
            'title' => 'First site categories',
        ],
        [
            'uid' => '4200',
            'pid' => '4030',
            'parent' => '0',
            'title' => 'First site keywords',
        ],
        [
            'uid' => '5100',
            'pid' => '5020',
            'parent' => '0',
            'title' => 'Second site categories',
        ],
        [
            'uid' => '5200',
            'pid' => '5030',
            'parent' => '0',
            'title' => 'Second site keywords',
        ],
    ],
    'tx_thuecat_import_configuration' => [
        [
            'uid' => '1',
            'pid' => '0',
            'disable' => '0',
            'title' => 'First site town import',
            'type' => 'static',
            'configuration' => '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
            <T3FlexForms>
                <data>
                    <sheet index="sDEF">
                        <language index="lDEF">
                            <field index="storagePid">
                                <value index="vDEF">4010</value>
                            </field>
                            <field index="urls">
                                <el index="el">
                                    <field index="602a89f54d694654233086">
                                        <value index="url">
                                            <el>
                                                <field index="url">
                                                    <value index="vDEF">https://thuecat.org/resources/043064193523-jcyt</value>
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
        [
            'uid' => '2',
            'pid' => '0',
            'disable' => '0',
            'title' => 'Second site town import',
            'type' => 'static',
            'configuration' => '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
            <T3FlexForms>
                <data>
                    <sheet index="sDEF">
                        <language index="lDEF">
                            <field index="storagePid">
                                <value index="vDEF">5010</value>
                            </field>
                            <field index="urls">
                                <el index="el">
                                    <field index="602a89f54d694654233086">
                                        <value index="url">
                                            <el>
                                                <field index="url">
                                                    <value index="vDEF">https://thuecat.org/resources/043064193523-jcyt</value>
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
