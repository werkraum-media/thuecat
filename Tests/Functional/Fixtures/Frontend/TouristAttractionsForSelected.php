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
            'title' => 'Storage for Attractions',
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
        [
            'uid' => '20',
            'pid' => '1',
            'title' => 'List Page EN',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'slug' => '/list/',
            'sorting' => '256',
            'sys_language_uid' => '1',
            'l10n_parent' => '10',
        ],
        [
            'uid' => '21',
            'pid' => '1',
            'title' => 'Storage for Attractions EN',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'sorting' => '256',
            'sys_language_uid' => '1',
            'l10n_parent' => '11',
        ],
        [
            'uid' => '22',
            'pid' => '1',
            'title' => 'Second Storage Folder EN',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'sorting' => '384',
            'sys_language_uid' => '1',
            'l10n_parent' => '12',
        ],
        [
            'uid' => '30',
            'pid' => '1',
            'title' => 'List Page ES',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'slug' => '/list/',
            'sorting' => '256',
            'sys_language_uid' => '3',
            'l10n_parent' => '10',
        ],
        [
            'uid' => '31',
            'pid' => '1',
            'title' => 'Storage for Attractions ES',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'sorting' => '256',
            'sys_language_uid' => '3',
            'l10n_parent' => '11',
        ],
        [
            'uid' => '32',
            'pid' => '1',
            'title' => 'Second Storage Folder ES',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'sorting' => '384',
            'sys_language_uid' => '3',
            'l10n_parent' => '12',
        ],
    ],
    'tt_content' => [
        [
            'uid' => '10',
            'pid' => '10',
            'hidden' => '0',
            'sorting' => '1',
            'CType' => 'werkraummedia_thuecatattractionlistselected',
            'header' => 'Selected Attraction List',
            'deleted' => '0',
            'starttime' => '0',
            'endtime' => '0',
            'colPos' => '0',
            'sys_language_uid' => '0',
            'pages' => '11',
            'recursive' => '0',
            'pi_flexform' => $selectedListFlexform('3,6,1,7,999'),
        ],
        [
            'uid' => '20',
            'pid' => '10',
            'hidden' => '0',
            'sorting' => '1',
            'CType' => 'werkraummedia_thuecatattractionlistselected',
            'header' => 'Selected Attraction List EN',
            'deleted' => '0',
            'starttime' => '0',
            'endtime' => '0',
            'colPos' => '0',
            'sys_language_uid' => '1',
            'l18n_parent' => '10',
            'pages' => '11',
            'recursive' => '0',
            'pi_flexform' => $selectedListFlexform('3,1'),
        ],
        [
            'uid' => '30',
            'pid' => '10',
            'hidden' => '0',
            'sorting' => '1',
            'CType' => 'werkraummedia_thuecatattractionlistselected',
            'header' => 'Selected Attraction List ES',
            'deleted' => '0',
            'starttime' => '0',
            'endtime' => '0',
            'colPos' => '0',
            'sys_language_uid' => '3',
            'l18n_parent' => '10',
            'pages' => '11',
            'recursive' => '0',
            'pi_flexform' => $selectedListFlexform('3,1'),
        ],
    ],
    'tx_thuecat_tourist_attraction' => [
        [
            'uid' => '1',
            'pid' => '11',
            'title' => 'Stadtmuseum Erfurt',
        ],
        [
            'uid' => '2',
            'pid' => '11',
            'title' => 'Domberg Erfurt',
        ],
        [
            'uid' => '3',
            'pid' => '12',
            'title' => 'Goethehaus Weimar',
        ],
        // Only 3 is translated; 1 has no translation in any language.
        [
            'uid' => '4',
            'pid' => '12',
            'title' => 'Goethe House Weimar',
            'sys_language_uid' => '1',
            'l18n_parent' => '3',
        ],
        [
            'uid' => '5',
            'pid' => '12',
            'title' => 'Casa de Goethe Weimar',
            'sys_language_uid' => '3',
            'l18n_parent' => '3',
        ],
        // Selected next to 999, which does not exist.
        [
            'uid' => '6',
            'pid' => '11',
            'title' => 'Hidden Attraction',
            'disable' => '1',
        ],
        [
            'uid' => '7',
            'pid' => '11',
            'title' => 'Deleted Attraction',
            'deleted' => '1',
        ],
    ],
];
