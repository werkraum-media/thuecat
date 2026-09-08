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
})(
    Extension::EXTENSION_KEY,
    'pages'
);
