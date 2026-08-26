<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

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
            'uid' => '11',
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
            'title' => 'Trail With Stored Children',
            'type' => 'static',
            'configuration' => '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
            <T3FlexForms>
                <data>
                    <sheet index="sDEF">
                        <language index="lDEF">
                            <field index="storagePid">
                                <value index="vDEF">11</value>
                            </field>
                            <field index="urls">
                                <el index="el">
                                    <field index="602a89e212237114263881">
                                        <value index="url">
                                            <el>
                                                <field index="url">
                                                    <value index="vDEF">https://thuecat.org/resources/e_52469786-oatour</value>
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
    'tx_thuecat_trail' => [
        0 => [
            'uid' => '1',
            'pid' => '11',
            'remote_id' => 'https://thuecat.org/resources/e_52469786-oatour',
            'title' => 'Radtour "Nessetal-Radweg" - Von Erfurt nach Eisenach',
        ],
    ],
    'tx_thuecat_trail_way_type' => [
        0 => [
            'uid' => '1',
            'pid' => '11',
            'parentid' => '1',
            'parenttable' => 'tx_thuecat_trail',
            'remote_id' => 'https://thuecat.org/resources/e_52469786-oatour::wt::0',
            'title' => 'Asphalt',
        ],
        1 => [
            'uid' => '2',
            'pid' => '11',
            'parentid' => '1',
            'parenttable' => 'tx_thuecat_trail',
            'remote_id' => 'https://thuecat.org/resources/e_52469786-oatour::wt::1',
            'title' => 'Schotterweg',
        ],
        2 => [
            'uid' => '3',
            'pid' => '11',
            'parentid' => '1',
            'parenttable' => 'tx_thuecat_trail',
            'remote_id' => 'https://thuecat.org/resources/e_52469786-oatour::wt::2',
            'title' => 'Naturweg',
        ],
        3 => [
            'uid' => '4',
            'pid' => '11',
            'parentid' => '1',
            'parenttable' => 'tx_thuecat_trail',
            'remote_id' => 'https://thuecat.org/resources/e_52469786-oatour::wt::3',
            'title' => 'Pfad',
        ],
        4 => [
            'uid' => '5',
            'pid' => '11',
            'parentid' => '1',
            'parenttable' => 'tx_thuecat_trail',
            'remote_id' => 'https://thuecat.org/resources/e_52469786-oatour::wt::4',
            'title' => 'Straße',
        ],
    ],
];
