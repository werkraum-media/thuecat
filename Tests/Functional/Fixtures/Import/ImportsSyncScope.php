<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

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
            'title' => 'Sync Scope ID',
            'type' => 'syncScope',
            'storage_pid' => '10',
            'sync_scope_id' => 'dd4615dc-58a6-4648-a7ce-4950293a06db',
            'fetch_last_x_days' => '0',
        ],
    ],
    'tx_thuecat_town' => [
        0 => [
            'uid' => '1',
            'pid' => '10',
            'tstamp' => '1613401129',
            'crdate' => '1613401129',
            'disable' => '0',
            'remote_id' => 'https://thuecat.org/resources/043064193523-jcyt',
            'managed_by' => '1',
            'tourist_information' => '0',
            'title' => 'Erfurt',
        ],
    ],
];
