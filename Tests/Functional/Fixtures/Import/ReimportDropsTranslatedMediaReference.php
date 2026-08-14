<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

// As the reimport state, plus a fifth image pair (default-language ref 9 and
// its translation 10) that upstream no longer supplies. Reaping the default
// row must take the translation with it.
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
    'tx_thuecat_import_configuration' => [
        0 => [
            'uid' => '1',
            'pid' => '0',
            'tstamp' => '1613400587',
            'crdate' => '1613400558',
            'disable' => '0',
            'title' => 'Tourist Attraction',
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
                                    <field index="602a89f54d694654233086">
                                        <value index="url">
                                            <el>
                                                <field index="url">
                                                    <value index="vDEF">https://thuecat.org/resources/attraction-with-media</value>
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
    'tx_thuecat_tourist_attraction' => [
        0 => [
            'uid' => '1',
            'pid' => '10',
            'sys_language_uid' => '0',
            'remote_id' => 'https://thuecat.org/resources/attraction-with-media',
            'title' => 'Attraktion mit Bildern',
            'media_files' => '5',
        ],
        1 => [
            'uid' => '2',
            'pid' => '10',
            'sys_language_uid' => '1',
            'l18n_parent' => '1',
            'remote_id' => 'https://thuecat.org/resources/attraction-with-media',
            'title' => 'Attraction with media',
            'media_files' => '5',
        ],
    ],
    'sys_file' => [
        0 => [
            'uid' => '2',
            'storage' => '1',
            'type' => '2',
            'extension' => 'jpg',
            'mime_type' => 'image/jpeg',
            'name' => 'image_6ab24be70ef3f2e8.jpg',
            'identifier' => '/thuecat/image_6ab24be70ef3f2e8.jpg',
            'identifier_hash' => '29c4571230ed6d5da323206b256b3e44658d4b7b',
            'folder_hash' => '7cd26a2efdc70daaac29904c75bc135bb21e3506',
        ],
        1 => [
            'uid' => '3',
            'storage' => '1',
            'type' => '2',
            'extension' => 'jpg',
            'mime_type' => 'image/jpeg',
            'name' => 'image_89d8f4e95612e13d.jpg',
            'identifier' => '/thuecat/image_89d8f4e95612e13d.jpg',
            'identifier_hash' => 'bf4ec96a5ecf8ffa4b3fe9d07a3c1b1d91ccee71',
            'folder_hash' => '7cd26a2efdc70daaac29904c75bc135bb21e3506',
        ],
        2 => [
            'uid' => '4',
            'storage' => '1',
            'type' => '2',
            'extension' => 'jpg',
            'mime_type' => 'image/jpeg',
            'name' => 'image_718be403bf38b616.jpg',
            'identifier' => '/thuecat/image_718be403bf38b616.jpg',
            'identifier_hash' => '2d4e025b5d4193318e4fdeec97d225767ec0a7ab',
            'folder_hash' => '7cd26a2efdc70daaac29904c75bc135bb21e3506',
        ],
        3 => [
            'uid' => '5',
            'storage' => '1',
            'type' => '2',
            'extension' => 'jpg',
            'mime_type' => 'image/jpeg',
            'name' => 'image_1bd2daee00b7ee9c.jpg',
            'identifier' => '/thuecat/image_1bd2daee00b7ee9c.jpg',
            'identifier_hash' => '8debee886f722bcabf1254239b7351195507dd62',
            'folder_hash' => '7cd26a2efdc70daaac29904c75bc135bb21e3506',
        ],
        4 => [
            'uid' => '6',
            'storage' => '1',
            'type' => '2',
            'extension' => 'jpg',
            'mime_type' => 'image/jpeg',
            'name' => 'image_deadbeefdeadbeef.jpg',
            'identifier' => '/thuecat/image_deadbeefdeadbeef.jpg',
            'identifier_hash' => '0c8f5a1d2b3e4f60718293a4b5c6d7e8f9a0b1c2',
            'folder_hash' => '7cd26a2efdc70daaac29904c75bc135bb21e3506',
        ],
    ],
    'sys_file_reference' => [
        0 => [
            'uid' => '1',
            'pid' => '10',
            'uid_local' => '2',
            'uid_foreign' => '1',
            'tablenames' => 'tx_thuecat_tourist_attraction',
            'fieldname' => 'media_files',
            'sorting_foreign' => '1',
            'title' => 'Bild mit externem Autor',
        ],
        1 => [
            'uid' => '2',
            'pid' => '10',
            'uid_local' => '3',
            'uid_foreign' => '1',
            'tablenames' => 'tx_thuecat_tourist_attraction',
            'fieldname' => 'media_files',
            'sorting_foreign' => '2',
            'title' => 'Bild mit author',
        ],
        2 => [
            'uid' => '3',
            'pid' => '10',
            'uid_local' => '4',
            'uid_foreign' => '1',
            'tablenames' => 'tx_thuecat_tourist_attraction',
            'fieldname' => 'media_files',
            'sorting_foreign' => '3',
            'title' => 'Bild mit license author',
        ],
        3 => [
            'uid' => '4',
            'pid' => '10',
            'uid_local' => '5',
            'uid_foreign' => '1',
            'tablenames' => 'tx_thuecat_tourist_attraction',
            'fieldname' => 'media_files',
            'sorting_foreign' => '4',
            'title' => 'Bild mit author und license author',
        ],
        4 => [
            'uid' => '5',
            'pid' => '10',
            'uid_local' => '2',
            'uid_foreign' => '2',
            'tablenames' => 'tx_thuecat_tourist_attraction',
            'fieldname' => 'media_files',
            'sys_language_uid' => '1',
            'l10n_parent' => '1',
            'sorting_foreign' => '1',
        ],
        5 => [
            'uid' => '6',
            'pid' => '10',
            'uid_local' => '3',
            'uid_foreign' => '2',
            'tablenames' => 'tx_thuecat_tourist_attraction',
            'fieldname' => 'media_files',
            'sys_language_uid' => '1',
            'l10n_parent' => '2',
            'sorting_foreign' => '2',
        ],
        6 => [
            'uid' => '7',
            'pid' => '10',
            'uid_local' => '4',
            'uid_foreign' => '2',
            'tablenames' => 'tx_thuecat_tourist_attraction',
            'fieldname' => 'media_files',
            'sys_language_uid' => '1',
            'l10n_parent' => '3',
            'sorting_foreign' => '3',
        ],
        7 => [
            'uid' => '8',
            'pid' => '10',
            'uid_local' => '5',
            'uid_foreign' => '2',
            'tablenames' => 'tx_thuecat_tourist_attraction',
            'fieldname' => 'media_files',
            'sys_language_uid' => '1',
            'l10n_parent' => '4',
            'sorting_foreign' => '4',
        ],
        8 => [
            'uid' => '9',
            'pid' => '10',
            'uid_local' => '6',
            'uid_foreign' => '1',
            'tablenames' => 'tx_thuecat_tourist_attraction',
            'fieldname' => 'media_files',
            'sorting_foreign' => '5',
        ],
        9 => [
            'uid' => '10',
            'pid' => '10',
            'uid_local' => '6',
            'uid_foreign' => '2',
            'tablenames' => 'tx_thuecat_tourist_attraction',
            'fieldname' => 'media_files',
            'sys_language_uid' => '1',
            'l10n_parent' => '9',
            'sorting_foreign' => '5',
        ],
    ],
];
