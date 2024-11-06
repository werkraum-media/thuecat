<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use WerkraumMedia\ThueCat\Extension;

defined('TYPO3') or die();

(static function (string $extensionKey, string $tableName) {
    ExtensionManagementUtility::addStaticFile(
        $extensionKey,
        'Configuration/TypoScript/PageTypes',
        'ThüCAT - Page Types'
    );
})(
    Extension::EXTENSION_KEY,
    'sys_template'
);
