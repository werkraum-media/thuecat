<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

// Order matters: the venue is staged as a reference at depth 1 before its own root turn.
$configuration = static function (string ...$urls): string {
    $elements = '';
    foreach ($urls as $index => $url) {
        $elements .= '
                                    <field index="url' . $index . '">
                                        <value index="url">
                                            <el>
                                                <field index="url">
                                                    <value index="vDEF">https://thuecat.org/resources/' . $url . '</value>
                                                </field>
                                            </el>
                                        </value>
                                        <value index="_TOGGLE">0</value>
                                    </field>';
    }

    return '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
            <T3FlexForms>
                <data>
                    <sheet index="sDEF">
                        <language index="lDEF">
                            <field index="storagePid">
                                <value index="vDEF">10</value>
                            </field>
                            <field index="urls">
                                <el index="el">' . $elements . '
                                </el>
                            </field>
                        </language>
                    </sheet>
                </data>
            </T3FlexForms>';
};

$configurations = [
    1 => ['Attraction, then the venue containing it', ['attraction-in-meeting-venue', '669150457039-gamn']],
    2 => ['Attraction, then the operated venue containing it', ['attraction-in-operated-meeting-venue', '117034427781-ndta']],
    3 => ['Attraction, then the venue with parking containing it', ['attraction-in-meeting-venue-with-parking', '160874095809-phhn']],
    4 => ['Attraction, then the venue twice', ['attraction-in-meeting-venue', '669150457039-gamn', '669150457039-gamn']],
    5 => ['Attraction, the venue, then another attraction in it', ['attraction-in-meeting-venue', '669150457039-gamn', 'attraction-also-in-meeting-venue']],
    6 => ['Attraction in an attraction in the venue, then the venue', ['attraction-in-attraction-in-meeting-venue', '669150457039-gamn']],
];

$rows = [];
foreach ($configurations as $uid => $definition) {
    $rows[] = [
        'uid' => (string)$uid,
        'pid' => '0',
        'disable' => '0',
        'title' => $definition[0],
        'type' => 'static',
        'configuration' => $configuration(...$definition[1]),
    ];
}

return [
    'pages' => [
        [
            'uid' => '3000',
            'pid' => '0',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Rootpage',
            'is_siteroot' => '1',
        ],
        [
            'uid' => '10',
            'pid' => '3000',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'title' => 'Record storage folder',
        ],
        [
            'uid' => '20',
            'pid' => '3000',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'title' => 'Category storage folder',
        ],
    ],
    'sys_category' => [
        [
            'uid' => '100',
            'pid' => '20',
            'parent' => '0',
            'title' => 'POIs',
        ],
    ],
    'tx_thuecat_import_configuration' => $rows,
];
