<?php

defined('TYPO3') or die();

return (static function (string $extensionKey, string $tableName) {
    $languagePath = \WerkraumMedia\ThueCat\Extension::getLanguagePath() . 'locallang_tca.xlf:' . $tableName;

    return [
        'ctrl' => [
            'label' => 'title',
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
            'manages_towns' => [
                'label' => $languagePath . '.manages_towns',
                'config' => [
                    'type' => 'inline',
                    'foreign_table' => 'tx_thuecat_town',
                    'foreign_field' => 'managed_by',
                    'readOnly' => true,
                ],
            ],
            'manages_tourist_information' => [
                'label' => $languagePath . '.manages_tourist_information',
                'config' => [
                    'type' => 'inline',
                    'foreign_table' => 'tx_thuecat_tourist_information',
                    'foreign_field' => 'managed_by',
                    'readOnly' => true,
                ],
            ],
            'manages_tourist_attraction' => [
                'label' => $languagePath . '.manages_tourist_attraction',
                'config' => [
                    'type' => 'inline',
                    'foreign_table' => 'tx_thuecat_tourist_attraction',
                    'foreign_field' => 'managed_by',
                    'readOnly' => true,
                ],
            ],
            'tstamp' => [
                'label' => $languagePath . '.tstamp',
                'config' => [
                    'type' => 'input',
                    'renderType' => 'inputDateTime',
                    'eval' => 'datetime',
                    'readOnly' => true,
                ],
            ],
        ],
        'types' => [
            '0' => [
                'showitem' => 'title, description, remote_id, tstamp'
                . ',--div--;' . $languagePath . '.div.manages'
                . ',manages_towns, manages_tourist_information, manages_tourist_attraction',
            ],
        ],
    ];
})(\WerkraumMedia\ThueCat\Extension::EXTENSION_KEY, 'tx_thuecat_organisation');
