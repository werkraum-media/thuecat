<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

$configuration = static function (int $storagePid, string ...$urls): string {
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
                                <value index="vDEF">' . $storagePid . '</value>
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
    1 => ['Venue with one room', ['059632237210-rbpe']],
    2 => ['Venue with twelve rooms', ['669150457039-gamn']],
    3 => ['Venue with a room and an attraction', ['r_22033376-oapoi']],
    4 => ['Two venues', ['059632237210-rbpe', 'r_22033376-oapoi']],
    5 => ['Venue with a divided room', ['e_ttg00020050000287335-tomas']],
    6 => ['Venue with a combined room', ['234669135473-wkez']],
    7 => ['Restaurant inside a venue', ['771285888381-bqfq']],
    8 => ['Venue with a numbered room', ['121522223838-onak']],
    9 => ['Venue with a bookable room', ['819437258593-jnec']],
    10 => ['Venue with one room, second site', ['059632237210-rbpe'], 50],
];

$rows = [];
foreach ($configurations as $uid => $definition) {
    $rows[] = [
        'uid' => (string)$uid,
        'pid' => '0',
        'disable' => '0',
        'title' => $definition[0],
        'type' => 'static',
        'configuration' => $configuration($definition[2] ?? 10, ...$definition[1]),
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
        [
            'uid' => '5000',
            'pid' => '0',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Second site root',
            'is_siteroot' => '1',
        ],
        [
            'uid' => '50',
            'pid' => '5000',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'title' => 'Second site record storage',
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
