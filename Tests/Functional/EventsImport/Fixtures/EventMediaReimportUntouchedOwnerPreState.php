<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

// Pre-state proving the reap stays inside the run's scope: a second event,
// never fetched, must keep its reference.
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
            'title' => 'Event media reimport',
            'type' => 'static',
            'configuration' => '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
            <T3FlexForms>
                <data>
                    <sheet index="sDEF">
                        <language index="lDEF">
                            <field index="storagePid">
                                <value index="vDEF">10</value>
                            </field>
                            <field index="fileFolder">
                                <value index="vDEF">1:/thuecat/</value>
                            </field>
                            <field index="urls">
                                <el index="el">
                                    <field index="evt-referenced-media">
                                        <value index="url">
                                            <el>
                                                <field index="url">
                                                    <value index="vDEF">https://cdb.int.thuecat.org/api/resources/e_referenced_media-tdm</value>
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
    'tx_events_domain_model_event' => [
        0 => [
            'uid' => '1',
            'pid' => '10',
            'tstamp' => 1613400587,
            'crdate' => 1613400558,
            'remote_id' => 'https://thuecat.org/resources/e_referenced_media-tdm',
            'title' => 'Referenced Media Event',
            'images' => '2',
        ],
        1 => [
            'uid' => '2',
            'pid' => '10',
            'tstamp' => 1613400587,
            'crdate' => 1613400558,
            'remote_id' => 'https://thuecat.org/resources/e_untouched-tdm',
            'title' => 'Event this run never fetches',
            'images' => '1',
        ],
    ],
    'sys_file' => [
        0 => [
            'uid' => '2',
            'storage' => '1',
            'type' => '2',
            'extension' => 'jpg',
            'mime_type' => 'image/jpeg',
            'name' => 'image_3e6a3987344f6d38.jpg',
            'identifier' => '/thuecat/image_3e6a3987344f6d38.jpg',
            'identifier_hash' => 'ddab589f6efe1b16558b40e15094410a0c5e99e9',
            'folder_hash' => '7cd26a2efdc70daaac29904c75bc135bb21e3506',
        ],
        1 => [
            'uid' => '3',
            'storage' => '1',
            'type' => '2',
            'extension' => 'jpg',
            'mime_type' => 'image/jpeg',
            'name' => 'dms_999999999_Nicht-mehr-geliefert.jpg',
            'identifier' => '/thuecat/dms_999999999_Nicht-mehr-geliefert.jpg',
            'identifier_hash' => '8c45ade12419e1c31c84a7e2b42fe3f86e186f7b',
            'folder_hash' => '7cd26a2efdc70daaac29904c75bc135bb21e3506',
        ],
    ],
    'sys_file_reference' => [
        // Still supplied upstream — must survive, keeping its uid.
        0 => [
            'uid' => '1',
            'pid' => '10',
            'uid_local' => '2',
            'uid_foreign' => '1',
            'tablenames' => 'tx_events_domain_model_event',
            'fieldname' => 'images',
            'sorting_foreign' => '1',
        ],
        // No longer supplied — the reap must delete it, which it can only do
        // if it sweeps the field the owner declares.
        1 => [
            'uid' => '2',
            'pid' => '10',
            'uid_local' => '3',
            'uid_foreign' => '1',
            'tablenames' => 'tx_events_domain_model_event',
            'fieldname' => 'images',
            'sorting_foreign' => '2',
        ],
        2 => [
            'uid' => '3',
            'pid' => '10',
            'uid_local' => '2',
            'uid_foreign' => '2',
            'tablenames' => 'tx_events_domain_model_event',
            'fieldname' => 'images',
            'sorting_foreign' => '1',
        ],
    ],
];
