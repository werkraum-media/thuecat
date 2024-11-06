<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use WerkraumMedia\ThueCat\Extension;

ExtensionManagementUtility::addTcaSelectItemGroup(
    'tt_content',
    'CType',
    Extension::TCA_SELECT_GROUP_IDENTIFIER,
    Extension::getLanguagePath() . 'locallang_tca.xlf:tt_content.group',
    'bottom',
);
