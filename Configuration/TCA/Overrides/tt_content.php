<?php

defined('TYPO3') or die();

(static function (string $extensionKey, string $tableName) {
    $languagePath = \WerkraumMedia\ThueCat\Extension::getLanguagePath()
        . 'locallang_tca.xlf:' . $tableName;

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItemGroup(
        $tableName,
        'CType',
        \WerkraumMedia\ThueCat\Extension::TT_CONTENT_GROUP,
        $languagePath . '.group'
    );
})(
    \WerkraumMedia\ThueCat\Extension::EXTENSION_KEY,
    'tt_content'
);
