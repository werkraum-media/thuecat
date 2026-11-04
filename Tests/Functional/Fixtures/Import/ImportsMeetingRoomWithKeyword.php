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
];

$rows = [];
foreach ($configurations as $uid => $definition) {
    $rows[] = [
        'uid' => (string)$uid,
        'pid' => '0',
        'disable' => '0',
        'title' => $definition[0],
        'type' => 'static',
        'configuration' => $configuration(10, ...$definition[1]),
    ];
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
];
