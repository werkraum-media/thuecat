<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

ExtensionManagementUtility::addTcaSelectItemGroup(
    'tt_content',
    'CType',
    'thuecat',
    'LLL:EXT:thuecat/Resources/Private/Language/locallang_tca.xlf:tt_content.group',
    'bottom',
);
