<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

// The organisation already manages 60 events, more than 255 characters of
// comma-separated uids hold, and hosts event 950 in its other role.
$seeded = range(1000, 1059);

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
    'tx_thuecat_organisation' => [
        0 => [
            'uid' => '1',
            'pid' => '10',
            'sys_language_uid' => '0',
            'remote_id' => 'https://thuecat.org/resources/900000000040-ksw',
            'title' => 'Klassik Stiftung Weimar',
            'address_inline' => '1',
            'hosts_events' => '1',
            'manages_event' => (string)count($seeded),
        ],
    ],
    'tx_thuecat_organisation_hosts_events_mm' => [
        ['uid_local' => '1', 'uid_foreign' => '950', 'sorting' => '1'],
    ],
    'tx_thuecat_organisation_manages_event_mm' => array_map(
        static fn (int $uid): array => ['uid_local' => '1', 'uid_foreign' => (string)$uid, 'sorting' => (string)($uid - 999)],
        $seeded
    ),
    'tx_thuecat_address' => [
        0 => [
            'uid' => '1',
            'pid' => '10',
            'sys_language_uid' => '0',
            'parentid' => '1',
            'parenttable' => 'tx_thuecat_organisation',
            'remote_id' => 'https://thuecat.org/resources/900000000040-ksw::addr::0',
            'street' => 'Burgplatz 4',
            'zip' => '99423',
            'city' => 'Weimar',
        ],
    ],
    'tx_events_domain_model_event' => array_map(
        static fn (int $uid): array => [
            'uid' => (string)$uid,
            'pid' => '10',
            'sys_language_uid' => '0',
            'remote_id' => 'https://int.thuecat.org/resources/e_seeded' . $uid . '-hubev',
            'title' => 'Veranstaltung ' . $uid,
        ],
        [...$seeded, 950]
    ),
    'tx_thuecat_import_configuration' => [
        0 => [
            'uid' => '1',
            'pid' => '0',
            'tstamp' => 1613400587,
            'crdate' => 1613400558,
            'disable' => '0',
            'title' => 'Event organizer matching import',
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
                                    <field index="evt-orgmatch">
                                        <value index="url">
                                            <el>
                                                <field index="url">
                                                    <value index="vDEF">https://cdb.int.thuecat.org/api/resources/e_orgmatch-hubev</value>
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
