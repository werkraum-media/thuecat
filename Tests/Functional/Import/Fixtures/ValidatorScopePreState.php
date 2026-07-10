<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

// Site 'example' has rootPageId 1. Pages 10/20 sit inside that site; category
// 100 lives at pid 20 (in-site). Page 90 is a second, detached site root with
// category 900 at pid 91 (out of the example site).
return [
    'pages' => [
        [
            'uid' => '1',
            'pid' => '0',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Example root',
            'is_siteroot' => '1',
        ],
        [
            'uid' => '10',
            'pid' => '1',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'title' => 'Event storage',
        ],
        [
            'uid' => '20',
            'pid' => '1',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'title' => 'Category storage',
        ],
        [
            'uid' => '90',
            'pid' => '0',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Other site root',
            'is_siteroot' => '1',
        ],
        [
            'uid' => '91',
            'pid' => '90',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'title' => 'Other-site category storage',
        ],
        [
            'uid' => '500',
            'pid' => '0',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'title' => 'Site-less folder',
        ],
    ],
    'sys_category' => [
        [
            'uid' => '100',
            'pid' => '20',
            'parent' => '0',
            'title' => 'In-site parent',
        ],
        [
            'uid' => '900',
            'pid' => '91',
            'parent' => '0',
            'title' => 'Other-site parent',
        ],
    ],
];