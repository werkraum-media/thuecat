<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

// Two roots: one whose managedBy reference is not a fetchable URL (raises in
// the resolver, caught per-root as mappingError), one whose managedBy 404s
// (skipped as a warning).
return [
    'pages' => [
        0 => [
            'uid' => '1',
            'pid' => '0',
            'tstamp' => '1613400587',
            'crdate' => '1613400558',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Rootpage',
            'is_siteroot' => '1',
        ],
        1 => [
            'uid' => '10',
            'pid' => '1',
            'tstamp' => '1613400587',
            'crdate' => '1613400558',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'title' => 'Storage folder',
        ],
    ],
    'tx_thuecat_import_configuration' => [
        0 => [
            'uid' => '1',
            'pid' => '0',
            'tstamp' => '1613400587',
            'crdate' => '1613400558',
            'disable' => '0',
            'title' => 'Skip and error',
            'type' => 'static',
            'configuration' => '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
            <T3FlexForms>
                <data>
                    <sheet index="sDEF">
                        <language index="lDEF">
                            <field index="storagePid">
                                <value index="vDEF">10</value>
                            </field>
                            <field index="urls">
                                <el index="el">
                                    <field index="602a89f54d694654233086">
                                        <value index="url">
                                            <el>
                                                <field index="url">
                                                    <value index="vDEF">https://thuecat.org/resources/unfetchable-reference</value>
                                                </field>
                                            </el>
                                        </value>
                                        <value index="_TOGGLE">0</value>
                                    </field>
                                    <field index="602a89f5e6c52907080672">
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
            </T3FlexForms>
        ',
        ],
    ],
];
