<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

// Own site root (200) for the writeSiteSettings() helper, so the test never
// touches the symlinked 'example' fixture. 210 is the storage folder the
// settings are read against.
return [
    'pages' => [
        [
            'uid' => '200',
            'pid' => '0',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Settings helper root',
            'is_siteroot' => '1',
        ],
        [
            'uid' => '210',
            'pid' => '200',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'title' => 'Storage',
        ],
    ],
];
