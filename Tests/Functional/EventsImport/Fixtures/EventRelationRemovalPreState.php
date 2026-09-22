<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

// Pre-state for the event organizer relation test.
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
    'tx_thuecat_import_configuration' => [
        0 => [
            'uid' => '1',
            'pid' => '0',
            'tstamp' => 1613400587,
            'crdate' => 1613400558,
            'disable' => '0',
            'title' => 'Relation removal import',
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
                                    <field index="evt-no-organizer">
                                        <value index="url">
                                            <el>
                                                <field index="url">
                                                    <value index="vDEF">https://cdb.int.thuecat.org/api/resources/e_noorg-hubev</value>
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
    'tx_events_domain_model_event' => [
        0 => [
            'uid' => '1',
            'pid' => '10',
            'remote_id' => 'https://int.thuecat.org/resources/e_noorg-hubev',
            'title' => 'Veranstaltung ohne Veranstalter',
            'organizer' => '55',
            'location' => '77',
        ],
    ],
    'tx_events_domain_model_organizer' => [
        0 => [
            'uid' => '55',
            'pid' => '10',
            'remote_id' => 'thuecat:organizer:0a4191f8ead00647fc836566878243f5d7cb60e146593e06929d03b1e5670806',
            'name' => 'Erfurt Tourismus und Marketing GmbH',
            'street' => 'Benediktsplatz 1',
            'zip' => '99084',
            'city' => 'Erfurt',
            'district' => '',
            'phone' => '+49 361 66 400',
            'email' => 'info@erfurt-tourismus.de',
            'web' => 'http://www.erfurt-tourismus.de/',
            'sys_language_uid' => '-1',
        ],
    ],
    'tx_events_domain_model_location' => [
        0 => [
            'uid' => '77',
            'pid' => '10',
            'global_id' => 'e0f2faeb81126342ae90a5445a7d7420fdcd421ab97b0cba09fbcad0695ae5fd',
            'name' => 'Messe Erfurt',
            'street' => 'Gothaer Straße 34',
            'zip' => '99094',
            'city' => 'Erfurt - Brühlervorstadt',
            'district' => '',
            'country' => 'Deutschland',
            'phone' => '',
            'latitude' => '',
            'longitude' => '',
            'sys_language_uid' => '-1',
        ],
    ],
];
