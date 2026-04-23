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
];
