<?php

defined('TYPO3') or die();

(static function (string $extensionKey, string $tableName) {
    $languagePath = \WerkraumMedia\ThueCat\Extension::getLanguagePath() . 'locallang_be.xlf:' . $tableName . '.';

    \TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($GLOBALS['SiteConfiguration']['site'], [
        'columns' => [
            'thuecat_api_key' => [
                'label' => $languagePath . 'thuecat_api_key',
                'config' => [
                    'type' => 'input',
                ],
            ],
        ],
        'types' => [
            '0' => [
                'showitem' => $GLOBALS['SiteConfiguration']['site']['types']['0']['showitem']
                . ', ' . implode(',', [
                    '--div--;' . $languagePath . 'div.thuecat',
                    'thuecat_api_key',
                ]),
            ],
        ],
    ]);
})(\WerkraumMedia\ThueCat\Extension::EXTENSION_KEY, 'site');
