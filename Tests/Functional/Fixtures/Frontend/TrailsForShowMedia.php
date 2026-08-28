<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

// Deliberately NOT square: the source is square, so only a crop with a
// different aspect ratio makes the processed height prove the crop applied.
$crop = json_encode([
    'default' => [
        'cropArea' => ['x' => 0.25, 'y' => 0.375, 'width' => 0.5, 'height' => 0.25],
        'selectedRatio' => 'NaN',
        'focusArea' => null,
    ],
]);

return [
    'pages' => [
        [
            'uid' => '1',
            'pid' => '0',
            'title' => 'Root',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'slug' => '/',
            'sorting' => '128',
            'deleted' => '0',
        ],
        [
            'uid' => '10',
            'pid' => '1',
            'title' => 'Show Page',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'slug' => '/show/',
            'sorting' => '256',
            'deleted' => '0',
        ],
        [
            'uid' => '11',
            'pid' => '1',
            'title' => 'Storage for Trails',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'sorting' => '512',
            'deleted' => '0',
        ],
    ],
    'tt_content' => [
        [
            'uid' => '10',
            'pid' => '10',
            'CType' => 'werkraummedia_thuecattrailshow',
            'header' => 'Show Plugin',
            'colPos' => '0',
            'sorting' => '256',
            'sys_language_uid' => '0',
        ],
    ],
    'tx_thuecat_trail' => [
        [
            'uid' => '21',
            'pid' => '11',
            'disable' => '0',
            'title' => 'Goethe-Erlebnisweg',
            'description' => 'Beschreibung des Goethe-Erlebniswegs',
            'sys_language_uid' => '0',
            'main_image' => '1',
            'media_files' => '1',
            'logo' => '1',
        ],
        [
            'uid' => '22',
            'pid' => '11',
            'disable' => '0',
            'title' => 'Weg ohne Medien',
            'description' => 'Beschreibung des Wegs ohne Medien',
            'sys_language_uid' => '0',
        ],
    ],
    // One file on disk behind every reference; each reference renders its own
    // file's metadata.
    'sys_file' => [
        [
            'uid' => '1',
            'type' => '2',
            'storage' => '1',
            'identifier' => '/thuecat/image.jpg',
            'identifier_hash' => '59d09859a167e0a68e7444fff47c03430d53123c',
            'folder_hash' => '7cd26a2efdc70daaac29904c75bc135bb21e3506',
            'extension' => 'jpg',
            'mime_type' => 'image/jpeg',
            'name' => 'image.jpg',
            'sha1' => 'bd4a88b2a3fc9b3b9f2c526cd07a001d0c42c980',
            'size' => '294',
        ],
        [
            'uid' => '2',
            'type' => '2',
            'storage' => '1',
            'identifier' => '/thuecat/image.jpg',
            'identifier_hash' => '59d09859a167e0a68e7444fff47c03430d53123c',
            'folder_hash' => '7cd26a2efdc70daaac29904c75bc135bb21e3506',
            'extension' => 'jpg',
            'mime_type' => 'image/jpeg',
            'name' => 'image.jpg',
            'sha1' => 'bd4a88b2a3fc9b3b9f2c526cd07a001d0c42c980',
            'size' => '294',
        ],
        [
            'uid' => '3',
            'type' => '2',
            'storage' => '1',
            'identifier' => '/thuecat/image.jpg',
            'identifier_hash' => '59d09859a167e0a68e7444fff47c03430d53123c',
            'folder_hash' => '7cd26a2efdc70daaac29904c75bc135bb21e3506',
            'extension' => 'jpg',
            'mime_type' => 'image/jpeg',
            'name' => 'image.jpg',
            'sha1' => 'bd4a88b2a3fc9b3b9f2c526cd07a001d0c42c980',
            'size' => '294',
        ],
    ],
    'sys_file_metadata' => [
        [
            'uid' => '1',
            'file' => '1',
            'title' => 'Main image',
            'description' => 'Main image description',
            'copyright' => 'Foto: Main Author',
            'width' => '20',
            'height' => '20',
        ],
        [
            'uid' => '2',
            'file' => '2',
            'title' => 'Gallery image',
            'description' => 'Gallery image description',
            'copyright' => 'Foto: Gallery Author',
            'width' => '20',
            'height' => '20',
        ],
        [
            'uid' => '3',
            'file' => '3',
            'title' => 'Logo',
            'description' => 'Logo description',
            'copyright' => 'Foto: Logo Author',
            'width' => '20',
            'height' => '20',
        ],
    ],
    // Every reference carries the crop: an editor's crop must survive into the
    // processed file, which it only can when the reference is rendered.
    'sys_file_reference' => [
        [
            'uid' => '1',
            'uid_local' => '1',
            'uid_foreign' => '21',
            'tablenames' => 'tx_thuecat_trail',
            'fieldname' => 'main_image',
            'sorting_foreign' => '1',
            'title' => 'Main image',
            'description' => 'Main image description',
            'crop' => $crop,
        ],
        [
            'uid' => '2',
            'uid_local' => '2',
            'uid_foreign' => '21',
            'tablenames' => 'tx_thuecat_trail',
            'fieldname' => 'media_files',
            'sorting_foreign' => '1',
            'title' => 'Gallery image',
            'description' => 'Gallery image description',
            'crop' => $crop,
        ],
        [
            'uid' => '3',
            'uid_local' => '3',
            'uid_foreign' => '21',
            'tablenames' => 'tx_thuecat_trail',
            'fieldname' => 'logo',
            'sorting_foreign' => '1',
            'title' => 'Logo',
            'description' => 'Logo description',
            'crop' => $crop,
        ],
    ],
];
