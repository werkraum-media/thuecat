<?php

declare(strict_types=1);

use WerkraumMedia\ThueCat\Extension;

defined('TYPO3') or die();

return (static function (string $tableName) {
    $languagePath = Extension::getLanguagePath() . 'locallang_tca.xlf:' . $tableName;

    return [
        'ctrl' => [
            'label' => 'title',
            'default_sortby' => 'valid_from',
            'tstamp' => 'tstamp',
            'crdate' => 'crdate',
            'delete' => 'deleted',
            'title' => $languagePath,
            'hideTable' => true,
            'enablecolumns' => [
                'disabled' => 'disable',
            ],
            'transOrigPointerField' => 'l18n_parent',
            'transOrigDiffSourceField' => 'l18n_diffsource',
            'languageField' => 'sys_language_uid',
            'translationSource' => 'l18n_source',
        ],
        'columns' => [
            'sys_language_uid' => [
                'exclude' => true,
                'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
                'config' => [
                    'type' => 'language',
                ],
            ],
            'l18n_parent' => [
                'displayCond' => 'FIELD:sys_language_uid:>:0',
                'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'items' => [
                        [
                            'label' => '',
                            'value' => 0,
                        ],
                    ],
                    'foreign_table' => $tableName,
                    'foreign_table_where' => 'AND ' . $tableName . '.pid=###CURRENT_PID### AND ' . $tableName . '.sys_language_uid IN (-1,0)',
                    'default' => 0,
                ],
            ],
            'l18n_diffsource' => [
                'config' => [
                    'type' => 'passthrough',
                    'default' => '',
                ],
            ],
            'l18n_source' => [
                'config' => [
                    'type' => 'passthrough',
                ],
            ],
            'disable' => [
                'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.enabled',
                'config' => [
                    'type' => 'check',
                    'renderType' => 'checkboxToggle',
                    'items' => [
                        [
                            'label' => '',
                            'invertStateDisplay' => true,
                        ],
                    ],
                ],
            ],
            'title' => [
                'label' => $languagePath . '.title',
                'config' => [
                    'type' => 'input',
                ],
            ],
            'description' => [
                'label' => $languagePath . '.description',
                'config' => [
                    'type' => 'text',
                ],
            ],
            'valid_from' => [
                'label' => $languagePath . '.valid_from',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'datetime',
                    'format' => 'date',
                    'dbType' => 'date',
                    'nullable' => true,
                ],
            ],
            // stored as text to preserve precision
            'latitude' => [
                'label' => $languagePath . '.latitude',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'input',
                    'size' => 20,
                ],
            ],
            'longitude' => [
                'label' => $languagePath . '.longitude',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'input',
                    'size' => 20,
                ],
            ],
            'remote_id' => [
                'label' => $languagePath . '.remote_id',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'input',
                    'searchable' => false,
                ],
            ],
        ],
        'types' => [
            '0' => [
                'showitem' => 'title, description, valid_from, latitude, longitude, remote_id',
            ],
        ],
    ];
})('tx_thuecat_trail_condition');
