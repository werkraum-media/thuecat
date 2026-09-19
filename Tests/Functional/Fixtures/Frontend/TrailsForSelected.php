<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

$selectedListFlexform = static function (string $selectedRecords): string {
    return '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
<T3FlexForms>
    <data>
        <sheet index="sDEF">
            <language index="lDEF">
                <field index="settings.selectedRecords">
                    <value index="vDEF">' . $selectedRecords . '</value>
                </field>
            </language>
        </sheet>
    </data>
</T3FlexForms>';
};

return [
    'pages' => [
        [
            'uid' => '1',
            'pid' => '0',
            'title' => 'Root',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'slug' => '/',
            'sorting' => '128',
            'deleted' => '0',
        ],
        [
            'uid' => '10',
            'pid' => '1',
            'title' => 'List Page',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'slug' => '/list/',
            'sorting' => '256',
            'deleted' => '0',
        ],
        [
            'uid' => '11',
            'pid' => '1',
            'title' => 'Storage for Trails',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'sorting' => '256',
            'deleted' => '0',
        ],
        [
            'uid' => '12',
            'pid' => '1',
            'title' => 'Second Storage Folder',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'sorting' => '384',
            'deleted' => '0',
        ],
    ],
    'tt_content' => [
        [
            'uid' => '10',
            'pid' => '10',
            'hidden' => '0',
            'sorting' => '1',
            'CType' => 'werkraummedia_thuecattraillistselected',
            'header' => 'Selected Trail List',
            'deleted' => '0',
            'starttime' => '0',
            'endtime' => '0',
            'colPos' => '0',
            'sys_language_uid' => '0',
            'pages' => '11',
            'recursive' => '0',
            'pi_flexform' => $selectedListFlexform('3,1'),
        ],
    ],
    'tx_thuecat_trail' => [
        [
            'uid' => '1',
            'pid' => '11',
            'title' => 'Goethe-Erlebnisweg',
        ],
        [
            'uid' => '2',
            'pid' => '11',
            'title' => 'Lutherweg Thüringen',
        ],
        // On the second storage folder: selection ignores the storage page.
        [
            'uid' => '3',
            'pid' => '12',
            'title' => 'Ilmtal-Radweg',
        ],
    ],
];
