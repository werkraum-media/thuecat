<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use WerkraumMedia\ThueCat\Extension;

defined('TYPO3') or die();

(static function () {
    ExtensionManagementUtility::addTcaSelectItem(
        'pages',
        'tx_thuecat_page_role',
        [
            'label' => Extension::getLanguagePath() . 'locallang_tca.xlf:pages.tx_thuecat_page_role.touristattractionlist',
            'value' => 'thuecat_touristattractionlist',
        ]
    );

    ExtensionManagementUtility::addTcaSelectItem(
        'pages',
        'tx_thuecat_page_role',
        [
            'label' => Extension::getLanguagePath() . 'locallang_tca.xlf:pages.tx_thuecat_page_role.touristattractionshow',
            'value' => 'thuecat_touristattractionshow',
        ]
    );

    ExtensionManagementUtility::addTcaSelectItem(
        'pages',
        'tx_thuecat_page_role',
        [
            'label' => Extension::getLanguagePath() . 'locallang_tca.xlf:pages.tx_thuecat_page_role.trailshow',
            'value' => 'thuecat_trailshow',
        ]
    );
})();
