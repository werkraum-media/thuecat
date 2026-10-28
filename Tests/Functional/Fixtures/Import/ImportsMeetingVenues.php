<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

// Inside the attraction_categories site, so the categories of the venue types get their anchor.
$configuration = static fn (string $url): string => '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
            <T3FlexForms>
                <data>
                    <sheet index="sDEF">
                        <language index="lDEF">
                            <field index="storagePid">
                                <value index="vDEF">10</value>
                            </field>
                            <field index="urls">
                                <el index="el">
                                    <field index="venue">
                                        <value index="url">
                                            <el>
                                                <field index="url">
                                                    <value index="vDEF">' . $url . '</value>
                                                </field>
                                            </el>
                                        </value>
                                        <value index="_TOGGLE">0</value>
                                    </field>
                                </el>
                            </field>
                        </language>
                    </sheet>
                </data>
            </T3FlexForms>';

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
            'title' => 'Attraction storage folder',
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
    'tx_thuecat_import_configuration' => [
        [
            'uid' => '1',
            'pid' => '0',
            'disable' => '0',
            'title' => 'Conference hotel',
            'type' => 'static',
            'configuration' => $configuration('https://thuecat.org/resources/669150457039-gamn'),
        ],
        [
            'uid' => '2',
            'pid' => '0',
            'disable' => '0',
            'title' => 'Convention center',
            'type' => 'static',
            'configuration' => $configuration('https://thuecat.org/resources/766768028887-ohje'),
        ],
    ],
];
