<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

// Two sites, each storing its own towns and one attraction. The towns share a
// title across sites so that a wizard search matching by title cannot tell them
// apart by anything but scope.
return [
    'pages' => [
        [
            'uid' => '4000',
            'pid' => '0',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'First site root',
            'is_siteroot' => '1',
        ],
        [
            'uid' => '4010',
            'pid' => '4000',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'title' => 'First site record storage',
        ],
        [
            'uid' => '5000',
            'pid' => '0',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Second site root',
            'is_siteroot' => '1',
        ],
        [
            'uid' => '5010',
            'pid' => '5000',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'title' => 'Second site record storage',
        ],
    ],
    'tx_thuecat_town' => [
        [
            'uid' => '4001',
            'pid' => '4010',
            'sys_language_uid' => '0',
            'l10n_parent' => '0',
            'title' => 'Shared Town Name',
            'remote_id' => 'https://thuecat.org/resources/first-town',
        ],
        [
            // Translation of the town above: same pid, same title, so only the
            // language condition can keep it out of the offer.
            'uid' => '4002',
            'pid' => '4010',
            'sys_language_uid' => '1',
            'l10n_parent' => '4001',
            'title' => 'Shared Town Name',
            'remote_id' => 'https://thuecat.org/resources/first-town',
        ],
        [
            'uid' => '5001',
            'pid' => '5010',
            'sys_language_uid' => '0',
            'l10n_parent' => '0',
            'title' => 'Shared Town Name',
            'remote_id' => 'https://thuecat.org/resources/second-town',
        ],
    ],
    'tx_thuecat_tourist_attraction' => [
        [
            'uid' => '4900',
            'pid' => '4010',
            'sys_language_uid' => '0',
            'l18n_parent' => '0',
            'title' => 'First site attraction',
            'remote_id' => 'https://thuecat.org/resources/first-attraction',
        ],
        [
            'uid' => '5900',
            'pid' => '5010',
            'sys_language_uid' => '0',
            'l18n_parent' => '0',
            'title' => 'Second site attraction',
            'remote_id' => 'https://thuecat.org/resources/second-attraction',
        ],
    ],
];
