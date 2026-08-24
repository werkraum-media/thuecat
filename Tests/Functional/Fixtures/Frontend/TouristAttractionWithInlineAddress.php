<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

return [
    'pages' => [
        0 => [
            'uid' => '10',
            'pid' => '1',
            'title' => 'Attraction with inline address',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'slug' => '/inline-address/',
            'sorting' => '256',
            'deleted' => '0',
        ],
    ],
    'tt_content' => [
        0 => [
            'uid' => '10',
            'pid' => '10',
            'hidden' => '0',
            'sorting' => '1',
            'CType' => 'thuecat_tourist_attraction',
            'header' => 'Show attraction with inline address',
            'deleted' => '0',
            'starttime' => '0',
            'endtime' => '0',
            'colPos' => '0',
            'sys_language_uid' => '0',
            'records' => '10',
        ],
    ],
    'tx_thuecat_tourist_attraction' => [
        0 => [
            'uid' => '10',
            'pid' => '3',
            'sys_language_uid' => '0',
            'title' => 'Attraktion mit Adressdatensatz',
            'description' => 'Adresse liegt in eigenen Zeilen vor.',
            'address_inline' => '1',
        ],
        1 => [
            'uid' => '11',
            'pid' => '3',
            'sys_language_uid' => '1',
            'l18n_parent' => '10',
            'l10n_source' => '10',
            'title' => 'Attraction with an address record',
            'description' => 'The address lives in rows of its own.',
            'address_inline' => '1',
        ],
    ],
    // Postal code and street differ per language; the de row has no fax.
    'tx_thuecat_address' => [
        0 => [
            'uid' => '10',
            'pid' => '3',
            'sys_language_uid' => '0',
            'parentid' => '10',
            'parenttable' => 'tx_thuecat_tourist_attraction',
            'street' => 'Beispielweg 5',
            'zip' => '99425',
            'city' => 'Beispielstadt',
            'email' => 'inline@example.com',
            'phone' => '+49 3643 545400',
            'fax' => '',
            'latitude' => '50.974722',
            'longitude' => '11.331389',
        ],
        1 => [
            'uid' => '11',
            'pid' => '3',
            'sys_language_uid' => '1',
            'l18n_parent' => '10',
            'l18n_source' => '10',
            'parentid' => '11',
            'parenttable' => 'tx_thuecat_tourist_attraction',
            'street' => 'Example Lane 5',
            'zip' => '99423',
            'city' => 'Beispielstadt',
            'email' => 'inline@example.com',
            'phone' => '+49 3643 545400',
            'fax' => '+49 3643 545401',
            'latitude' => '50.974722',
            'longitude' => '11.331389',
        ],
    ],
];
