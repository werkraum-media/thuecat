<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Resource\FileType;

return [
    'tx_thuecat_tourist_attraction' => [
        0 => [
            'uid' => '1',
            'pid' => '10',
            'remote_id' => 'https://thuecat.org/resources/attraction-with-media',
            'title' => 'Attraktion mit Bildern',
            // 'mainImage' => 0,
            'images' => 4,
        ],
        1 => [
            'uid' => '2',
            'pid' => '10',
            'remote_id' => 'https://thuecat.org/resources/attraction-with-media',
            'title' => 'Attraction with media',
            // 'media' => '[{"mainImage":false,"type":"image","title":"Bild mit externem Autor","description":"","url":"https:\\/\\/cms.thuecat.org\\/o\\/adaptive-media\\/image\\/5099196\\/Preview-1280x0\\/image","author":"GivenName FamilyName","copyrightYear":0,"license":{"type":"","author":""}},{"mainImage":false,"type":"image","title":"Bild mit author","description":"","url":"https:\\/\\/cms.thuecat.org\\/o\\/adaptive-media\\/image\\/5099196\\/Preview-1280x0\\/image","author":"Full Name","copyrightYear":0,"license":{"type":"https:\\/\\/creativecommons.org\\/licenses\\/by\\/4.0\\/","author":""}},{"mainImage":false,"type":"image","title":"Bild mit license author","description":"","url":"https:\\/\\/cms.thuecat.org\\/o\\/adaptive-media\\/image\\/5099196\\/Preview-1280x0\\/image","author":"","copyrightYear":0,"license":{"type":"https:\\/\\/creativecommons.org\\/licenses\\/by\\/4.0\\/","author":"Autor aus Lizenz"}},{"mainImage":false,"type":"image","title":"Bild mit author und license author","description":"","url":"https:\\/\\/cms.thuecat.org\\/o\\/adaptive-media\\/image\\/5099196\\/Preview-1280x0\\/image","author":"Full Name","copyrightYear":0,"license":{"type":"https:\\/\\/creativecommons.org\\/licenses\\/by\\/4.0\\/","author":"Autor aus Lizenz"}}]',
        ],
    ],
    'sys_file' => [
        0 => [
            'uid' => 1,
            'pid' => 0,
            'type' => FileType::IMAGE->value,
            'storage' => 1,
            'identifier' => '/editors/thuecat/cc3b031e765a7638b04fc5fd9aeff70abdde342f639e389ad83a151ca4dbaf19.jpeg',
            'extension' => 'jpeg',
            'mime_type' => 'image/jpeg',
            'name' => 'cc3b031e765a7638b04fc5fd9aeff70abdde342f639e389ad83a151ca4dbaf19.jpeg',
            'sha1' => '657a98aa35dad21b6145437f182d98174851bb04',
            'size' => 167998,
            'missing' => 0,
            'identifier_hash' => '6b2cdc95f4cb93ba32ddd26431d6d8a46f2fd9dc',
            'folder_hash' => '8baca3464bf1d9065397866289dd1ee489dbd496',
        ],
        // TODO: Add other files
    ],
    'sys_file_metadata' => [
        0 => [
            'uid' => 1,
            'pid' => 0,
            'file' => 1,
            'title' => 'Bild mit externem Autor',
            'width' => 1280,
            'height' => 960,
            'description' => null,
            'alternative' => null,
            'categories' => 0,
        ],
        // TODO: Add other files
    ],
    'sys_file_reference' => [
        0 => [
            'uid' => 1,
            'pid' => 10,
            'deleted' => 0,
            'hidden' => 0,
            'uid_local' => 1,
            'uid_foreign' => 1,
            'tablenames' => 'tx_thuecat_tourist_attraction',
            'fieldname' => 'images',
            'sorting_foreign' => 1,
            'title' => '',
            'description' => '',
            'alternative' => '',
            'link' => '',
            'showinpreview' => 0,
            'crop' => '',
            'autoplay' => 0,
        ],
        // TODO: Add other files
    ],
];
