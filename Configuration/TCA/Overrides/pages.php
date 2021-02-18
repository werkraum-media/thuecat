<?php

defined('TYPO3') or die();

(static function (string $extensionKey, string $tableName) {
    $languagePath = \WerkraumMedia\ThueCat\Extension::getLanguagePath()
        . 'locallang_tca.xlf:' . $tableName;

    \TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($GLOBALS['TCA'][$tableName], [
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

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItemGroup(
        $tableName,
        'doktype',
        \WerkraumMedia\ThueCat\Extension::TCA_SELECT_GROUP_IDENTIFIER,
        $languagePath . '.group'
    );
})(
    \WerkraumMedia\ThueCat\Extension::EXTENSION_KEY,
    'pages'
);
