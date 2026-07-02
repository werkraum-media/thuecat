<?php

declare(strict_types=1);

use WerkraumMedia\ThueCat\Extension;
use WerkraumMedia\ThueCat\Import\Parser\Entity\OpeningHourSpecificationEntity;

defined('TYPO3') or die();

return (static function (string $tableName) {
    $languagePath = Extension::getLanguagePath() . 'locallang_tca.xlf:' . $tableName;

    return [
        'ctrl' => [
            'label' => 'day_of_week',
            'label_alt' => 'opens,closes',
            'label_alt_force' => true,
            'default_sortby' => 'valid_from, day_of_week, opens',
            'tstamp' => 'tstamp',
            'crdate' => 'crdate',
            'delete' => 'deleted',
            'title' => $languagePath,
            'hideTable' => true,
            'enablecolumns' => [
                'disabled' => 'disable',
            ],
        ],
        'columns' => [
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
            'specification_type' => [
                'label' => $languagePath . '.specification_type',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'items' => [
                        ['label' => $languagePath . '.specification_type.regular', 'value' => OpeningHourSpecificationEntity::TYPE_REGULAR],
                        ['label' => $languagePath . '.specification_type.special', 'value' => OpeningHourSpecificationEntity::TYPE_SPECIAL],
                    ],
                    'default' => OpeningHourSpecificationEntity::TYPE_REGULAR,
                ],
            ],
            'day_of_week' => [
                'label' => $languagePath . '.day_of_week',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'items' => [
                        ['label' => '', 'value' => ''],
                        ['label' => $languagePath . '.day_of_week.monday', 'value' => 'Monday'],
                        ['label' => $languagePath . '.day_of_week.tuesday', 'value' => 'Tuesday'],
                        ['label' => $languagePath . '.day_of_week.wednesday', 'value' => 'Wednesday'],
                        ['label' => $languagePath . '.day_of_week.thursday', 'value' => 'Thursday'],
                        ['label' => $languagePath . '.day_of_week.friday', 'value' => 'Friday'],
                        ['label' => $languagePath . '.day_of_week.saturday', 'value' => 'Saturday'],
                        ['label' => $languagePath . '.day_of_week.sunday', 'value' => 'Sunday'],
                        ['label' => $languagePath . '.day_of_week.publicHolidays', 'value' => 'PublicHolidays'],
                    ],
                    'default' => '',
                ],
            ],
            'opens' => [
                'label' => $languagePath . '.opens',
                'config' => [
                    'type' => 'datetime',
                    'format' => 'time',
                    'dbType' => 'time',
                ],
            ],
            'closes' => [
                'label' => $languagePath . '.closes',
                'config' => [
                    'type' => 'datetime',
                    'format' => 'time',
                    'dbType' => 'time',
                ],
            ],
            'valid_from' => [
                'label' => $languagePath . '.valid_from',
                'config' => [
                    'type' => 'datetime',
                    'format' => 'date',
                    'dbType' => 'date',
                    'nullable' => true,
                ],
            ],
            'valid_through' => [
                'label' => $languagePath . '.valid_through',
                'config' => [
                    'type' => 'datetime',
                    'format' => 'date',
                    'dbType' => 'date',
                    'nullable' => true,
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
                'showitem' => 'specification_type, day_of_week, opens, closes, valid_from, valid_through, remote_id',
            ],
        ],
    ];
})('tx_thuecat_opening_hours');
