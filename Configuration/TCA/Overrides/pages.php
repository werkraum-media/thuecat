<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use WerkraumMedia\ThueCat\Extension;

defined('TYPO3') or die();

(static function (string $extensionKey, string $tableName) {
    $languagePath = Extension::getLanguagePath()
        . 'locallang_tca.xlf:' . $tableName;

    ArrayUtility::mergeRecursiveWithOverrule($GLOBALS['TCA'][$tableName], [
        'ctrl' => [
            'typeicon_classes' => [
                'contains-thuecat' => 'pages_module_thuecat',
            ],
        ],
        'columns' => [
            'tx_thuecat_flexform' => [
                'label' => $languagePath . '.tx_thuecat_flexform',
                'config' => [
                    'type' => 'flex',
                    'ds_pointerField' => 'doktype',
                    'ds' => [
                        'default' => '<T3DataStructure> <ROOT> <type>array</type> <el> <!-- Repeat an element like "xmlTitle" beneath for as many elements you like. Remember to name them uniquely --> <xmlTitle> <TCEforms> <label>The Title:</label> <config> <type>input</type> <size>48</size> </config> </TCEforms> </xmlTitle> </el> </ROOT> </T3DataStructure>',
                    ],
                ],
            ],
        ],
        'palettes' => [
            'tx_thuecat' => [
                'label' => $languagePath . 'palette.tx_thuecat',
                'showitem' => 'tx_thuecat_flexform',
            ],
        ],
    ]);

    ExtensionManagementUtility::addTcaSelectItemGroup(
        $tableName,
        'doktype',
        Extension::TCA_SELECT_GROUP_IDENTIFIER,
        $languagePath . '.group'
    );

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
