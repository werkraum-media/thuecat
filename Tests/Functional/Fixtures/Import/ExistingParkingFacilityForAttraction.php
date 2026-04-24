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
            'title' => 'Preloaded town jcyt',
        ],
        1 => [
            'uid' => '6',
            'pid' => '10',
            'tstamp' => '1613400587',
            'crdate' => '1613400558',
            'disable' => '0',
            'remote_id' => 'https://thuecat.org/resources/052821473718-oxfq',
            'title' => 'Preloaded town oxfq',
        ],
    ],
    'tx_thuecat_organisation' => [
        0 => [
            'uid' => '7',
            'pid' => '10',
            'tstamp' => '1613400587',
            'crdate' => '1613400558',
            'disable' => '0',
            'remote_id' => 'https://thuecat.org/resources/018132452787-ngbe',
            'title' => 'Erfurt Tourismus und Marketing GmbH',
        ],
    ],
    'tx_thuecat_parking_facility' => [
        0 => [
            'uid' => '9',
            'pid' => '10',
            'tstamp' => '1613400587',
            'crdate' => '1613400558',
            'disable' => '0',
            'remote_id' => 'https://thuecat.org/resources/396420044896-drzt',
            'title' => 'Preloaded parking facility drzt',
        ],
        1 => [
            'uid' => '11',
            'pid' => '10',
            'tstamp' => '1613400587',
            'crdate' => '1613400558',
            'disable' => '0',
            'remote_id' => 'https://thuecat.org/resources/440055527204-ocar',
            'title' => 'Preloaded parking facility ocar',
        ],
    ],
];
