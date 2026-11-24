<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

// The attraction already lists event 900, which this run never produces, and
// event 901, which it matches again. Both are seeded as stored rows so the
// merge is exercised against the database rather than against the payload.
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
    'tx_thuecat_tourist_attraction' => [
        0 => [
            'uid' => '1',
            'pid' => '10',
            'sys_language_uid' => '0',
            'remote_id' => 'https://thuecat.org/resources/900000000020-bauh',
            'title' => 'Bauhaus-Museum Weimar',
            'address_inline' => '1',
            'hosts_events' => '2',
        ],
    ],
    'tx_thuecat_tourist_attraction_hosts_events_mm' => [
        ['uid_local' => '1', 'uid_foreign' => '900', 'sorting' => '1'],
        ['uid_local' => '1', 'uid_foreign' => '901', 'sorting' => '2'],
    ],
    'tx_thuecat_address' => [
        0 => [
            'uid' => '1',
            'pid' => '10',
            'sys_language_uid' => '0',
            'parentid' => '1',
            'parenttable' => 'tx_thuecat_tourist_attraction',
            'remote_id' => 'https://thuecat.org/resources/900000000020-bauh::addr::0',
            'street' => 'Stéphane-Hessel-Platz 1',
            'zip' => '99423',
            'city' => 'Weimar',
        ],
    ],
    'tx_events_domain_model_event' => [
        0 => [
            'uid' => '900',
            'pid' => '10',
            'sys_language_uid' => '0',
            'remote_id' => 'https://int.thuecat.org/resources/e_unrelated-hubev',
            'title' => 'Veranstaltung aus einem früheren Lauf',
        ],
        1 => [
            'uid' => '901',
            'pid' => '10',
            'sys_language_uid' => '0',
            'remote_id' => 'https://int.thuecat.org/resources/e_bauhaus-hubev',
            'title' => 'Führung durch die Sammlung',
        ],
    ],
    'tx_thuecat_import_configuration' => [
        0 => [
            'uid' => '1',
            'pid' => '0',
            'tstamp' => 1613400587,
            'crdate' => 1613400558,
            'disable' => '0',
            'title' => 'Event place matching import',
            'type' => 'static',
            'storage_pid' => '10',
        ],
    ],
    'tx_thuecat_import_configuration_url' => [
        [
            'uid' => '101',
            'pid' => '0',
            'configuration' => '1',
            'sorting' => '1',
            'title' => '',
            'url' => 'https://cdb.int.thuecat.org/api/resources/e_bauhaus-hubev',
        ],
    ],
];
