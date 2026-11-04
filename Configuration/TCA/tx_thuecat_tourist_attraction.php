<?php

declare(strict_types=1);

use WerkraumMedia\ThueCat\Extension;
use WerkraumMedia\ThueCat\Import\Parser\Entity\OpeningHourSpecificationEntity;

defined('TYPO3') or die();

return (static function (string $extensionKey, string $tableName) {
    $languagePath = Extension::getLanguagePath() . 'locallang_tca.xlf:' . $tableName;
    // Booleans keep "not delivered" apart from "no".
    $booleanItems = [
        ['label' => '', 'value' => ''],
        ['label' => $languagePath . '.boolean.true', 'value' => 'true'],
        ['label' => $languagePath . '.boolean.false', 'value' => 'false'],
    ];
    $attractionShowitem = 'type, --palette--;;language, title, slug, description, main_image, media_files, slogan, start_of_construction, sanitation, 
                other_service, museum_service, architectural_style, traffic_infrastructure, payment_accepted, digital_offer, 
                photography, pets_allowed, is_accessible_for_free, public_access, available_languages, distance_to_public_transport, 
                opening_hours_inline, special_opening_hours_inline, offers, accessibility_specification, address_inline, url,
                remote_id, --div--;' . $languagePath . '.tab.relations, town, managed_by,
                parking_facility_near_by, contained_in_organisation, contained_in_attraction,
                contained_in_tourist_information, contained_in_parking_facility, contained_in_trail,
                --div--;' . $languagePath . '.tab.events, hosts_events,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories, categories, keywords,
                --div--;' . $languagePath . '.tab.editorial_additions, editorial_images';

    return [
        'ctrl' => [
            'label' => 'title',
            'type' => 'type',
            'iconfile' => Extension::getIconPath() . $tableName . '.svg',
            'sortby' => 'sorting',
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

            // Written by the import from the node's @type on every run.
            'type' => [
                'label' => $languagePath . '.type',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'items' => [
                        [
                            'label' => $languagePath . '.type.attraction',
                            'value' => 'attraction',
                        ],
                        [
                            'label' => $languagePath . '.type.meeting_venue',
                            'value' => 'meeting_venue',
                        ],
                    ],
                    'default' => 'attraction',
                ],
            ],
            'venue_type' => [
                'label' => $languagePath . '.venue_type',
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
                            'label' => $languagePath . '.venue_type.meeting_congress_hotel',
                            'value' => 'meeting_congress_hotel',
                        ],
                        [
                            'label' => $languagePath . '.venue_type.meeting_congress',
                            'value' => 'meeting_congress',
                        ],
                    ],
                ],
            ],

            // Venue properties: imported for the meeting_venue type only, shown on its tabs.
            'meeting_facilities' => [
                'label' => $languagePath . '.meeting_facilities',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'input',
                    'searchable' => false,
                ],
            ],
            'gastro' => [
                'label' => $languagePath . '.gastro',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'input',
                    'searchable' => false,
                ],
            ],
            'serves_cuisine' => [
                'label' => $languagePath . '.serves_cuisine',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'input',
                    'searchable' => false,
                ],
            ],
            'entrance' => [
                'label' => $languagePath . '.entrance',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'input',
                    'searchable' => false,
                ],
            ],
            'guided_tour' => [
                'label' => $languagePath . '.guided_tour',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'input',
                    'searchable' => false,
                ],
            ],
            'monument' => [
                'label' => $languagePath . '.monument',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'input',
                    'searchable' => false,
                ],
            ],
            'ensemble' => [
                'label' => $languagePath . '.ensemble',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'input',
                    'searchable' => false,
                ],
            ],
            'exhibition_objects' => [
                'label' => $languagePath . '.exhibition_objects',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'input',
                    'searchable' => false,
                ],
            ],
            'sports_equipment' => [
                'label' => $languagePath . '.sports_equipment',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'input',
                    'searchable' => false,
                ],
            ],
            'amenity_features' => [
                'label' => $languagePath . '.amenity_features',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'input',
                    'searchable' => false,
                ],
            ],
            'price_range' => [
                'label' => $languagePath . '.price_range',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'input',
                    'searchable' => false,
                ],
            ],
            'star_rating' => [
                'label' => $languagePath . '.star_rating',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'input',
                    'searchable' => false,
                ],
            ],
            'checkin_time' => [
                'label' => $languagePath . '.checkin_time',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'input',
                    'searchable' => false,
                ],
            ],
            'checkout_time' => [
                'label' => $languagePath . '.checkout_time',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'input',
                    'searchable' => false,
                ],
            ],
            'smoking_allowed' => [
                'label' => $languagePath . '.smoking_allowed',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'items' => $booleanItems,
                ],
            ],
            'is_ruin' => [
                'label' => $languagePath . '.is_ruin',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'items' => $booleanItems,
                ],
            ],
            'open_air_museum' => [
                'label' => $languagePath . '.open_air_museum',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'items' => $booleanItems,
                ],
            ],
            'bookable' => [
                'label' => $languagePath . '.bookable',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'items' => $booleanItems,
                ],
            ],
            'has_drive_through_service' => [
                'label' => $languagePath . '.has_drive_through_service',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'items' => $booleanItems,
                ],
            ],
            'beer_garden' => [
                'label' => $languagePath . '.beer_garden',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'items' => $booleanItems,
                ],
            ],
            'catering' => [
                'label' => $languagePath . '.catering',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'items' => $booleanItems,
                ],
            ],
            'delivery_service' => [
                'label' => $languagePath . '.delivery_service',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'items' => $booleanItems,
                ],
            ],
            'hot_cuisine_throughout' => [
                'label' => $languagePath . '.hot_cuisine_throughout',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'items' => $booleanItems,
                ],
            ],
            'late_kitchen' => [
                'label' => $languagePath . '.late_kitchen',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'items' => $booleanItems,
                ],
            ],
            'self_service' => [
                'label' => $languagePath . '.self_service',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'items' => $booleanItems,
                ],
            ],
            'take_away' => [
                'label' => $languagePath . '.take_away',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'items' => $booleanItems,
                ],
            ],
            'accepts_reservations' => [
                'label' => $languagePath . '.accepts_reservations',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'items' => $booleanItems,
                ],
            ],
            'maximum_attendee_capacity' => [
                'label' => $languagePath . '.maximum_attendee_capacity',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'number',
                ],
            ],
            'number_of_rooms' => [
                'label' => $languagePath . '.number_of_rooms',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'number',
                ],
            ],
            'single_rooms' => [
                'label' => $languagePath . '.single_rooms',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'number',
                ],
            ],
            'double_rooms' => [
                'label' => $languagePath . '.double_rooms',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'number',
                ],
            ],
            'twin_rooms' => [
                'label' => $languagePath . '.twin_rooms',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'number',
                ],
            ],
            'suites' => [
                'label' => $languagePath . '.suites',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'number',
                ],
            ],
            'seats_inside' => [
                'label' => $languagePath . '.seats_inside',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'number',
                ],
            ],
            'seats_outside' => [
                'label' => $languagePath . '.seats_outside',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'number',
                ],
            ],
            'distance_airport' => [
                'label' => $languagePath . '.distance_airport',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'number',
                ],
            ],
            'distance_city_center' => [
                'label' => $languagePath . '.distance_city_center',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'number',
                ],
            ],
            'distance_fair' => [
                'label' => $languagePath . '.distance_fair',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'number',
                ],
            ],
            'distance_freeway' => [
                'label' => $languagePath . '.distance_freeway',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'number',
                ],
            ],
            'distance_ice_station' => [
                'label' => $languagePath . '.distance_ice_station',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'number',
                ],
            ],
            'size_of_area' => [
                'label' => $languagePath . '.size_of_area',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'number',
                ],
            ],
            'typical_age_range' => [
                'label' => $languagePath . '.typical_age_range',
                'l10n_mode' => '',
                'config' => [
                    'type' => 'text',
                ],
            ],
            'end_of_construction' => [
                'label' => $languagePath . '.end_of_construction',
                'l10n_mode' => '',
                'config' => [
                    'type' => 'text',
                ],
            ],
            'certifications' => [
                'label' => $languagePath . '.certifications',
                'l10n_mode' => '',
                'config' => [
                    'type' => 'text',
                ],
            ],
            'description_culture_portal' => [
                'label' => $languagePath . '.description_culture_portal',
                'l10n_mode' => '',
                'config' => [
                    'type' => 'text',
                ],
            ],
            'description_lodging_meeting' => [
                'label' => $languagePath . '.description_lodging_meeting',
                'l10n_mode' => '',
                'config' => [
                    'type' => 'text',
                ],
            ],
            'has_map' => [
                'label' => $languagePath . '.has_map',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'text',
                    'searchable' => false,
                ],
            ],
            'tour_booking_page' => [
                'label' => $languagePath . '.tour_booking_page',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'text',
                    'searchable' => false,
                ],
            ],
            'identifier' => [
                'label' => $languagePath . '.identifier',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'text',
                    'searchable' => false,
                ],
            ],
            'same_as' => [
                'label' => $languagePath . '.same_as',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'text',
                    'searchable' => false,
                ],
            ],
            'subject_of' => [
                'label' => $languagePath . '.subject_of',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'text',
                    'searchable' => false,
                ],
            ],
            'tourist_types' => [
                'label' => $languagePath . '.tourist_types',
                'l10n_mode' => '',
                'config' => [
                    'type' => 'text',
                ],
            ],
            'who_was_here' => [
                'label' => $languagePath . '.who_was_here',
                'l10n_mode' => '',
                'config' => [
                    'type' => 'text',
                ],
            ],
            'architects' => [
                'label' => $languagePath . '.architects',
                'l10n_mode' => '',
                'config' => [
                    'type' => 'text',
                ],
            ],
            'managed_objects' => [
                'label' => $languagePath . '.managed_objects',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectMultipleSideBySide',
                    'foreign_table' => 'tx_thuecat_tourist_attraction',
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
            'subject_of_media' => [
                'label' => $languagePath . '.subject_of_media',
                'config' => [
                    'type' => 'file',
                    'allowed' => 'common-image-types',
                    'behaviour' => [
                        'allowLanguageSynchronization' => true,
                    ],
                ],
            ],

            // Both fields are bounded by the anchor their import writes beneath,
            // so neither offers the other's tree. Core resolves ###SITE:### in
            // treeConfig.startingPoints per site, which is where the anchors are
            // configured; an unset anchor leaves the field offering nothing.
            'categories' => [
                'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_category.categories',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'category',
                    'treeConfig' => [
                        'startingPoints' => '###THUECAT_ANCHOR:tx_thuecat_tourist_attraction:category###',
                    ],
                ],
            ],

            // Own property, own anchor: never mixed with the categories above.
            'keywords' => [
                'label' => $languagePath . '.keywords',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'category',
                    'treeConfig' => [
                        'startingPoints' => '###THUECAT_ANCHOR:tx_thuecat_tourist_attraction:keywords###',
                    ],
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
            'slug' => [
                'label' => $languagePath . '.slug',
                'config' => [
                    'type' => 'slug',
                    'generatorOptions' => [
                        'fields' => ['title'],
                        'prefixParentPageSlug' => false,
                        'replacements' => ['/' => '-'],
                    ],
                    'fallbackCharacter' => '-',
                    'eval' => 'uniqueInSite',
                    'prependSlash' => false,
                ],
            ],
            'description' => [
                'label' => $languagePath . '.description',
                'l10n_mode' => '',
                'config' => [
                    'type' => 'text',
                ],
            ],
            'slogan' => [
                'label' => $languagePath . '.slogan',
                'l10n_mode' => '',
                'config' => [
                    'type' => 'input',
                    'searchable' => false,
                ],
            ],
            'start_of_construction' => [
                'label' => $languagePath . '.start_of_construction',
                'l10n_mode' => '',
                'config' => [
                    'type' => 'input',
                    'searchable' => false,
                ],
            ],
            'sanitation' => [
                'label' => $languagePath . '.sanitation',
                'l10n_mode' => '',
                'config' => [
                    'type' => 'input',
                    'searchable' => false,
                ],
            ],
            'other_service' => [
                'label' => $languagePath . '.other_service',
                'l10n_mode' => '',
                'config' => [
                    'type' => 'input',
                    'searchable' => false,
                ],
            ],
            'museum_service' => [
                'label' => $languagePath . '.museum_service',
                'l10n_mode' => '',
                'config' => [
                    'type' => 'input',
                    'searchable' => false,
                ],
            ],
            'architectural_style' => [
                'label' => $languagePath . '.architectural_style',
                'l10n_mode' => '',
                'config' => [
                    'type' => 'input',
                    'searchable' => false,
                ],
            ],
            'traffic_infrastructure' => [
                'label' => $languagePath . '.traffic_infrastructure',
                'l10n_mode' => '',
                'config' => [
                    'type' => 'input',
                    'searchable' => false,
                ],
            ],
            'payment_accepted' => [
                'label' => $languagePath . '.payment_accepted',
                'l10n_mode' => '',
                'config' => [
                    'type' => 'input',
                    'searchable' => false,
                ],
            ],
            'digital_offer' => [
                'label' => $languagePath . '.digital_offer',
                'l10n_mode' => '',
                'config' => [
                    'type' => 'input',
                    'searchable' => false,
                ],
            ],
            'photography' => [
                'label' => $languagePath . '.photography',
                'l10n_mode' => '',
                'config' => [
                    'type' => 'input',
                    'searchable' => false,
                ],
            ],
            'pets_allowed' => [
                'label' => $languagePath . '.pets_allowed',
                'l10n_mode' => '',
                'config' => [
                    'type' => 'input',
                    'searchable' => false,
                ],
            ],
            'is_accessible_for_free' => [
                'label' => $languagePath . '.is_accessible_for_free',
                'l10n_mode' => '',
                'config' => [
                    'type' => 'input',
                    'searchable' => false,
                ],
            ],
            'public_access' => [
                'label' => $languagePath . '.public_access',
                'l10n_mode' => '',
                'config' => [
                    'type' => 'input',
                    'searchable' => false,
                ],
            ],
            'available_languages' => [
                'label' => $languagePath . '.available_languages',
                'l10n_mode' => '',
                'config' => [
                    'type' => 'input',
                    'searchable' => false,
                ],
            ],
            'distance_to_public_transport' => [
                'label' => $languagePath . '.distance_to_public_transport',
                'l10n_mode' => '',
                'config' => [
                    'type' => 'input',
                    'searchable' => false,
                ],
            ],
            'opening_hours_inline' => [
                'label' => $languagePath . '.opening_hours_inline',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'inline',
                    'foreign_table' => 'tx_thuecat_opening_hours',
                    'foreign_field' => 'parentid',
                    'foreign_table_field' => 'parenttable',
                    'foreign_match_fields' => [
                        'specification_type' => OpeningHourSpecificationEntity::TYPE_REGULAR,
                    ],
                    'foreign_default_sortby' => 'valid_from, day_of_week, opens',
                ],
            ],
            'special_opening_hours_inline' => [
                'label' => $languagePath . '.special_opening_hours_inline',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'inline',
                    'foreign_table' => 'tx_thuecat_opening_hours',
                    'foreign_field' => 'parentid',
                    'foreign_table_field' => 'parenttable',
                    'foreign_match_fields' => [
                        'specification_type' => OpeningHourSpecificationEntity::TYPE_SPECIAL,
                    ],
                    'foreign_default_sortby' => 'valid_from, day_of_week, opens',
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
            'parking_facilities_inline' => [
                'label' => $languagePath . '.parking_facilities_inline',
                'config' => [
                    'type' => 'inline',
                    'foreign_table' => 'tx_thuecat_parking_facility',
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
            'meeting_rooms' => [
                'label' => $languagePath . '.meeting_rooms',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectMultipleSideBySide',
                    'foreign_table' => 'tx_thuecat_meeting_room',
                    'MM' => 'tx_thuecat_tourist_attraction_meeting_rooms_mm',
                    'readOnly' => true,
                ],
            ],
            'hosts_events' => [
                'label' => $languagePath . '.hosts_events',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectMultipleSideBySide',
                    'foreign_table' => 'tx_events_domain_model_event',
                    'MM' => 'tx_thuecat_tourist_attraction_hosts_events_mm',
                    'readOnly' => true,
                ],
            ],
            'url' => [
                'label' => $languagePath . '.url',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'input',
                    'searchable' => false,
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
            'offers' => [
                'label' => $languagePath . '.offers',
                'config' => [
                    'type' => 'text',
                    'searchable' => false,
                ],
            ],
            'accessibility_specification' => [
                'label' => $languagePath . '.accessibility_specification',
                'config' => [
                    'type' => 'text',
                    'searchable' => false,
                ],
            ],
            'remote_id' => [
                'label' => $languagePath . '.remote_id',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'input',
                ],
            ],
            'town' => [
                'label' => $languagePath . '.town',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectMultipleSideBySide',
                    'foreign_table' => 'tx_thuecat_town',
                ],
            ],
            'contained_in_organisation' => [
                'label' => $languagePath . '.contained_in_organisation',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectMultipleSideBySide',
                    'foreign_table' => 'tx_thuecat_organisation',
                ],
            ],
            // Upstream nests places within places. One relation per target
            // table, not one field spanning them: Extbase resolves a relation
            // through a single concrete class, so "any place" cannot be one
            // property. A place table with no field here lands in the
            // unrelatable report instead of being silently dropped.
            'contained_in_attraction' => [
                'label' => $languagePath . '.contained_in_attraction',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectMultipleSideBySide',
                    'foreign_table' => 'tx_thuecat_tourist_attraction',
                ],
            ],
            'contained_in_place' => [
                'label' => $languagePath . '.contained_in_place',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectMultipleSideBySide',
                    'foreign_table' => 'tx_thuecat_place',
                ],
            ],
            'contained_in_tourist_information' => [
                'label' => $languagePath . '.contained_in_tourist_information',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectMultipleSideBySide',
                    'foreign_table' => 'tx_thuecat_tourist_information',
                ],
            ],
            'contained_in_parking_facility' => [
                'label' => $languagePath . '.contained_in_parking_facility',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectMultipleSideBySide',
                    'foreign_table' => 'tx_thuecat_parking_facility',
                    'foreign_table_where' => 'AND {#tx_thuecat_parking_facility}.{#parentid} = 0',
                ],
            ],
            'contained_in_trail' => [
                'label' => $languagePath . '.contained_in_trail',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectMultipleSideBySide',
                    'foreign_table' => 'tx_thuecat_trail',
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
            'operated_by' => [
                'label' => $languagePath . '.operated_by',
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
            'geo_touches_trail' => [
                'label' => $languagePath . '.geo_touches_trail',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectMultipleSideBySide',
                    'foreign_table' => 'tx_thuecat_trail',
                ],
            ],
            'parking_facility_near_by' => [
                'label' => $languagePath . '.parking_facility_near_by',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectMultipleSideBySide',
                    'foreign_table' => 'tx_thuecat_parking_facility',
                    'foreign_table_where' => 'AND {#tx_thuecat_parking_facility}.{#parentid} = 0',
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
            'attraction' => [
                'showitem' => $attractionShowitem,
            ],
            'meeting_venue' => [
                'showitem' => $attractionShowitem . ',
                --div--;' . $languagePath . '.tab.meeting, venue_type, meeting_rooms, meeting_facilities, maximum_attendee_capacity,
                distance_airport, distance_city_center, distance_fair, distance_freeway, distance_ice_station,
                description_lodging_meeting,
                --div--;' . $languagePath . '.tab.meeting_venue, checkin_time, checkout_time, number_of_rooms,
                single_rooms, double_rooms, twin_rooms, suites, star_rating, amenity_features, price_range,
                gastro, serves_cuisine, seats_inside, seats_outside, beer_garden, catering, delivery_service,
                hot_cuisine_throughout, late_kitchen, self_service, take_away, accepts_reservations,
                has_drive_through_service, entrance, guided_tour, smoking_allowed, typical_age_range,
                tour_booking_page, bookable, sports_equipment, ensemble, exhibition_objects, open_air_museum,
                monument, is_ruin, end_of_construction, size_of_area, certifications, description_culture_portal,
                has_map, same_as, identifier, logo, subject_of_media, subject_of, tourist_types, who_was_here,
                architects, managed_objects, operated_by, geo_touches_trail, parking_facilities_inline',
            ],
        ],
    ];
})(Extension::EXTENSION_KEY, 'tx_thuecat_tourist_attraction');
