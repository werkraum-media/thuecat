<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

// Pre-state for the reap-scoping tests: alongside the event the run imports
// sits a second event with its own dates, one of which is stale. The run never
// touches that event, so all of its dates must survive.
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
    'tx_events_domain_model_event' => [
        0 => [
            'uid' => '1',
            'pid' => '10',
            'remote_id' => 'https://int.thuecat.org/resources/e_19542-hubev',
            'title' => 'Konzert des Dresdner Kreuzchores',
        ],
        1 => [
            'uid' => '2',
            'pid' => '10',
            'remote_id' => 'https://int.thuecat.org/resources/e_not-in-this-run',
            'title' => 'Event not part of this run',
        ],
    ],
    'tx_events_domain_model_date' => [
        0 => [
            'uid' => '1',
            'pid' => '10',
            'remote_id' => 'https://int.thuecat.org/resources/e_19542-hubev::date::2026-11-29T18:00:00+01:00',
            'event' => '1',
            'start' => 1795971600,
            'end' => 1795971600,
            'canceled' => 'no',
        ],
        1 => [
            'uid' => '2',
            'pid' => '10',
            'remote_id' => 'https://int.thuecat.org/resources/e_not-in-this-run::date::2026-10-04T18:00:00+02:00',
            'event' => '2',
            'start' => 1791136800,
            'end' => 1791136800,
            'canceled' => 'no',
        ],
        2 => [
            'uid' => '3',
            'pid' => '10',
            'remote_id' => 'https://int.thuecat.org/resources/e_not-in-this-run::date::2026-10-11T18:00:00+02:00',
            'event' => '2',
            'start' => 1791741600,
            'end' => 1791741600,
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
            'title' => 'Kreuzchor event import',
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
                                    <field index="evt-kreuzchor">
                                        <value index="url">
                                            <el>
                                                <field index="url">
                                                    <value index="vDEF">https://cdb.int.thuecat.org/api/resources/e_19542-hubev</value>
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
