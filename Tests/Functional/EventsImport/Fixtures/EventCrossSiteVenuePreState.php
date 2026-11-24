<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

// The same title and postal code in both sites. The import runs into site
// 4000, so only uid 1 may be matched; uid 2 proves the scope is enforced
// rather than the second record merely being absent.
return [
    'pages' => [
        [
            'uid' => '4000',
            'pid' => '0',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'First site root',
            'is_siteroot' => '1',
        ],
        [
            'uid' => '4010',
            'pid' => '4000',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'title' => 'First site record storage',
        ],
        [
            'uid' => '5000',
            'pid' => '0',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Second site root',
            'is_siteroot' => '1',
        ],
        [
            'uid' => '5010',
            'pid' => '5000',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'title' => 'Second site record storage',
        ],
    ],
    'tx_thuecat_tourist_attraction' => [
        [
            'uid' => '1',
            'pid' => '4010',
            'sys_language_uid' => '0',
            'remote_id' => 'https://thuecat.org/resources/900000000020-bauh',
            'title' => 'Bauhaus-Museum Weimar',
            'address_inline' => '1',
        ],
        [
            'uid' => '2',
            'pid' => '5010',
            'sys_language_uid' => '0',
            'remote_id' => 'https://thuecat.org/resources/900000000030-bauh2',
            'title' => 'Bauhaus-Museum Weimar',
            'address_inline' => '1',
        ],
    ],
    'tx_thuecat_address' => [
        [
            'uid' => '1',
            'pid' => '4010',
            'sys_language_uid' => '0',
            'parentid' => '1',
            'parenttable' => 'tx_thuecat_tourist_attraction',
            'remote_id' => 'https://thuecat.org/resources/900000000020-bauh::addr::0',
            'street' => 'Stéphane-Hessel-Platz 1',
            'zip' => '99423',
            'city' => 'Weimar',
        ],
        [
            'uid' => '2',
            'pid' => '5010',
            'sys_language_uid' => '0',
            'parentid' => '2',
            'parenttable' => 'tx_thuecat_tourist_attraction',
            'remote_id' => 'https://thuecat.org/resources/900000000030-bauh2::addr::0',
            'street' => 'Stéphane-Hessel-Platz 1',
            'zip' => '99423',
            'city' => 'Weimar',
        ],
    ],
    'tx_thuecat_import_configuration' => [
        [
            'uid' => '1',
            'pid' => '0',
            'disable' => '0',
            'title' => 'First site event import',
            'type' => 'static',
            'storage_pid' => '4010',
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
