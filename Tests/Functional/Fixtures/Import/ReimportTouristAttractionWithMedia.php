<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

// State after a first import: scaffolding plus the attraction rows, sys_file
// rows and the eight sys_file_reference rows the importer produced. Re-running
// the import over this must keep the reference set flat.
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
            'media_files' => '4',
        ],
        1 => [
            'uid' => '2',
            'pid' => '10',
            'sys_language_uid' => '1',
            'l18n_parent' => '1',
            'remote_id' => 'https://thuecat.org/resources/attraction-with-media',
            'title' => 'Attraction with media',
            'media_files' => '4',
        ],
    ],
    'sys_file' => [
        0 => [
            'uid' => '2',
            'storage' => '1',
            'type' => '2',
            'extension' => 'jpg',
            'mime_type' => 'image/jpeg',
            'name' => 'image-with-foreign-author_Bild-mit-externem-Autor.jpg',
            'identifier' => '/thuecat/image-with-foreign-author_Bild-mit-externem-Autor.jpg',
            'identifier_hash' => '5a1a2c5e9026ea90e3f9b3ea744f0ee620e6b4fc',
            'folder_hash' => '7cd26a2efdc70daaac29904c75bc135bb21e3506',
        ],
        1 => [
            'uid' => '3',
            'storage' => '1',
            'type' => '2',
            'extension' => 'jpg',
            'mime_type' => 'image/jpeg',
            'name' => 'image-with-author-string_Bild-mit-author.jpg',
            'identifier' => '/thuecat/image-with-author-string_Bild-mit-author.jpg',
            'identifier_hash' => '06b87f62d1ea4e6d21e1b45bb46f7d01accede48',
            'folder_hash' => '7cd26a2efdc70daaac29904c75bc135bb21e3506',
        ],
        2 => [
            'uid' => '4',
            'storage' => '1',
            'type' => '2',
            'extension' => 'jpg',
            'mime_type' => 'image/jpeg',
            'name' => 'image-with-license-author_Bild-mit-license-author.jpg',
            'identifier' => '/thuecat/image-with-license-author_Bild-mit-license-author.jpg',
            'identifier_hash' => '092a21f088e765bbaa7c2de6ea0a8983cd1d5301',
            'folder_hash' => '7cd26a2efdc70daaac29904c75bc135bb21e3506',
        ],
        3 => [
            'uid' => '5',
            'storage' => '1',
            'type' => '2',
            'extension' => 'jpg',
            'mime_type' => 'image/jpeg',
            'name' => 'image-with-author-and-license-author_Bild-mit-author-und-license-author.jpg',
            'identifier' => '/thuecat/image-with-author-and-license-author_Bild-mit-author-und-license-author.jpg',
            'identifier_hash' => '62e0070804ae9b489ad55d816c145bf37796499e',
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
    ],
];
