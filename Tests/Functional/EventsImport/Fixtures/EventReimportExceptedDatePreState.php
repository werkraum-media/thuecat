<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

// Pre-state for reaping an occurrence that became excepted after it was stored:
// the weekly event already imported with every occurrence its schedule produced
// at the time, including the one the schedule now excepts.
return [
    'pages' => [
        0 => [
            'uid' => '1',
            'pid' => '0',
            'tstamp' => 1613400587,
            'crdate' => 1613400558,
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Rootpage',
            'is_siteroot' => '1',
        ],
        1 => [
            'uid' => '10',
            'pid' => '1',
            'tstamp' => 1613400587,
            'crdate' => 1613400558,
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'title' => 'Storage folder',
        ],
    ],
    'tx_events_domain_model_event' => [
        0 => [
            'uid' => '1',
            'pid' => '10',
            'remote_id' => 'https://thuecat.org/resources/e_7cbe5bb1-160b-4916-802c-c64dd2f1bf9e-tdm',
            'title' => 'Test-Altstadtführung',
        ],
    ],
    'tx_events_domain_model_date' => [
        0 => [
            'uid' => '1',
            'pid' => '10',
            'remote_id' => 'https://thuecat.org/resources/e_7cbe5bb1-160b-4916-802c-c64dd2f1bf9e-tdm::date::2026-12-03T14:00:00+01:00',
            'event' => '1',
            'start' => 1796302800,
            'end' => 1796310000,
            'canceled' => 'no',
        ],
        1 => [
            'uid' => '2',
            'pid' => '10',
            'remote_id' => 'https://thuecat.org/resources/e_7cbe5bb1-160b-4916-802c-c64dd2f1bf9e-tdm::date::2026-12-10T14:00:00+01:00',
            'event' => '1',
            'start' => 1796907600,
            'end' => 1796914800,
            'canceled' => 'no',
        ],
        2 => [
            'uid' => '3',
            'pid' => '10',
            'remote_id' => 'https://thuecat.org/resources/e_7cbe5bb1-160b-4916-802c-c64dd2f1bf9e-tdm::date::2026-12-17T14:00:00+01:00',
            'event' => '1',
            'start' => 1797512400,
            'end' => 1797519600,
            'canceled' => 'no',
        ],
        3 => [
            'uid' => '4',
            'pid' => '10',
            'remote_id' => 'https://thuecat.org/resources/e_7cbe5bb1-160b-4916-802c-c64dd2f1bf9e-tdm::date::2026-12-24T14:00:00+01:00',
            'event' => '1',
            'start' => 1798117200,
            'end' => 1798124400,
            'canceled' => 'no',
        ],
        4 => [
            'uid' => '5',
            'pid' => '10',
            'remote_id' => 'https://thuecat.org/resources/e_7cbe5bb1-160b-4916-802c-c64dd2f1bf9e-tdm::date::2026-12-31T14:00:00+01:00',
            'event' => '1',
            'start' => 1798722000,
            'end' => 1798729200,
            'canceled' => 'no',
        ],
    ],
    'tx_thuecat_import_configuration' => [
        0 => [
            'uid' => '1',
            'pid' => '0',
            'tstamp' => 1613400587,
            'crdate' => 1613400558,
            'disable' => '0',
            'title' => 'Weekly event import',
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
                                    <field index="evt-weekly">
                                        <value index="url">
                                            <el>
                                                <field index="url">
                                                    <value index="vDEF">https://cdb.int.thuecat.org/api/resources/e_7cbe5bb1-tdm</value>
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
