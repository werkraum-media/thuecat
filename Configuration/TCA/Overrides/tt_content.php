<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use WerkraumMedia\ThueCat\Extension;

defined('TYPO3') or die();

(static function (string $extensionKey, string $tableName) {
    $languagePath = Extension::getLanguagePath()
        . 'locallang_tca.xlf:' . $tableName;

    ExtensionManagementUtility::addTcaSelectItemGroup(
        $tableName,
        'CType',
        Extension::TCA_SELECT_GROUP_IDENTIFIER,
        $languagePath . '.group'
    );
})(
    Extension::EXTENSION_KEY,
    'tt_content'
);
