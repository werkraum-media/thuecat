<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

// A genuine sub-region (centered 50%) so core actually processes the file.
$crop = json_encode([
    'default' => [
        'cropArea' => ['x' => 0.25, 'y' => 0.25, 'width' => 0.5, 'height' => 0.5],
        'selectedRatio' => 'NaN',
        'focusArea' => null,
    ],
]);

$legacyMain = json_encode([
    [
        'mainImage' => true,
        'type' => 'image',
        'url' => 'https://cms.thuecat.org/legacy-main/image',
        'description' => 'Legacy main description',
        'author' => 'Legacy Main Author',
    ],
    [
        'mainImage' => false,
        'type' => 'image',
        'url' => 'https://cms.thuecat.org/legacy-extra/image',
        'description' => 'Legacy extra description',
        'author' => 'Legacy Extra Author',
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
            'title' => 'Storage for Attractions',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'sorting' => '512',
            'deleted' => '0',
        ],
    ],
    'tt_content' => [
        [
            'uid' => '10',
            'pid' => '10',
            'CType' => 'werkraummedia_thuecatattractionshow',
            'header' => 'Show Plugin',
            'colPos' => '0',
            'sorting' => '256',
            'sys_language_uid' => '0',
        ],
    ],
    'tx_thuecat_tourist_attraction' => [
        [
            'uid' => '21',
            'pid' => '11',
            'title' => 'Stadtmuseum Erfurt',
            'description' => 'Beschreibung des Stadtmuseums',
            'main_image' => '1',
            'media_files' => '1',
            'editorial_images' => '1',
        ],
        [
            'uid' => '22',
            'pid' => '11',
            'title' => 'Attraktion mit Altdaten',
            'description' => 'Beschreibung mit Altdaten',
            'media' => $legacyMain,
        ],
        [
            'uid' => '23',
            'pid' => '11',
            'title' => 'Attraktion ohne Medien',
            'description' => 'Beschreibung ohne Medien',
            'media' => '',
        ],
    ],
    // storage uid 1 is created at runtime via createLocalStorage() in the test setUp.
    // Both files point at the same image on disk; distinct copyright proves each
    // reference renders its own file's metadata.
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
            'title' => 'Editorial image',
            'description' => 'Editorial image description',
            'copyright' => 'Foto: Editorial Author',
            'width' => '20',
            'height' => '20',
        ],
    ],
    'sys_file_reference' => [
        [
            'uid' => '1',
            'uid_local' => '1',
            'uid_foreign' => '21',
            'tablenames' => 'tx_thuecat_tourist_attraction',
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
            'tablenames' => 'tx_thuecat_tourist_attraction',
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
            'tablenames' => 'tx_thuecat_tourist_attraction',
            'fieldname' => 'editorial_images',
            'sorting_foreign' => '1',
            'title' => 'Editorial image',
            'description' => 'Editorial image description',
            'crop' => $crop,
        ],
    ],
];
