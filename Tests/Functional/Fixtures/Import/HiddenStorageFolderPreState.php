<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

// One site whose record storage folder is hidden, holding an already-imported
// town with a stale title. A re-import must match that town despite the hidden
// folder: identity follows storage, not frontend visibility.
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
            'title' => 'Hidden record storage',
            'hidden' => '1',
        ],
        [
            'uid' => '4020',
            'pid' => '4000',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'title' => 'First site category storage',
        ],
        [
            'uid' => '4030',
            'pid' => '4000',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'title' => 'First site keyword storage',
        ],
    ],
    'sys_category' => [
        [
            'uid' => '4100',
            'pid' => '4020',
            'parent' => '0',
            'title' => 'First site categories',
        ],
        [
            'uid' => '4200',
            'pid' => '4030',
            'parent' => '0',
            'title' => 'First site keywords',
        ],
    ],
    'tx_thuecat_town' => [
        [
            'uid' => '1',
            'pid' => '4010',
            'remote_id' => 'https://thuecat.org/resources/043064193523-jcyt',
            'title' => 'Stale title on a hidden page',
            'managed_by' => '1',
        ],
    ],
    'tx_thuecat_organisation' => [
        [
            'uid' => '1',
            'pid' => '4010',
            'remote_id' => 'https://thuecat.org/resources/018132452787-ngbe',
            'title' => 'Stale organisation on a hidden page',
        ],
    ],
    'tx_thuecat_import_configuration' => [
        [
            'uid' => '1',
            'pid' => '0',
            'disable' => '0',
            'title' => 'Import into a hidden folder',
            'type' => 'static',
            'configuration' => '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
            <T3FlexForms>
                <data>
                    <sheet index="sDEF">
                        <language index="lDEF">
                            <field index="storagePid">
                                <value index="vDEF">4010</value>
                            </field>
                            <field index="urls">
                                <el index="el">
                                    <field index="602a89f54d694654233086">
                                        <value index="url">
                                            <el>
                                                <field index="url">
                                                    <value index="vDEF">https://thuecat.org/resources/043064193523-jcyt</value>
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
            </T3FlexForms>',
        ],
    ],
];
