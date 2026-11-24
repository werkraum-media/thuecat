<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

$roots = [
    1 => 'place-store',
    2 => 'place-manufacturer',
    3 => 'place-attraction',
    4 => 'attraction-on-a-square',
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
    ],
    'tx_thuecat_import_configuration' => $configurations,
    'tx_thuecat_import_configuration_url' => $urls,
];
