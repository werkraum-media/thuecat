<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

// Railguard + isolation case. Two decoy categories carry the SAME remote_id
// ("type:thuecat:CultureEvent") but MUST NOT be reused:
//   - uid 301: in-site, but under a DIFFERENT parent (200), so 100 is not in
//     its rootline → rejected by the rootline guard.
//   - uid 401: on page 90, which belongs to a DIFFERENT site (siteroot 90) →
//     outside the storagePid site's page set → never seen.
// The import must therefore CREATE a fresh category under parent 100 at pid 20,
// leaving both decoys untouched.
return [
    'pages' => [
        [
            'uid' => '1',
            'pid' => '0',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Rootpage',
            'is_siteroot' => '1',
        ],
        [
            'uid' => '10',
            'pid' => '1',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'title' => 'Event storage folder',
        ],
        [
            'uid' => '20',
            'pid' => '1',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'title' => 'Category storage folder',
        ],
        [
            'uid' => '90',
            'pid' => '0',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Other site root',
            'is_siteroot' => '1',
        ],
    ],
    'sys_category' => [
        [
            'uid' => '100',
            'pid' => '20',
            'parent' => '0',
            'title' => 'Events',
        ],
        // Different parent, in-site: rootline does NOT contain 100.
        [
            'uid' => '200',
            'pid' => '20',
            'parent' => '0',
            'title' => 'Other root category',
        ],
        [
            'uid' => '301',
            'pid' => '20',
            'parent' => '200',
            'title' => 'Kulturveranstaltung',
            'remote_id' => 'type:thuecat:CultureEvent',
        ],
        // Different site entirely (pid 90).
        [
            'uid' => '401',
            'pid' => '90',
            'parent' => '0',
            'title' => 'Kulturveranstaltung',
            'remote_id' => 'type:thuecat:CultureEvent',
        ],
    ],
    'tx_thuecat_import_configuration' => [
        [
            'uid' => '1',
            'pid' => '0',
            'disable' => '0',
            'title' => 'Kreuzchor event import',
            'type' => 'static',
            'configuration' => '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
            <T3FlexForms>
                <data>
                    <sheet index="sDEF">
                        <language index="lDEF">
                            <field index="storagePid">
                                <value index="vDEF">10</value>
                            </field>
                            <field index="categoryStoragePid">
                                <value index="vDEF">20</value>
                            </field>
                            <field index="categoryParent">
                                <value index="vDEF">100</value>
                            </field>
                            <field index="urls">
                                <el index="el">
                                    <field index="evt-kreuzchor">
                                        <value index="url">
                                            <el>
                                                <field index="url">
                                                    <value index="vDEF">https://cdb.int.thuecat.org/api/resources/e_19542-hubev</value>
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
