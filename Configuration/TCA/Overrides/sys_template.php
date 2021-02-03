<?php

defined('TYPO3') or die();

(static function (string $extensionKey, string $tableName) {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
        $extensionKey,
        'Configuration/TypoScript/',
        'ThüCAT'
    );
})(
    \WerkraumMedia\ThueCat\Extension::EXTENSION_KEY,
    'sys_template'
);
