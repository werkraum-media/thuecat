<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

// Pre-state for a failure alongside a success: the owner still collects
// media, so it enters the flush and the failed image's reference is at risk.
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
    'tx_thuecat_import_configuration' => [
        0 => [
            'uid' => '1',
            'pid' => '0',
            'tstamp' => 1613400587,
            'crdate' => 1613400558,
            'disable' => '0',
            'title' => 'Event mixed media reimport',
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
                                    <field index="evt-mixed-media">
                                        <value index="url">
                                            <el>
                                                <field index="url">
                                                    <value index="vDEF">https://cdb.int.thuecat.org/api/resources/e_mixed_media-tdm</value>
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
            'remote_id' => 'https://thuecat.org/resources/e_mixed_media-tdm',
            'title' => 'Mixed Media Event',
            'images' => '2',
        ],
    ],
    'sys_file' => [
        // Still supplied: the referenced image.
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
        // Still supplied: the inline image.
        1 => [
            'uid' => '3',
            'storage' => '1',
            'type' => '2',
            'extension' => 'jpg',
            'mime_type' => 'image/jpeg',
            'name' => 'c4915c4a-9a68-4a51-8d4e-782158f6887d_31222b4549bdcfaa.jpg',
            'identifier' => '/thuecat/c4915c4a-9a68-4a51-8d4e-782158f6887d_31222b4549bdcfaa.jpg',
            'identifier_hash' => '15c71f28533818439f319329f3fd9c58b3ea9e25',
            'folder_hash' => '7cd26a2efdc70daaac29904c75bc135bb21e3506',
        ],
    ],
    'sys_file_reference' => [
        0 => [
            'uid' => '1',
            'pid' => '10',
            'uid_local' => '2',
            'uid_foreign' => '1',
            'tablenames' => 'tx_events_domain_model_event',
            'fieldname' => 'images',
            'sorting_foreign' => '1',
        ],
        1 => [
            'uid' => '2',
            'pid' => '10',
            'uid_local' => '3',
            'uid_foreign' => '1',
            'tablenames' => 'tx_events_domain_model_event',
            'fieldname' => 'images',
            'sorting_foreign' => '2',
        ],
    ],
];
