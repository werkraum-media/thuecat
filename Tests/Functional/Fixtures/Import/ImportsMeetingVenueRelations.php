<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

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
        [
            'uid' => '30',
            'pid' => '3000',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'title' => 'Event storage folder',
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
            'title' => 'Second site event storage',
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
            'title' => 'Museum hosting meetings',
            'type' => 'static',
            'configuration' => $configuration('https://thuecat.org/resources/117034427781-ndta'),
        ],
        [
            'uid' => '2',
            'pid' => '0',
            'disable' => '0',
            'title' => 'Riding hall hosting meetings',
            'type' => 'static',
            'configuration' => $configuration('https://thuecat.org/resources/700475118524-zdca'),
        ],
        [
            'uid' => '3',
            'pid' => '0',
            'disable' => '0',
            'title' => 'Cultural estate hosting meetings',
            'type' => 'static',
            'configuration' => $configuration('https://thuecat.org/resources/332021174274-eyhf'),
        ],
    ],
];
