<?php

defined('TYPO3') or die();

(static function (string $extensionKey, string $tableName, int $doktype, string $pageIdentifier) {
    $languagePath = \WerkraumMedia\ThueCat\Extension::getLanguagePath()
        . 'locallang_tca.xlf:' . $tableName . '.' . $pageIdentifier;

    \TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($GLOBALS['TCA'][$tableName], [
        'ctrl' => [
            'typeicon_classes' => [
                $doktype => $tableName . '_' . $pageIdentifier,
            ],
        ],
        'types' => [
            $doktype => [
                'showitem' => ''
                    . '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,'
                        . 'doktype,'
                        . '--palette--;;title,'
                        . '--palette--;;tx_thuecat,'
                    . '--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.metadata,'
                        . '--palette--;;abstract,'
                        . '--palette--;;editorial,'
                    . '--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.appearance,'
                        . '--palette--;;layout,'
                    . '--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.behaviour,'
                        . '--palette--;;links,'
                        . '--palette--;;miscellaneous,'
                    . '--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.resources,'
                        . '--palette--;;media,'
                        . '--palette--;;config,'
                    . '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,'
                        . '--palette--;;language,'
                    . '--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.access,'
                        . '--palette--;;visibility,'
                        . '--palette--;;access,'
                    . '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,'
                    . '--div--;LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_category.tabs.category,'
                        . 'categories,'
                    . '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,'
                        . 'rowDescription,'
                    . '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended',
            ],
        ],
        'columns' => [
            'tx_thuecat_flexform' => [
                'config' => [
                    'ds' => [
                        $doktype => 'FILE:EXT:' . $extensionKey . '/Configuration/FlexForm/Pages/' . $pageIdentifier . '.xml',
                    ],
                ],
            ],
        ],
    ]);

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem(
        $tableName,
        'doktype',
        [
            $languagePath,
            $doktype,
            \WerkraumMedia\ThueCat\Extension::getIconPath() . $tableName . '_' . $pageIdentifier . '.svg',
            \WerkraumMedia\ThueCat\Extension::TCA_SELECT_GROUP_IDENTIFIER,
        ]
    );
})(
    \WerkraumMedia\ThueCat\Extension::EXTENSION_KEY,
    'pages',
    \WerkraumMedia\ThueCat\Extension::PAGE_DOKTYPE_TOURIST_ATTRACTION,
    'tourist_attraction'
);
