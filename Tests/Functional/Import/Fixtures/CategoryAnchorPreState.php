<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

// Two sites: 300 with storage 310, and 900 with storage 910, so anchor
// resolution can be shown to follow the import's own storagePid.
return [
    'pages' => [
        [
            'uid' => '300',
            'pid' => '0',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Anchors root',
            'is_siteroot' => '1',
        ],
        [
            'uid' => '310',
            'pid' => '300',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'title' => 'Record storage',
        ],
        [
            'uid' => '320',
            'pid' => '300',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'title' => 'Category storage',
        ],
        [
            'uid' => '330',
            'pid' => '300',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'title' => 'Keyword storage',
        ],
        [
            'uid' => '900',
            'pid' => '0',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Other anchors root',
            'is_siteroot' => '1',
        ],
        [
            'uid' => '910',
            'pid' => '900',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'title' => 'Other record storage',
        ],
        [
            'uid' => '930',
            'pid' => '900',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'title' => 'Other keyword storage',
        ],
    ],
];
