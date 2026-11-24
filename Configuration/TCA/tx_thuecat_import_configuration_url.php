<?php

declare(strict_types=1);

defined('TYPO3') || die();

return (static function (string $tableName): array {
    $languagePath = 'LLL:EXT:thuecat/Resources/Private/Language/locallang_tca.xlf:' . $tableName;

    return [
        'ctrl' => [
            'label' => 'title',
            'label_alt' => 'url',
            'tstamp' => 'tstamp',
            'crdate' => 'crdate',
            'title' => $languagePath,
            'delete' => 'deleted',
            'sortby' => 'sorting',
            'hideTable' => true,
            'security' => [
                'ignoreRootLevelRestriction' => true,
            ],
            'rootLevel' => -1,
        ],
        'columns' => [
            'title' => [
                'label' => $languagePath . '.title',
                'description' => $languagePath . '.title.description',
                'config' => [
                    'type' => 'input',
                    'eval' => 'trim',
                    'readOnly' => true,
                ],
            ],
            'url' => [
                'label' => $languagePath . '.url',
                'config' => [
                    'type' => 'input',
                    'eval' => 'trim',
                    'required' => true,
                ],
            ],
        ],
        'types' => [
            '0' => [
                'showitem' => 'url, title',
            ],
        ],
    ];
})('tx_thuecat_import_configuration_url');
