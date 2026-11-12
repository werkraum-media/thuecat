<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Schema\Struct\SelectItem;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') || die();

(static function (): void {
    ExtensionManagementUtility::addTcaSelectItem(
        'pages',
        'tx_thuecat_page_role',
        new SelectItem(type: 'select', label: 'LLL:EXT:thuecat/Resources/Private/Language/locallang_tca.xlf:pages.tx_thuecat_page_role.touristattractionlist', value: 'thuecat_touristattractionlist')
    );

    ExtensionManagementUtility::addTcaSelectItem(
        'pages',
        'tx_thuecat_page_role',
        new SelectItem(type: 'select', label: 'LLL:EXT:thuecat/Resources/Private/Language/locallang_tca.xlf:pages.tx_thuecat_page_role.touristattractionshow', value: 'thuecat_touristattractionshow')
    );

    ExtensionManagementUtility::addTcaSelectItem(
        'pages',
        'tx_thuecat_page_role',
        new SelectItem(type: 'select', label: 'LLL:EXT:thuecat/Resources/Private/Language/locallang_tca.xlf:pages.tx_thuecat_page_role.trailshow', value: 'thuecat_trailshow')
    );
})();
