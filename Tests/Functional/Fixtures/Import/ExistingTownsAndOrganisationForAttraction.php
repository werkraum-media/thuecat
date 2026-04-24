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
        2 => [
            'uid' => '12',
            'pid' => '10',
            'tstamp' => '1613400587',
            'crdate' => '1613400558',
            'disable' => '0',
            'remote_id' => 'https://thuecat.org/resources/508431710173-wwne',
            'title' => 'Preloaded town wwne (containedInPlace target of drzt)',
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
        1 => [
            'uid' => '8',
            'pid' => '10',
            'tstamp' => '1613400587',
            'crdate' => '1613400558',
            'disable' => '0',
            'remote_id' => 'https://thuecat.org/resources/570107928040-rfze',
            'title' => 'Managing organisation rfze (managedBy target of drzt)',
        ],
    ],
];
