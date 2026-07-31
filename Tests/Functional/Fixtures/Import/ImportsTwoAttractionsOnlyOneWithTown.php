<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

// Two attractions in ONE run, town-carrying one first. Order is load-bearing:
// a bucket only leaks into a record parsed after one that filled it.
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
            'title' => 'Two attractions, only one with a town',
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
                        <field index="69eafde91e333263046182">
                            <value index="url">
                                <el>
                                    <field index="url">
                                        <value index="vDEF">https://thuecat.org/resources/attraction-with-town</value>
                                    </field>
                                </el>
                            </value>
                        </field>
                        <field index="69eb076056d09902142246">
                            <value index="url">
                                <el>
                                    <field index="url">
                                        <value index="vDEF">https://thuecat.org/resources/attraction-without-town</value>
                                    </field>
                                </el>
                            </value>
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
