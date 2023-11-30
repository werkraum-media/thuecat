<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

return  [
    'be_users' => [
        0 => [
            'uid' => '1',
            'pid' => '0',
            'tstamp' => '1366642540',
            'username' => 'admin',
            'password' => '$1$tCrlLajZ$C0sikFQQ3SWaFAZ1Me0Z/1',
            'admin' => '1',
            'disable' => '0',
            'starttime' => '0',
            'endtime' => '0',
            'options' => '0',
            'crdate' => '1366642540',
            'workspace_perms' => '1',
            'deleted' => '0',
            'TSconfig' => null,
            'lastlogin' => '1371033743',
            'workspace_id' => '0',
        ],
    ],
    'pages' => [
        0 => [
            'uid' => '1',
            'pid' => '0',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'slug' => '/',
            'title' => 'Rootpage',
        ],
        1 => [
            'uid' => '2',
            'pid' => '1',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'slug' => '/storage',
            'title' => 'Storage',
        ],
    ],
    'tx_thuecat_import_configuration' => [
        0 => [
            'uid' => '1',
            'pid' => '2',
            'title' => 'Example Configuration',
            'type' => 'static',
            'configuration' => '<?xml version="1.0" encoding="utf-8" standalone="yes" ?><T3FlexForms>
                <data>
                    <sheet index="sDEF">
                        <language index="lDEF">
                            <field index="storagePid">
                                <value index="vDEF">2</value>
                            </field>
                            <field index="urls">
                                <el index="el">
                                    <field index="633554a57c83b383375701">
                                        <value index="url">
                                            <el>
                                                <field index="url">
                                                    <value index="vDEF">https://thuecat.org/resources/644315157726-jmww</value>
                                                </field>
                                            </el>
                                        </value>
                                        <value index="_TOGGLE">0</value>
                                    </field>
                                    <field index="633551f49acee985442403">
                                        <value index="url">
                                            <el>
                                                <field index="url">
                                                    <value index="vDEF">https://thuecat.org/resources/072778761562-kwah</value>
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
