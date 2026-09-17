<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

// One show page per precedence case, because the selection lives on the content
// element: varying it within a page would mean several plugins on one page.

$selectedRecordFlexform = static function (string $selectedRecord): string {
    return '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
<T3FlexForms>
    <data>
        <sheet index="sDEF">
            <language index="lDEF">
                <field index="settings.selectedRecord">
                    <value index="vDEF">' . $selectedRecord . '</value>
                </field>
            </language>
        </sheet>
    </data>
</T3FlexForms>';
};

$page = static function (
    int $uid,
    string $slug,
    string $title,
    int $languageId = 0,
    int $l10nParent = 0
): array {
    return [
        'uid' => (string)$uid,
        'pid' => '1',
        'title' => $title,
        'doktype' => PageRepository::DOKTYPE_DEFAULT,
        'slug' => $slug,
        'sorting' => (string)($uid * 16),
        'deleted' => '0',
        'sys_language_uid' => (string)$languageId,
        'l10n_parent' => (string)$l10nParent,
    ];
};

$showPlugin = static function (
    int $uid,
    int $pid,
    string $flexform,
    int $languageId = 0,
    int $l18nParent = 0
): array {
    return [
        'uid' => (string)$uid,
        'pid' => (string)$pid,
        'CType' => 'werkraummedia_thuecattrailshow',
        'header' => 'Show Plugin',
        'colPos' => '0',
        'sorting' => '256',
        'sys_language_uid' => (string)$languageId,
        'l18n_parent' => (string)$l18nParent,
        'hidden' => '0',
        'deleted' => '0',
        'pi_flexform' => $flexform,
    ];
};

$trail = static function (
    int $uid,
    string $title,
    string $disable = '0',
    int $languageId = 0,
    int $l18nParent = 0
): array {
    return [
        'uid' => (string)$uid,
        'pid' => '14',
        'disable' => $disable,
        'title' => $title,
        'description' => 'Beschreibung: ' . $title,
        'sys_language_uid' => (string)$languageId,
        'l18n_parent' => (string)$l18nParent,
    ];
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
        $page(10, '/preselected/', 'Preselected Trail'),
        $page(110, '/preselected/', 'Preselected Trail EN', 1, 10),
        $page(11, '/unconfigured/', 'No Preselection'),
        $page(12, '/preselected-hidden/', 'Preselected Hidden Trail'),
        $page(13, '/preselected-vanishing/', 'Preselected Trail To Delete'),
        $page(15, '/preselected-unknown/', 'Preselected Unknown Trail'),
        [
            'uid' => '14',
            'pid' => '1',
            'title' => 'Storage for Trails',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'sorting' => '512',
            'deleted' => '0',
        ],
        // Records are only found in a language whose storage folder exists there.
        [
            'uid' => '114',
            'pid' => '1',
            'title' => 'Storage for Trails EN',
            'doktype' => PageRepository::DOKTYPE_SYSFOLDER,
            'sorting' => '528',
            'deleted' => '0',
            'sys_language_uid' => '1',
            'l10n_parent' => '14',
        ],
    ],
    'tt_content' => [
        $showPlugin(10, 10, $selectedRecordFlexform('21')),
        // Translated content sits on the DEFAULT page uid; l18n_parent links it.
        $showPlugin(110, 10, $selectedRecordFlexform('21'), 1, 10),
        // Empty value, not a missing field: an element saved without a pick.
        $showPlugin(11, 11, $selectedRecordFlexform('')),
        $showPlugin(12, 12, $selectedRecordFlexform('20')),
        $showPlugin(13, 13, $selectedRecordFlexform('24')),
        // No row carries this uid.
        $showPlugin(15, 15, $selectedRecordFlexform('9999')),
    ],
    'tx_thuecat_trail' => [
        $trail(20, 'Versteckter Weg', '1'),
        $trail(21, 'Goethe-Erlebnisweg'),
        $trail(22, 'Goethe experience trail', '0', 1, 21),
        // The record a query parameter names while a selection is configured.
        $trail(23, 'Ilmtal-Radweg'),
        // Deleted mid-test to prove the empty state and its cache tag.
        $trail(24, 'Weg der verschwindet'),
    ],
];
