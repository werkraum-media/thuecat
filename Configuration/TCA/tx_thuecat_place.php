<?php

declare(strict_types=1);

use WerkraumMedia\ThueCat\Extension;

defined('TYPO3') or die();

return (static function (string $extensionKey, string $tableName) {
    $languagePath = Extension::getLanguagePath() . 'locallang_tca.xlf:' . $tableName;

    return [
        'ctrl' => [
            'label' => 'title',
            'default_sortby' => 'title',
            'tstamp' => 'tstamp',
            'crdate' => 'crdate',
            'title' => $languagePath,
            'delete' => 'deleted',
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
                ],
            ],
            'address_inline' => [
                'label' => $languagePath . '.address_inline',
                'config' => [
                    'type' => 'inline',
                    'foreign_table' => 'tx_thuecat_address',
                    'foreign_field' => 'parentid',
                    'foreign_table_field' => 'parenttable',
                    'appearance' => [
                        'enabledControls' => [
                            'new' => false,
                            'delete' => false,
                        ],
                        'collapseAll' => true,
                        'expandSingle' => true,
                    ],
                    'behaviour' => [
                        'allowLanguageSynchronization' => true,
                    ],
                ],
            ],
            'hosts_events' => [
                'label' => $languagePath . '.hosts_events',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectMultipleSideBySide',
                    'foreign_table' => 'tx_events_domain_model_event',
                    'MM' => 'tx_thuecat_place_hosts_events_mm',
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
                'showitem' => 'title, description, remote_id, address_inline, tstamp'
                . ',--div--;' . $languagePath . '.tab.events'
                . ',hosts_events'
                . ',--div--;LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language'
                . ',--palette--;;language',
            ],
        ],
    ];
})(Extension::EXTENSION_KEY, 'tx_thuecat_place');
