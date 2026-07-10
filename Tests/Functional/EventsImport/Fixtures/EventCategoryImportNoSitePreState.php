<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

// storagePid 500 is a folder at the tree root with no site config → the import
// must abort in the pre-flight validator (StoragePidConfigurationException)
// BEFORE any URL is fetched.
return [
    'pages' => [
        [
            'uid' => '500',
            'pid' => '0',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'title' => 'Site-less storage folder',
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
                                <value index="vDEF">500</value>
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
