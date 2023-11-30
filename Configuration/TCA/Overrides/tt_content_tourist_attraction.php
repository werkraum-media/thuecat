<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use WerkraumMedia\ThueCat\Extension;

defined('TYPO3') or die();

(static function (string $extensionKey, string $tableName, string $cType) {
    $languagePath = Extension::getLanguagePath()
        . 'locallang_tca.xlf:' . $tableName . '.' . $cType;

    ArrayUtility::mergeRecursiveWithOverrule($GLOBALS['TCA'][$tableName], [
        'ctrl' => [
            'typeicon_classes' => [
                $cType => 'tt_content_' . $cType,
            ],
        ],
        'types' => [
            $cType => [
                'showitem' => '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,'
                    . '--palette--;;general,'
                    . '--palette--;;headers,'
                    . 'records,'
                    . '--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,'
                    . '--palette--;;frames,'
                    . '--palette--;;appearanceLinks,'
                    . '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,'
                    . '--palette--;;language,'
                    . '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,'
                    . '--palette--;;hidden,'
                    . '--palette--;;access,'
                    . '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,'
                    . '--div--;LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_category.tabs.category,'
                    . 'categories,'
                    . '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,'
                    . 'rowDescription,'
                    . '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended',
                'columnsOverrides' => [
                    'records' => [
                        'config' => [
                            'allowed' => 'tx_thuecat_tourist_attraction',
                            'suggestOptions' => [
                                'tx_thuecat_tourist_attraction' => [
                                    'addWhere' => 'sys_language_uid in (0,-1)',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    ExtensionManagementUtility::addTcaSelectItem(
        $tableName,
        'CType',
        [
            'label' => $languagePath,
            'value' => $cType,
            'icon' => Extension::getIconPath() . 'tt_content_' . $cType . '.svg',
            'group' => Extension::TCA_SELECT_GROUP_IDENTIFIER,
        ]
    );
})(
    Extension::EXTENSION_KEY,
    'tt_content',
    'thuecat_tourist_attraction'
);
