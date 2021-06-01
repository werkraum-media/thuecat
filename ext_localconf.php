<?php

defined('TYPO3') or die();

\WerkraumMedia\ThueCat\Extension::registerConfig();

(static function (string $extensionKey) {
    TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScriptSetup(
        '@import "EXT:' . $extensionKey . '/Configuration/TypoScript/Default/Setup.typoscript"'
    );
})(\WerkraumMedia\ThueCat\Extension::EXTENSION_KEY);
