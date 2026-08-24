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
            'uid' => '10',
            'pid' => '1',
            'tstamp' => '1613400587',
            'crdate' => '1613400558',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'title' => 'Storage folder',
        ],
    ],
    // State left by a first import; the run under test updates it in place.
    'tx_thuecat_tourist_attraction' => [
        0 => [
            'uid' => '1',
            'pid' => '10',
            'sys_language_uid' => '0',
            'remote_id' => 'https://thuecat.org/resources/900000000001-goet',
            'title' => 'Beispielhaus im Park',
            'address_inline' => '1',
        ],
        1 => [
            'uid' => '2',
            'pid' => '10',
            'sys_language_uid' => '1',
            'l18n_parent' => '1',
            'l10n_source' => '1',
            'remote_id' => 'https://thuecat.org/resources/900000000001-goet',
            'title' => 'Example house in the park',
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
            'remote_id' => 'https://thuecat.org/resources/900000000001-goet::addr::0',
            'street' => 'Beispielweg 5',
            'zip' => '99425',
            'city' => 'Beispielstadt',
            'email' => 'info@example.com',
            'phone' => '+49 3643 545400',
            'latitude' => '50.974722',
            'longitude' => '11.331389',
        ],
        1 => [
            'uid' => '2',
            'pid' => '10',
            'sys_language_uid' => '1',
            'l18n_parent' => '1',
            'l18n_source' => '1',
            'parentid' => '2',
            'parenttable' => 'tx_thuecat_tourist_attraction',
            'remote_id' => 'https://thuecat.org/resources/900000000001-goet::addr::0',
            'street' => 'Example Lane 5',
            'zip' => '99423',
            'city' => 'Beispielstadt',
            'latitude' => '50.974722',
            'longitude' => '11.331389',
        ],
    ],
    'tx_thuecat_import_configuration' => [
        0 => [
            'uid' => '1',
            'pid' => '0',
            'tstamp' => '1613400587',
            'crdate' => '1613400558',
            'disable' => '0',
            'title' => 'Attraction with a translated address',
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
                                                    <value index="vDEF">https://thuecat.org/resources/900000000001-goet</value>
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
