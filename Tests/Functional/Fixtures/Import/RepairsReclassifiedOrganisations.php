<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

$roots = [
    1 => 'repair-hotel',
    2 => 'place-store',
    3 => 'place-square',
    4 => 'claiming-dmo',
    5 => 'repair-holiday-flat',
    6 => 'repair-fountain-on-square',
];

$configurations = [];
$urls = [];
foreach ($roots as $uid => $root) {
    $configurations[] = [
        'uid' => (string)$uid,
        'pid' => '0',
        'disable' => '0',
        'title' => $root,
        'type' => 'static',
        'storage_pid' => '10',
    ];
    $urls[] = [
        'uid' => (string)($uid * 100 + 1),
        'pid' => '0',
        'configuration' => (string)$uid,
        'sorting' => '1',
        'url' => 'https://thuecat.org/resources/' . $root,
    ];
}

return [
    'pages' => [
        [
            'uid' => '1',
            'pid' => '0',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Rootpage',
            'is_siteroot' => '1',
        ],
        [
            'uid' => '10',
            'pid' => '1',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'title' => 'Storage folder',
        ],
        [
            'uid' => '4000',
            'pid' => '0',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Other site',
            'is_siteroot' => '1',
        ],
        [
            'uid' => '4010',
            'pid' => '4000',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'title' => 'Other site storage folder',
        ],
    ],
    'tx_thuecat_import_configuration' => $configurations,
    'tx_thuecat_import_configuration_url' => $urls,
];
