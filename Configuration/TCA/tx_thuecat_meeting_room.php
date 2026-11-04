<?php

declare(strict_types=1);

use WerkraumMedia\ThueCat\Extension;

defined('TYPO3') or die();

return (static function (string $extensionKey, string $tableName) {
    $languagePath = Extension::getLanguagePath() . 'locallang_tca.xlf:' . $tableName;
    $booleanItems = [
        ['label' => '', 'value' => ''],
        ['label' => Extension::getLanguagePath() . 'locallang_tca.xlf:tx_thuecat_tourist_attraction.boolean.true', 'value' => 'true'],
        ['label' => Extension::getLanguagePath() . 'locallang_tca.xlf:tx_thuecat_tourist_attraction.boolean.false', 'value' => 'false'],
    ];
    $integer = static fn (string $field): array => [
        'label' => $languagePath . '.' . $field,
        'l10n_mode' => 'exclude',
        'config' => [
            'type' => 'number',
            'readOnly' => true,
        ],
    ];
    $decimal = static fn (string $field): array => [
        'label' => $languagePath . '.' . $field,
        'l10n_mode' => 'exclude',
        'config' => [
            'type' => 'number',
            'format' => 'decimal',
            'readOnly' => true,
        ],
    ];
    $text = static fn (string $field): array => [
        'label' => $languagePath . '.' . $field,
        'l10n_mode' => 'exclude',
        'config' => [
            'type' => 'input',
            'readOnly' => true,
            'searchable' => false,
        ],
    ];
    $file = static fn (string $field, ?int $maxitems = null): array => [
        'label' => $languagePath . '.' . $field,
        'config' => array_filter([
            'type' => 'file',
            'allowed' => 'common-image-types',
            'maxitems' => $maxitems,
            'behaviour' => [
                'allowLanguageSynchronization' => true,
            ],
        ], static fn (mixed $value): bool => $value !== null),
    ];

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
            'url' => $text('url'),
            'floor_size' => $decimal('floor_size'),
            'room_height' => $decimal('room_height'),
            'capacity_banquet' => $integer('capacity_banquet'),
            'capacity_classroom' => $integer('capacity_classroom'),
            'capacity_theatre' => $integer('capacity_theatre'),
            'capacity_standing' => $integer('capacity_standing'),
            'facilities' => $text('facilities'),
            'number_of_rooms' => $integer('number_of_rooms'),
            'amenity_features' => $text('amenity_features'),
            'tour_booking_page' => $text('tour_booking_page'),
            'bookable' => [
                'label' => $languagePath . '.bookable',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'items' => $booleanItems,
                    'readOnly' => true,
                ],
            ],
            'main_image' => $file('main_image', 1),
            'media_files' => $file('media_files'),
            'floor_plan' => $file('floor_plan', 1),
            'managed_by' => [
                'label' => $languagePath . '.managed_by',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'foreign_table' => 'tx_thuecat_organisation',
                    'default' => '0',
                    'items' => [
                        [
                            'label' => Extension::getLanguagePath() . 'locallang_tca.xlf:tx_thuecat_tourist_attraction.managed_by.unkown',
                            'value' => 0,
                        ],
                    ],
                    'readOnly' => true,
                ],
            ],
            // Upstream direction (combined/divided) is not interpreted.
            'contained_rooms' => [
                'label' => $languagePath . '.contained_rooms',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectMultipleSideBySide',
                    'foreign_table' => 'tx_thuecat_meeting_room',
                    'MM' => 'tx_thuecat_meeting_room_contained_rooms_mm',
                    'readOnly' => true,
                ],
            ],
            'categories' => [
                'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_category.categories',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'category',
                    'treeConfig' => [
                        'startingPoints' => '###THUECAT_ANCHOR:tx_thuecat_meeting_room:category###',
                    ],
                ],
            ],
            'keywords' => [
                'label' => $languagePath . '.keywords',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'category',
                    'treeConfig' => [
                        'startingPoints' => '###THUECAT_ANCHOR:tx_thuecat_meeting_room:keywords###',
                    ],
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
                'showitem' => '--palette--;;language, disable, title, description, main_image, media_files, floor_plan,
                floor_size, room_height, capacity_banquet, capacity_classroom, capacity_theatre, capacity_standing,
                facilities, number_of_rooms, amenity_features, bookable, url, tour_booking_page, remote_id, tstamp,
                --div--;' . Extension::getLanguagePath() . 'locallang_tca.xlf:tx_thuecat_tourist_attraction.tab.relations,
                managed_by, contained_rooms,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories, categories, keywords',
            ],
        ],
    ];
})(Extension::EXTENSION_KEY, 'tx_thuecat_meeting_room');
