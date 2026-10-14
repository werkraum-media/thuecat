<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use WerkraumMedia\ThueCat\Extension;

defined('TYPO3') or die();

(static function (string $extensionKey, string $tableName) {
    $languagePath = Extension::getLanguagePath()
        . 'locallang_tca.xlf:' . $tableName;

    // @phpstan-ignore offsetAccess.nonOffsetAccessible, offsetAccess.nonOffsetAccessible, offsetAccess.nonOffsetAccessible, offsetAccess.nonOffsetAccessible (we put up with TCA Array for now)
    $GLOBALS['TCA'][$tableName]['ctrl']['typeicon_classes']['contains-thuecat'] = 'pages_module_thuecat';

    ExtensionManagementUtility::addTcaSelectItem(
        $tableName,
        'module',
        [
            'label' => $languagePath . '.module.thuecat',
            'value' => 'thuecat',
            'icon' => 'pages_module_thuecat',
        ]
    );

    // Each routed plugin adds its role item in its own pages_page_role_<plugin>.php, next to its route file.
    ExtensionManagementUtility::addTCAcolumns($tableName, [
        'tx_thuecat_page_role' => [
            'label' => $languagePath . '.tx_thuecat_page_role',
            'description' => $languagePath . '.tx_thuecat_page_role.description',
            // limitToPages evaluates the translated page row, so translations must carry the role.
            'l10n_mode' => 'exclude',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => '', 'value' => ''],
                ],
                'default' => '',
            ],
        ],
    ]);
    ExtensionManagementUtility::addFieldsToPalette(
        $tableName,
        'module',
        '--linebreak--, tx_thuecat_page_role',
        'after:module'
    );
})(
    Extension::EXTENSION_KEY,
    'pages'
);
