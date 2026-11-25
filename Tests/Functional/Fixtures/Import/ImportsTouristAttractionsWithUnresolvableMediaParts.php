<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

$roots = [
    1 => 'attraction-with-blank-node-author',
    2 => 'attraction-with-bare-blank-node-author',
    3 => 'attraction-with-failing-author',
    4 => 'attraction-with-failing-media-resource',
];

$configurations = [];
$urls = [];
foreach ($roots as $uid => $root) {
    $configurations[] = [
        'uid' => (string)$uid,
        'pid' => '0',
        'tstamp' => '1613400587',
        'crdate' => '1613400558',
        'disable' => '0',
        'title' => $root,
        'type' => 'static',
        'storage_pid' => '10',
        'file_folder' => '1:/thuecat/',
    ];
    $urls[] = [
        'uid' => (string)(100 + $uid),
        'pid' => '0',
        'configuration' => (string)$uid,
        'sorting' => '1',
        'title' => '',
        'url' => 'https://thuecat.org/resources/' . $root,
    ];
}

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
    'tx_thuecat_import_configuration' => $configurations,
    'tx_thuecat_import_configuration_url' => $urls,
];
