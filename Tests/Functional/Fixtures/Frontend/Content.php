<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

return [
    'pages' => [
        0 => [
            'uid' => '1',
            'pid' => '0',
            'title' => 'Root',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'slug' => '/',
            'sorting' => '128',
            'deleted' => '0',
        ],
        1 => [
            'uid' => '2',
            'pid' => '1',
            'title' => 'Tourist Attraction',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'slug' => '/example-attraction/',
            'sorting' => '128',
            'deleted' => '0',
        ],
        2 => [
            'uid' => '3',
            'pid' => '1',
            'title' => 'Storage',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'sorting' => '128',
            'deleted' => '0',
        ],
        3 => [
            'uid' => '4',
            'pid' => '1',
            'title' => 'Tourist Attraction false',
            'slug' => '/example-attraction-false/',
            'sorting' => '128',
            'deleted' => '0',
        ],
        4 => [
            'uid' => '5',
            'pid' => '1',
            'title' => 'Tourist Attraction true',
            'slug' => '/example-attraction-true/',
            'sorting' => '128',
            'deleted' => '0',
        ],
    ],
    'tt_content' => [
        0 => [
            'uid' => '2',
            'pid' => '2',
            'hidden' => '0',
            'sorting' => '1',
            'CType' => 'thuecat_tourist_attraction',
            'header' => 'Show Example Tourist Attraction',
            'deleted' => '0',
            'starttime' => '0',
            'endtime' => '0',
            'colPos' => '0',
            'sys_language_uid' => '0',
            'records' => '1',
        ],
        1 => [
            'uid' => '3',
            'pid' => '4',
            'hidden' => '0',
            'sorting' => '1',
            'CType' => 'thuecat_tourist_attraction',
            'header' => 'Show Example Tourist Attraction with false',
            'deleted' => '0',
            'starttime' => '0',
            'endtime' => '0',
            'colPos' => '0',
            'sys_language_uid' => '0',
            'records' => '2',
        ],
        2 => [
            'uid' => '4',
            'pid' => '5',
            'hidden' => '0',
            'sorting' => '1',
            'CType' => 'thuecat_tourist_attraction',
            'header' => 'Show Example Tourist Attraction with true',
            'deleted' => '0',
            'starttime' => '0',
            'endtime' => '0',
            'colPos' => '0',
            'sys_language_uid' => '0',
            'records' => '3',
        ],
    ],
];
