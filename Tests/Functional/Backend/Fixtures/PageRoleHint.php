<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional\Backend\Fixtures;

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

// Stub CTypes, mapped as a content element package would.
$pageRoles = '
tx_thuecat.pageRoles {
    werkraummedia_thuecatattractionlist = thuecat_touristattractionlist
    werkraummedia_thuecatattractionlistfiltered = thuecat_touristattractionlist
    werkraummedia_thuecatattractionshow = thuecat_touristattractionshow
    werkraummedia_thuecattrailshow = thuecat_trailshow
}
';

return [
    'pages' => [
        [
            'uid' => '1',
            'pid' => '0',
            'title' => 'Root',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'slug' => '/',
            'is_siteroot' => '1',
            'TSconfig' => $pageRoles,
        ],
        [
            'uid' => '10',
            'pid' => '1',
            'title' => 'Show page without role',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'slug' => '/without-role',
        ],
        [
            'uid' => '20',
            'pid' => '1',
            'title' => 'Show page with role',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'slug' => '/with-role',
            'tx_thuecat_page_role' => 'thuecat_touristattractionshow',
        ],
        [
            'uid' => '30',
            'pid' => '1',
            'title' => 'Show page with wrong role',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'slug' => '/wrong-role',
            'tx_thuecat_page_role' => 'thuecat_trailshow',
        ],
        [
            'uid' => '40',
            'pid' => '1',
            'title' => 'Text page',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'slug' => '/text',
        ],
        [
            'uid' => '50',
            'pid' => '1',
            'title' => 'Selected list page',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'slug' => '/selected',
        ],
    ],
    'tt_content' => [
        [
            'uid' => '1',
            'pid' => '10',
            'CType' => 'werkraummedia_thuecatattractionshow',
            'header' => 'Show without role',
        ],
        [
            'uid' => '2',
            'pid' => '20',
            'CType' => 'werkraummedia_thuecatattractionshow',
            'header' => 'Show with role',
        ],
        [
            'uid' => '3',
            'pid' => '30',
            'CType' => 'werkraummedia_thuecatattractionshow',
            'header' => 'Show with wrong role',
        ],
        [
            'uid' => '4',
            'pid' => '40',
            'CType' => 'text',
            'header' => 'Text',
        ],
        [
            'uid' => '5',
            'pid' => '50',
            'CType' => 'werkraummedia_thuecatattractionlistselected',
            'header' => 'Selected list',
        ],
    ],
];
