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
            'title' => 'Foreign organizer import',
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
                                    <field index="evt-organizer">
                                        <value index="url">
                                            <el>
                                                <field index="url">
                                                    <value index="vDEF">https://cdb.int.thuecat.org/api/resources/e_organizer-hubev</value>
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
    'tx_events_domain_model_organizer' => [
        0 => [
            'uid' => '55',
            'pid' => '10',
            // destination.data identifies organizers by name and writes no
            // identity column, so this stays empty. That is what marks the row
            // as foreign.
            'remote_id' => '',
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
];
