<?php

declare(strict_types=1);

return  [
    'tx_thuecat_tourist_attraction' => [
        0 => [
            'uid' => '1',
            'pid' => '3',
            'title' => 'Attraktion mit redaktionellen Bildern',
            'editorial_images' => '2',
        ],
    ],
    'sys_file_reference' => [
        0 => [
            'uid' => '1',
            'uid_local' => '1',
            'uid_foreign' => '1',
            'tablenames' => 'tx_thuecat_tourist_attraction',
            'fieldname' => 'editorial_images',
            'sorting_foreign' => '1',
        ],
        1 => [
            'uid' => '2',
            'uid_local' => '2',
            'uid_foreign' => '1',
            'tablenames' => 'tx_thuecat_tourist_attraction',
            'fieldname' => 'editorial_images',
            'sorting_foreign' => '2',
        ],
    ],
    'sys_file' => [
        0 => [
            'uid' => '1',
            'type' => '2',
            'storage' => '1',
            'identifier' => '/tourismus/images/inhalte/sehenswertes/parks_gaerten/hirschgarten/2998_Spielplaetze_Hirschgarten.jpg',
            'extension' => 'jpg',
            'mime_type' => 'image/jpeg',
            'name' => '2998_Spielplaetze_Hirschgarten.jpg',
            'sha1' => '61079cbeb5d13c21d20dbbcc2e28e9c8fa04b3b4',
            'size' => '7329219',
            'identifier_hash' => '69066cc9c3b5ff135a7daa36059b18c75b3d9a23',
            'folder_hash' => '4dd66a1c0a2a0ab89a22bfe734df75d9750d28f2',
        ],
        1 => [
            'uid' => '2',
            'type' => '2',
            'storage' => '1',
            'identifier' => '/tourismus/images/inhalte/sehenswertes/sehenswuerdigkeiten/Petersberg/20_Erfurt-Schriftzug_Petersberg_2021__c_Stadtverwaltung_Erfurt_CC-BY-NC-SA.JPG',
            'extension' => 'JPG',
            'mime_type' => 'image/jpeg',
            'name' => '20_Erfurt-Schriftzug_Petersberg_2021__c_Stadtverwaltung_Erfurt_CC-BY-NC-SA.JPG',
            'sha1' => 'f4c45d3c738d29162759ecd7d2dbc9af2a8f515f',
            'size' => '2807135',
            'identifier_hash' => '384f006a1452e901badb0db799fa7ff364e88a5e',
            'folder_hash' => '01086eae3464ef516edc0756ba3e12e35e09c33d',
        ],
    ],
];
