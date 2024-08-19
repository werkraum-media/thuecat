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
            'enablecolumns' => [
                'disabled' => 'disable',
            ],
            'searchFields' => 'title, description',
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
            'disable' => [
                'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.enabled',
                'config' => [
                    'type' => 'check',
                    'renderType' => 'checkboxToggle',
                    'default' => 0,
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
                'l10n_mode' => 'prefixLangTitle',
                'config' => [
                    'type' => 'input',
                    'size' => 20,
                    'max' => 255,
                    'readOnly' => true,
                ],
            ],
            'description' => [
                'label' => $languagePath . '.description',
                'l10n_mode' => 'prefixLangTitle',
                'config' => [
                    'type' => 'text',
                    'readOnly' => true,
                ],
            ],
            'slogan' => [
                'label' => $languagePath . '.slogan',
                'l10n_mode' => 'prefixLangTitle',
                'config' => [
                    'type' => 'input',
                    'readOnly' => true,
                ],
            ],
            'start_of_construction' => [
                'label' => $languagePath . '.start_of_construction',
                'l10n_mode' => 'prefixLangTitle',
                'config' => [
                    'type' => 'input',
                    'readOnly' => true,
                ],
            ],
            'sanitation' => [
                'label' => $languagePath . '.sanitation',
                'l10n_mode' => 'prefixLangTitle',
                'config' => [
                    'type' => 'input',
                    'readOnly' => true,
                ],
            ],
            'other_service' => [
                'label' => $languagePath . '.other_service',
                'l10n_mode' => 'prefixLangTitle',
                'config' => [
                    'type' => 'input',
                    'readOnly' => true,
                ],
            ],
            'museum_service' => [
                'label' => $languagePath . '.museum_service',
                'l10n_mode' => 'prefixLangTitle',
                'config' => [
                    'type' => 'input',
                    'readOnly' => true,
                ],
            ],
            'architectural_style' => [
                'label' => $languagePath . '.architectural_style',
                'l10n_mode' => 'prefixLangTitle',
                'config' => [
                    'type' => 'input',
                    'readOnly' => true,
                ],
            ],
            'traffic_infrastructure' => [
                'label' => $languagePath . '.traffic_infrastructure',
                'l10n_mode' => 'prefixLangTitle',
                'config' => [
                    'type' => 'input',
                    'readOnly' => true,
                ],
            ],
            'payment_accepted' => [
                'label' => $languagePath . '.payment_accepted',
                'l10n_mode' => 'prefixLangTitle',
                'config' => [
                    'type' => 'input',
                    'readOnly' => true,
                ],
            ],
            'digital_offer' => [
                'label' => $languagePath . '.digital_offer',
                'l10n_mode' => 'prefixLangTitle',
                'config' => [
                    'type' => 'input',
                    'readOnly' => true,
                ],
            ],
            'photography' => [
                'label' => $languagePath . '.photography',
                'l10n_mode' => 'prefixLangTitle',
                'config' => [
                    'type' => 'input',
                    'readOnly' => true,
                ],
            ],
            'pets_allowed' => [
                'label' => $languagePath . '.pets_allowed',
                'l10n_mode' => 'prefixLangTitle',
                'config' => [
                    'type' => 'input',
                    'readOnly' => true,
                ],
            ],
            'is_accessible_for_free' => [
                'label' => $languagePath . '.is_accessible_for_free',
                'l10n_mode' => 'prefixLangTitle',
                'config' => [
                    'type' => 'input',
                    'readOnly' => true,
                ],
            ],
            'public_access' => [
                'label' => $languagePath . '.public_access',
                'l10n_mode' => 'prefixLangTitle',
                'config' => [
                    'type' => 'input',
                    'readOnly' => true,
                ],
            ],
            'available_languages' => [
                'label' => $languagePath . '.available_languages',
                'l10n_mode' => 'prefixLangTitle',
                'config' => [
                    'type' => 'input',
                    'readOnly' => true,
                ],
            ],
            'distance_to_public_transport' => [
                'label' => $languagePath . '.distance_to_public_transport',
                'l10n_mode' => 'prefixLangTitle',
                'config' => [
                    'type' => 'input',
                    'readOnly' => true,
                ],
            ],
            'opening_hours' => [
                'label' => $languagePath . '.opening_hours',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'text',
                    'readOnly' => true,
                ],
            ],
            'special_opening_hours' => [
                'label' => $languagePath . '.special_opening_hours',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'text',
                    'readOnly' => true,
                ],
            ],
            'address' => [
                'label' => $languagePath . '.address',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'text',
                    'readOnly' => true,
                ],
            ],
            'url' => [
                'label' => $languagePath . '.url',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'input',
                    'readOnly' => true,
                ],
            ],
            'media' => [
                'label' => $languagePath . '.media',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'text',
                    'readOnly' => true,
                ],
            ],
            'offers' => [
                'label' => $languagePath . '.offers',
                'config' => [
                    'type' => 'text',
                    'readOnly' => true,
                ],
            ],
            'accessibility_specification' => [
                'label' => $languagePath . '.accessibility_specification',
                'config' => [
                    'type' => 'text',
                    'readOnly' => true,
                ],
            ],
            'remote_id' => [
                'label' => $languagePath . '.remote_id',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'input',
                    'readOnly' => true,
                ],
            ],
            'town' => [
                'label' => $languagePath . '.town',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'foreign_table' => 'tx_thuecat_town',
                    'default' => '0',
                    'items' => [
                        [
                            'label' => $languagePath . '.town.unkown',
                            'value' => 0,
                        ],
                    ],
                    'readOnly' => true,
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
                    'readOnly' => true,
                ],
            ],
            'parking_facility_near_by' => [
                'label' => $languagePath . '.parking_facility_near_by',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'group',
                    'allowed' => 'tx_thuecat_parking_facility',
                    'foreign_table' => 'tx_thuecat_parking_facility',
                    'suggestOptions' => [
                        'tx_thuecat_parking_facility' => [
                            'searchCondition' => 'sys_language_uid IN (0,-1)',
                        ],
                    ],
                    'readOnly' => true,
                ],
            ],

            'editorial_images' => [
                'label' => $languagePath . '.editorial_images',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'file',
                    'allowed' => 'common-image-types',
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
                'showitem' => '--palette--;;language, title, description, slogan, start_of_construction, sanitation, other_service, museum_service, architectural_style, traffic_infrastructure, payment_accepted, digital_offer, photography, pets_allowed, is_accessible_for_free, public_access, available_languages, distance_to_public_transport, opening_hours, special_opening_hours, offers, accessibility_specification, address, url, media, remote_id, --div--;' . $languagePath . '.tab.relations, town, managed_by, parking_facility_near_by, --div--;' . $languagePath . '.tab.editorial_additions, editorial_images',
            ],
        ],
    ];
})(Extension::EXTENSION_KEY, 'tx_thuecat_tourist_attraction');
