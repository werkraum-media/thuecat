<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

// The attraction is titled with a hyphen where the payload writes a space, so
// only normalization can bring the two together. Uid 2 shares the postal code
// under a different name, so a match on postal code alone would pick it up.
return [
    'pages' => [
        0 => [
            'uid' => '2000',
            'pid' => '0',
            'tstamp' => 1613400587,
            'crdate' => 1613400558,
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Rootpage',
            'is_siteroot' => '1',
        ],
        1 => [
            'uid' => '10',
            'pid' => '2000',
            'tstamp' => 1613400587,
            'crdate' => 1613400558,
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'title' => 'Storage folder',
        ],
    ],
    'tx_thuecat_tourist_attraction' => [
        0 => [
            'uid' => '1',
            'pid' => '10',
            'sys_language_uid' => '0',
            'remote_id' => 'https://thuecat.org/resources/900000000020-bauh',
            'title' => 'Bauhaus-Museum Weimar',
            'address_inline' => '1',
        ],
        1 => [
            'uid' => '2',
            'pid' => '10',
            'sys_language_uid' => '0',
            'remote_id' => 'https://thuecat.org/resources/900000000021-nietz',
            'title' => 'Nietzsche-Archiv',
            'address_inline' => '1',
        ],
    ],
    'tx_thuecat_address' => [
        0 => [
            'uid' => '1',
            'pid' => '10',
            'sys_language_uid' => '0',
            'parentid' => '1',
            'parenttable' => 'tx_thuecat_tourist_attraction',
            'remote_id' => 'https://thuecat.org/resources/900000000020-bauh::addr::0',
            'street' => 'Stéphane-Hessel-Platz 1',
            'zip' => '99423',
            'city' => 'Weimar',
        ],
        1 => [
            'uid' => '2',
            'pid' => '10',
            'sys_language_uid' => '0',
            'parentid' => '2',
            'parenttable' => 'tx_thuecat_tourist_attraction',
            'remote_id' => 'https://thuecat.org/resources/900000000021-nietz::addr::0',
            'street' => 'Humboldtstraße 36',
            'zip' => '99423',
            'city' => 'Weimar',
        ],
    ],
    'tx_thuecat_import_configuration' => [
        0 => [
            'uid' => '1',
            'pid' => '0',
            'tstamp' => 1613400587,
            'crdate' => 1613400558,
            'disable' => '0',
            'title' => 'Event place matching import',
            'type' => 'static',
            'configuration' => '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
            <T3FlexForms>
                <data>
                    <sheet index="sDEF">
                        <language index="lDEF">
                            <field index="storagePid">
                                <value index="vDEF">10</value>
                            </field>
                            <field index="importTarget">
                                <value index="vDEF">events</value>
                            </field>
                            <field index="urls">
                                <el index="el">
                                    <field index="evt-bauhaus">
                                        <value index="url">
                                            <el>
                                                <field index="url">
                                                    <value index="vDEF">https://cdb.int.thuecat.org/api/resources/e_bauhaus-hubev</value>
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
