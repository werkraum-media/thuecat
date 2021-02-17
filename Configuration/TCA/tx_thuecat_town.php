<?php

defined('TYPO3') or die();

return (static function (string $extensionKey, string $tableName) {
    $languagePath = \WerkraumMedia\ThueCat\Extension::getLanguagePath() . 'locallang_tca.xlf:' . $tableName;

    return [
        'ctrl' => [
            'label' => 'title',
            'iconfile' => \WerkraumMedia\ThueCat\Extension::getIconPath() . $tableName . '.svg',
            'default_sortby' => 'title',
            'tstamp' => 'tstamp',
            'crdate' => 'crdate',
            'cruser_id' => 'cruser_id',
            'title' => $languagePath,
            'enablecolumns' => [
                'disabled' => 'disable',
            ],
            'searchFields' => 'title',
        ],
        'columns' => [
            'title' => [
                'label' => $languagePath . '.title',
                'config' => [
                    'type' => 'input',
                    'size' => 20,
                    'max' => 255,
                    'readOnly' => true,
                ],
            ],
            'description' => [
                'label' => $languagePath . '.description',
                'config' => [
                    'type' => 'text',
                    'readOnly' => true,
                ],
            ],
            'remote_id' => [
                'label' => $languagePath . '.remote_id',
                'config' => [
                    'type' => 'input',
                    'readOnly' => true,
                ],
            ],
            'managed_by' => [
                'label' => $languagePath . '.managed_by',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'foreign_table' => 'tx_thuecat_organisation',
                    'items' => [
                        [
                            $languagePath . '.managed_by.unkown',
                            0,
                        ],
                    ],
                    'readOnly' => true,
                ],
            ],
            'tourist_information' => [
                'label' => $languagePath . '.tourist_information',
                'config' => [
                    'type' => 'inline',
                    'foreign_table' => 'tx_thuecat_tourist_information',
                    'foreign_field' => 'town',
                    'readOnly' => true,
                ],
            ],
        ],
        'types' => [
            '0' => [
                'showitem' => 'title, description, remote_id, tourist_information, managed_by',
            ],
        ],
    ];
})(\WerkraumMedia\ThueCat\Extension::EXTENSION_KEY, 'tx_thuecat_town');
