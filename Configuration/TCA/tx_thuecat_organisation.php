<?php

declare(strict_types=1);

use WerkraumMedia\ThueCat\Extension;

defined('TYPO3') or die();

return (static function (string $extensionKey, string $tableName) {
    $languagePath = Extension::getLanguagePath() . 'locallang_tca.xlf:' . $tableName;

    return [
        'ctrl' => [
            'label' => 'title',
            'iconfile' => Extension::getIconPath() . $tableName . '.svg',
            'default_sortby' => 'title',
            'tstamp' => 'tstamp',
            'crdate' => 'crdate',
            'title' => $languagePath,
            'languageField' => 'sys_language_uid',
            'transOrigPointerField' => 'l10n_parent',
            'transOrigDiffSourceField' => 'l10n_diffsource',
            'translationSource' => 'l10n_source',
            'enablecolumns' => [
                'disabled' => 'disable',
            ],
        ],
        'palettes' => [
            'language' => ['showitem' => 'sys_language_uid, l10n_parent'],
        ],
        'columns' => [
            'sys_language_uid' => [
                'exclude' => true,
                'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
                'config' => [
                    'type' => 'language',
                ],
            ],
            'l10n_parent' => [
                'displayCond' => 'FIELD:sys_language_uid:>:0',
                'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'items' => [
                        ['label' => '', 'value' => 0],
                    ],
                    'foreign_table' => $tableName,
                    'foreign_table_where' => 'AND ' . $tableName . '.pid=###CURRENT_PID### AND ' . $tableName . '.sys_language_uid IN (-1,0)',
                    'default' => 0,
                ],
            ],
            'l10n_diffsource' => [
                'config' => [
                    'type' => 'passthrough',
                ],
            ],
            'l10n_source' => [
                'config' => [
                    'type' => 'passthrough',
                ],
            ],
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
                    'searchable' => false,
                ],
            ],
            'remote_id' => [
                'label' => $languagePath . '.remote_id',
                'config' => [
                    'type' => 'input',
                    'readOnly' => true,
                    'searchable' => false,
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
                    'type' => 'datetime',
                    'format' => 'datetime',
                    'readOnly' => true,
                    'searchable' => false,
                ],
            ],
        ],
        'types' => [
            '0' => [
                'showitem' => 'title, description, remote_id, tstamp'
                . ',--div--;' . $languagePath . '.div.manages'
                . ',manages_towns, manages_tourist_information, manages_tourist_attraction'
                . ',--div--;LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language'
                . ',--palette--;;language',
            ],
        ],
    ];
})(Extension::EXTENSION_KEY, 'tx_thuecat_organisation');
