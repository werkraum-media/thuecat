<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

// Pre-state for the venue-reuse test. Carries a location row destination.data
// could have written: its global_id is the hash of the SAME venue the
// e_101155874-hubev payload delivers, so the import must attach to this row
// rather than create a second one.
//
// The hashed fields hold values the import would otherwise overwrite, and the
// non-hashed ones are empty, so a reuse that respects the hash boundary is
// distinguishable from one that rewrites the row wholesale.
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
            'title' => 'Event location reuse',
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
                                    <field index="evt-messe">
                                        <value index="url">
                                            <el>
                                                <field index="url">
                                                    <value index="vDEF">https://cdb.int.thuecat.org/api/resources/e_101155874-hubev</value>
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
    'tx_events_domain_model_location' => [
        0 => [
            'uid' => '77',
            'pid' => '10',
            // sha256 over name, street, zip, city, district, country of the
            // Messe Erfurt venue the fixture delivers.
            'global_id' => 'e0f2faeb81126342ae90a5445a7d7420fdcd421ab97b0cba09fbcad0695ae5fd',
            'name' => 'Messe Erfurt',
            'street' => 'Gothaer Straße 34',
            'zip' => '99094',
            'city' => 'Erfurt - Brühlervorstadt',
            'district' => '',
            'country' => 'Deutschland',
            // Not part of the hash: the import is allowed to fill these.
            'phone' => '',
            'latitude' => '',
            'longitude' => '',
        ],
        // A second venue that must stay untouched, so the assertion proves the
        // import matched on the hash rather than simply taking the only row.
        1 => [
            'uid' => '78',
            'pid' => '10',
            'global_id' => 'f1b2c3d4e5f60718293a4b5c6d7e8f9012345678901234567890abcdefabcdef',
            'name' => 'Ein anderer Ort',
            'street' => 'Andere Straße 1',
            'zip' => '99999',
            'city' => 'Anderswo',
            'district' => '',
            'country' => 'Deutschland',
            'phone' => '',
            'latitude' => '',
            'longitude' => '',
        ],
    ],
];
