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
    'tx_thuecat_town' => [
        0 => [
            'uid' => '5',
            'pid' => '10',
            'tstamp' => '1613400587',
            'crdate' => '1613400558',
            'disable' => '0',
            'remote_id' => 'https://thuecat.org/resources/043064193523-jcyt',
            'title' => 'jcyt',
        ],
        1 => [
            'uid' => '6',
            'pid' => '10',
            'tstamp' => '1613400587',
            'crdate' => '1613400558',
            'disable' => '0',
            'remote_id' => 'https://thuecat.org/resources/573211638937-gmqb',
            'title' => 'gmqb',
        ],
        2 => [
            'uid' => '7',
            'pid' => '10',
            'tstamp' => '1613400587',
            'crdate' => '1613400558',
            'disable' => '0',
            'remote_id' => 'https://thuecat.org/resources/497839263245-edbm',
            'title' => 'edbm',
        ],
    ],
    'tx_thuecat_organisation' => [
        0 => [
            'uid' => '9',
            'pid' => '10',
            'tstamp' => '1613400587',
            'crdate' => '1613400558',
            'disable' => '0',
            'remote_id' => 'https://thuecat.org/resources/018132452787-ngbe',
            'title' => 'ngbe',
        ],
    ],
];
