<?php

declare(strict_types=1);

use WerkraumMedia\ThueCat\Extension;
use WerkraumMedia\ThueCat\Import\Parser\Entity\TrailLocationEntity;

defined('TYPO3') or die();

return (static function (string $extensionKey, string $tableName) {
    $languagePath = Extension::getLanguagePath() . 'locallang_tca.xlf:' . $tableName;

    return [
        'ctrl' => [
            'label' => 'title',
            'default_sortby' => 'title',
            'tstamp' => 'tstamp',
            'crdate' => 'crdate',
            'delete' => 'deleted',
            'title' => $languagePath,
            'enablecolumns' => [
                'disabled' => 'disable',
            ],
            'transOrigPointerField' => 'l18n_parent',
            'transOrigDiffSourceField' => 'l18n_diffsource',
            'languageField' => 'sys_language_uid',
            'translationSource' => 'l10n_source',
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
            'l10n_source' => [
                'config' => [
                    'type' => 'passthrough',
                ],
            ],
            'title' => [
                'label' => $languagePath . '.title',
                'l10n_mode' => '',
                'config' => [
                    'type' => 'input',
                    'size' => 20,
                    'max' => 255,
                ],
            ],
            'description' => [
                'label' => $languagePath . '.description',
                'l10n_mode' => '',
                'config' => [
                    'type' => 'text',
                ],
            ],
            'remote_id' => [
                'label' => $languagePath . '.remote_id',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'input',
                ],
            ],
            'keywords' => [
                'label' => $languagePath . '.keywords',
                'config' => [
                    'type' => 'category',
                    'treeConfig' => [
                        'startingPoints' => '###SITE:settings.import.trails.keywords.parent###',
                    ],
                ],
            ],
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
                            'label' => $languagePath . '.managed_by.unkown',
                            'value' => 0,
                        ],
                    ],
                ],
            ],
            'main_image' => [
                'label' => $languagePath . '.main_image',
                'config' => [
                    'type' => 'file',
                    'allowed' => 'common-image-types',
                    'maxitems' => 1,
                    'behaviour' => [
                        'allowLanguageSynchronization' => true,
                    ],
                ],
            ],
            'media_files' => [
                'label' => $languagePath . '.media_files',
                'config' => [
                    'type' => 'file',
                    'allowed' => 'common-image-types',
                    'behaviour' => [
                        'allowLanguageSynchronization' => true,
                    ],
                ],
            ],
            'logo' => [
                'label' => $languagePath . '.logo',
                'config' => [
                    'type' => 'file',
                    'allowed' => 'common-image-types',
                    'maxitems' => 1,
                    'behaviour' => [
                        'allowLanguageSynchronization' => true,
                    ],
                ],
            ],
            'way_types' => [
                'label' => $languagePath . '.way_types',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'inline',
                    'foreign_table' => 'tx_thuecat_trail_way_type',
                    'foreign_field' => 'parentid',
                    'foreign_table_field' => 'parenttable',
                    'behaviour' => [
                        'allowLanguageSynchronization' => true,
                    ],
                ],
            ],
            'conditions' => [
                'label' => $languagePath . '.conditions',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'inline',
                    'foreign_table' => 'tx_thuecat_trail_condition',
                    'foreign_field' => 'parentid',
                    'foreign_table_field' => 'parenttable',
                    'foreign_default_sortby' => 'valid_from',
                    'behaviour' => [
                        'allowLanguageSynchronization' => true,
                    ],
                ],
            ],
            'start_location' => [
                'label' => $languagePath . '.start_location',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'inline',
                    'foreign_table' => 'tx_thuecat_trail_location',
                    'foreign_field' => 'parentid',
                    'foreign_table_field' => 'parenttable',
                    'foreign_match_fields' => [
                        'location_type' => TrailLocationEntity::TYPE_START,
                    ],
                    'maxitems' => 1,
                    'behaviour' => [
                        'allowLanguageSynchronization' => true,
                    ],
                ],
            ],
            'end_location' => [
                'label' => $languagePath . '.end_location',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'inline',
                    'foreign_table' => 'tx_thuecat_trail_location',
                    'foreign_field' => 'parentid',
                    'foreign_table_field' => 'parenttable',
                    'foreign_match_fields' => [
                        'location_type' => TrailLocationEntity::TYPE_END,
                    ],
                    'maxitems' => 1,
                    'behaviour' => [
                        'allowLanguageSynchronization' => true,
                    ],
                ],
            ],
            // Bitmask. Item order is a stored contract — see TrailSeason.
            'season' => [
                'label' => $languagePath . '.season',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'check',
                    'cols' => 4,
                    'default' => 0,
                    'items' => [
                        ['label' => $languagePath . '.season.Jan'],
                        ['label' => $languagePath . '.season.Feb'],
                        ['label' => $languagePath . '.season.Mar'],
                        ['label' => $languagePath . '.season.Apr'],
                        ['label' => $languagePath . '.season.May'],
                        ['label' => $languagePath . '.season.Jun'],
                        ['label' => $languagePath . '.season.Jul'],
                        ['label' => $languagePath . '.season.Aug'],
                        ['label' => $languagePath . '.season.Sep'],
                        ['label' => $languagePath . '.season.Oct'],
                        ['label' => $languagePath . '.season.Nov'],
                        ['label' => $languagePath . '.season.Dec'],
                        ['label' => $languagePath . '.season.AllYearRound'],
                    ],
                ],
            ],
            'opening_status' => [
                'label' => $languagePath . '.opening_status',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'items' => [
                        [
                            'label' => '',
                            'value' => '',
                        ],
                        [
                            'label' => $languagePath . '.opening_status.Open',
                            'value' => 'Open',
                        ],
                        [
                            'label' => $languagePath . '.opening_status.Closed',
                            'value' => 'Closed',
                        ],
                        [
                            'label' => $languagePath . '.opening_status.WeekendOnly',
                            'value' => 'WeekendOnly',
                        ],
                        [
                            'label' => $languagePath . '.opening_status.NoInformation',
                            'value' => 'NoInformation',
                        ],
                    ],
                    'default' => '',
                ],
            ],
            'short_description' => [
                'label' => $languagePath . '.short_description',
                'l10n_mode' => '',
                'config' => [
                    'type' => 'text',
                ],
            ],
            'directions' => [
                'label' => $languagePath . '.directions',
                'l10n_mode' => '',
                'config' => [
                    'type' => 'text',
                ],
            ],
            'getting_there' => [
                'label' => $languagePath . '.getting_there',
                'l10n_mode' => '',
                'config' => [
                    'type' => 'text',
                ],
            ],
            'parking' => [
                'label' => $languagePath . '.parking',
                'l10n_mode' => '',
                'config' => [
                    'type' => 'text',
                ],
            ],
            'public_transit' => [
                'label' => $languagePath . '.public_transit',
                'l10n_mode' => '',
                'config' => [
                    'type' => 'text',
                ],
            ],
            'safety_guidelines' => [
                'label' => $languagePath . '.safety_guidelines',
                'l10n_mode' => '',
                'config' => [
                    'type' => 'text',
                ],
            ],
            'equipment' => [
                'label' => $languagePath . '.equipment',
                'l10n_mode' => '',
                'config' => [
                    'type' => 'text',
                ],
            ],
            'additional_information' => [
                'label' => $languagePath . '.additional_information',
                'l10n_mode' => '',
                'config' => [
                    'type' => 'text',
                ],
            ],
            'tip' => [
                'label' => $languagePath . '.tip',
                'l10n_mode' => '',
                'config' => [
                    'type' => 'text',
                ],
            ],
            'elevation_profile' => [
                'label' => $languagePath . '.elevation_profile',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'text',
                ],
            ],
            'elevation_profile_fall_back' => [
                'label' => $languagePath . '.elevation_profile_fall_back',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'text',
                ],
            ],
            // The whole coordinate track; mediumtext in ext_tables.sql.
            'route_line' => [
                'label' => $languagePath . '.route_line',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'text',
                ],
            ],
            'gpx_url' => [
                'label' => $languagePath . '.gpx_url',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'text',
                ],
            ],
            // Metrics stay strings so the source's precision survives DataHandler.
            'distance' => [
                'label' => $languagePath . '.distance',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'input',
                    'searchable' => false,
                ],
            ],
            'distance_unit' => [
                'label' => $languagePath . '.distance_unit',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'input',
                    'searchable' => false,
                ],
            ],
            'duration' => [
                'label' => $languagePath . '.duration',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'input',
                    'searchable' => false,
                ],
            ],
            'duration_unit' => [
                'label' => $languagePath . '.duration_unit',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'input',
                    'searchable' => false,
                ],
            ],
            'exercise_type' => [
                'label' => $languagePath . '.exercise_type',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'input',
                    'searchable' => false,
                ],
            ],
            'min_altitude' => [
                'label' => $languagePath . '.min_altitude',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'input',
                    'searchable' => false,
                ],
            ],
            'max_altitude' => [
                'label' => $languagePath . '.max_altitude',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'input',
                    'searchable' => false,
                ],
            ],
            'ascent_elevation' => [
                'label' => $languagePath . '.ascent_elevation',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'input',
                    'searchable' => false,
                ],
            ],
            'descent_elevation' => [
                'label' => $languagePath . '.descent_elevation',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'input',
                    'searchable' => false,
                ],
            ],
            // Each rating carries the scale it was measured on.
            'rating_landscape' => [
                'label' => $languagePath . '.rating_landscape',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'input',
                    'searchable' => false,
                ],
            ],
            'rating_landscape_min' => [
                'label' => $languagePath . '.rating_landscape_min',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'input',
                    'searchable' => false,
                ],
            ],
            'rating_landscape_max' => [
                'label' => $languagePath . '.rating_landscape_max',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'input',
                    'searchable' => false,
                ],
            ],
            'rating_condition' => [
                'label' => $languagePath . '.rating_condition',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'input',
                    'searchable' => false,
                ],
            ],
            'rating_condition_min' => [
                'label' => $languagePath . '.rating_condition_min',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'input',
                    'searchable' => false,
                ],
            ],
            'rating_condition_max' => [
                'label' => $languagePath . '.rating_condition_max',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'input',
                    'searchable' => false,
                ],
            ],
            'rating_difficulty' => [
                'label' => $languagePath . '.rating_difficulty',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'input',
                    'searchable' => false,
                ],
            ],
            'rating_difficulty_min' => [
                'label' => $languagePath . '.rating_difficulty_min',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'input',
                    'searchable' => false,
                ],
            ],
            'rating_difficulty_max' => [
                'label' => $languagePath . '.rating_difficulty_max',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'input',
                    'searchable' => false,
                ],
            ],
            'rating_quality_of_experience' => [
                'label' => $languagePath . '.rating_quality_of_experience',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'input',
                    'searchable' => false,
                ],
            ],
            'rating_quality_of_experience_min' => [
                'label' => $languagePath . '.rating_quality_of_experience_min',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'input',
                    'searchable' => false,
                ],
            ],
            'rating_quality_of_experience_max' => [
                'label' => $languagePath . '.rating_quality_of_experience_max',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'input',
                    'searchable' => false,
                ],
            ],
            'rating_technique' => [
                'label' => $languagePath . '.rating_technique',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'input',
                    'searchable' => false,
                ],
            ],
            'rating_technique_min' => [
                'label' => $languagePath . '.rating_technique_min',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'input',
                    'searchable' => false,
                ],
            ],
            'rating_technique_max' => [
                'label' => $languagePath . '.rating_technique_max',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'input',
                    'searchable' => false,
                ],
            ],
        ],
        'palettes' => [
            'language' => [
                'label' => $languagePath . '.palette.language',
                'showitem' => 'sys_language_uid,l18n_parent',
            ],
        ],
        'types' => [
            '0' => [
                'showitem' => '--palette--;;language, disable, title, description, short_description,
                main_image, media_files, logo,
                opening_status, season, remote_id, managed_by, keywords,
                --div--;' . $languagePath . '.tab.descriptions, directions, getting_there, parking,
                public_transit, safety_guidelines, equipment, additional_information, tip,
                --div--;' . $languagePath . '.tab.route, start_location, end_location, route_line, gpx_url,
                elevation_profile,
                elevation_profile_fall_back, distance, distance_unit, duration, duration_unit, exercise_type,
                min_altitude, max_altitude, ascent_elevation, descent_elevation, way_types,
                --div--;' . $languagePath . '.tab.conditions, conditions,
                --div--;' . $languagePath . '.tab.ratings, rating_landscape, rating_landscape_min,
                rating_landscape_max, rating_condition, rating_condition_min, rating_condition_max,
                rating_difficulty, rating_difficulty_min, rating_difficulty_max,
                rating_quality_of_experience, rating_quality_of_experience_min,
                rating_quality_of_experience_max, rating_technique, rating_technique_min,
                rating_technique_max',
            ],
        ],
    ];
})(Extension::EXTENSION_KEY, 'tx_thuecat_trail');
