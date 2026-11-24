<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

$configurations = [
    1 => ['Venue with one room', ['059632237210-rbpe']],
];

$rows = [];
$urlRows = [];
foreach ($configurations as $uid => $definition) {
    $rows[] = [
        'uid' => (string)$uid,
        'pid' => '0',
        'disable' => '0',
        'title' => $definition[0],
        'type' => 'static',
        'storage_pid' => '10',
    ];
    foreach ($definition[1] as $position => $url) {
        $urlRows[] = [
            'uid' => (string)($uid * 100 + $position + 1),
            'pid' => '0',
            'configuration' => (string)$uid,
            'sorting' => (string)($position + 1),
            'url' => 'https://thuecat.org/resources/' . $url,
        ];
    }
}

// The keywords site anchors keywords (200) apart from categories (100).
return [
    'pages' => [
        ['uid' => '1000', 'pid' => '0', 'doktype' => PageRepository::DOKTYPE_DEFAULT, 'title' => 'Rootpage', 'is_siteroot' => '1'],
        ['uid' => '10', 'pid' => '1000', 'doktype' => PageRepository::DOKTYPE_SYSFOLDER, 'title' => 'Record storage folder'],
        ['uid' => '20', 'pid' => '1000', 'doktype' => PageRepository::DOKTYPE_SYSFOLDER, 'title' => 'Category storage folder'],
        ['uid' => '30', 'pid' => '1000', 'doktype' => PageRepository::DOKTYPE_SYSFOLDER, 'title' => 'Keyword storage folder'],
    ],
    'sys_category' => [
        ['uid' => '100', 'pid' => '20', 'parent' => '0', 'title' => 'Categories'],
        ['uid' => '200', 'pid' => '30', 'parent' => '0', 'title' => 'Keywords'],
    ],
    'tx_thuecat_import_configuration' => $rows,
    'tx_thuecat_import_configuration_url' => $urlRows,
];
