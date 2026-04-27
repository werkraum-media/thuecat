<?php

declare(strict_types=1);

// uid order reflects how the Importer drives DataHandler: it accumulates
// every URL's parsed payload before running any DataHandler pass, so iter 0
// creates BOTH default-language rows (uids 1, 2), then iter 1 localizes
// both via cmdMap (uids 3, 4). The naive "single attraction fully done
// before the next" ordering does not happen.
return [
    'tx_thuecat_tourist_attraction' => [
        0 => [
            'uid' => '1',
            'pid' => '10',
            'sys_language_uid' => '0',
            'l18n_parent' => '0',
            'l10n_source' => '0',
            'remote_id' => 'https://thuecat.org/resources/attraction-with-single-slogan',
            'title' => 'Attraktion mit single slogan',
            'slogan' => 'InsiderTip',
        ],
        1 => [
            'uid' => '2',
            'pid' => '10',
            'sys_language_uid' => '0',
            'l18n_parent' => '0',
            'l10n_source' => '0',
            'remote_id' => 'https://thuecat.org/resources/attraction-with-slogan-array',
            'title' => 'Attraktion mit slogan array',
            'slogan' => 'Highlight,InsiderTip,Unique',
        ],
        2 => [
            'uid' => '3',
            'pid' => '10',
            'sys_language_uid' => '1',
            'l18n_parent' => '1',
            'l10n_source' => '1',
            'remote_id' => 'https://thuecat.org/resources/attraction-with-single-slogan',
            'title' => 'Attraction with single slogan',
            'slogan' => 'InsiderTip',
        ],
        3 => [
            'uid' => '4',
            'pid' => '10',
            'sys_language_uid' => '1',
            'l18n_parent' => '2',
            'l10n_source' => '2',
            'remote_id' => 'https://thuecat.org/resources/attraction-with-slogan-array',
            'title' => 'Attraction with slogan array',
            'slogan' => 'Highlight,InsiderTip,Unique',
        ],
    ],
];
