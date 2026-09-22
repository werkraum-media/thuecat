<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

$configuration = static function (string $url): string {
    return '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
        <T3FlexForms>
            <data>
                <sheet index="sDEF">
                    <language index="lDEF">
                        <field index="storagePid">
                            <value index="vDEF">10</value>
                        </field>
                        <field index="urls">
                            <el index="el">
                                <field index="602a89f54d694654233086">
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
        </T3FlexForms>
    ';
};

return [
    'pages' => [
        0 => [
            'uid' => '1',
            'pid' => '0',
            'tstamp' => '1613400587',
            'crdate' => '1613400558',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Rootpage',
            'is_siteroot' => '1',
        ],
        1 => [
            'uid' => '10',
            'pid' => '1',
            'tstamp' => '1613400587',
            'crdate' => '1613400558',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'title' => 'Storage folder',
        ],
    ],
    'tx_thuecat_import_configuration' => [
        0 => [
            'uid' => '1',
            'pid' => '0',
            'tstamp' => '1613400587',
            'crdate' => '1613400558',
            'disable' => '0',
            'title' => 'Organisation with address',
            'type' => 'static',
            'configuration' => $configuration('https://thuecat.org/resources/900000000010-orgaddr'),
        ],
        1 => [
            'uid' => '2',
            'pid' => '0',
            'tstamp' => '1613400587',
            'crdate' => '1613400558',
            'disable' => '0',
            'title' => 'Town with address',
            'type' => 'static',
            'configuration' => $configuration('https://thuecat.org/resources/900000000011-townaddr'),
        ],
        2 => [
            'uid' => '3',
            'pid' => '0',
            'tstamp' => '1613400587',
            'crdate' => '1613400558',
            'disable' => '0',
            'title' => 'Tourist information with address',
            'type' => 'static',
            'configuration' => $configuration('https://thuecat.org/resources/900000000012-tiaddr'),
        ],
        3 => [
            'uid' => '4',
            'pid' => '0',
            'tstamp' => '1613400587',
            'crdate' => '1613400558',
            'disable' => '0',
            'title' => 'Organisation without address',
            'type' => 'static',
            'configuration' => $configuration('https://thuecat.org/resources/900000000013-orgnoaddr'),
        ],
    ],
];
