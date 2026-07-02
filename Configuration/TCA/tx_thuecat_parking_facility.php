<?php

declare(strict_types=1);

use WerkraumMedia\ThueCat\Extension;
use WerkraumMedia\ThueCat\Import\Parser\Entity\OpeningHourSpecificationEntity;

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
                    'readOnly' => true,
                ],
            ],
            'description' => [
                'label' => $languagePath . '.description',
                'l10n_mode' => '',
                'config' => [
                    'type' => 'text',
                    'readOnly' => true,
                ],
            ],
            'sanitation' => [
                'label' => $languagePath . '.sanitation',
                'l10n_mode' => '',
                'config' => [
                    'type' => 'input',
                    'readOnly' => true,
                    'searchable' => false,
                ],
            ],
            'other_service' => [
                'label' => $languagePath . '.other_service',
                'l10n_mode' => '',
                'config' => [
                    'type' => 'input',
                    'readOnly' => true,
                    'searchable' => false,
                ],
            ],
            'traffic_infrastructure' => [
                'label' => $languagePath . '.traffic_infrastructure',
                'l10n_mode' => '',
                'config' => [
                    'type' => 'input',
                    'readOnly' => true,
                    'searchable' => false,
                ],
            ],
            'payment_accepted' => [
                'label' => $languagePath . '.payment_accepted',
                'l10n_mode' => '',
                'config' => [
                    'type' => 'input',
                    'readOnly' => true,
                    'searchable' => false,
                ],
            ],
            'distance_to_public_transport' => [
                'label' => $languagePath . '.distance_to_public_transport',
                'l10n_mode' => '',
                'config' => [
                    'type' => 'input',
                    'readOnly' => true,
                    'searchable' => false,
                ],
            ],
            // @deprecated legacy JSON blob, kept for un-reimported sites; no longer filled. Removed next major.
            'opening_hours' => [
                'label' => $languagePath . '.opening_hours',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'text',
                    'readOnly' => true,
                    'searchable' => false,
                ],
            ],
            // @deprecated legacy JSON blob, kept for un-reimported sites; no longer filled. Removed next major.
            'special_opening_hours' => [
                'label' => $languagePath . '.special_opening_hours',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'text',
                    'readOnly' => true,
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
            'address' => [
                'label' => $languagePath . '.address',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'text',
                    'readOnly' => true,
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
            // @deprecated legacy JSON blob, kept for un-reimported sites; no longer filled. Removed next major.
            'media' => [
                'label' => $languagePath . '.media',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'text',
                    'readOnly' => true,
                    'searchable' => false,
                ],
            ],
            'offers' => [
                'label' => $languagePath . '.offers',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'text',
                    'readOnly' => true,
                    'searchable' => false,
                ],
            ],
            'remote_id' => [
                'label' => $languagePath . '.remote_id',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'input',
                    'readOnly' => true,
                    'searchable' => false,
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
        ],
        'palettes' => [
            'language' => [
                'label' => $languagePath . '.palette.language',
                'showitem' => 'sys_language_uid,l18n_parent',
            ],
        ],
        'types' => [
            '0' => [
                'showitem' => '--palette--;;language, disable, title, description, main_image, media_files, sanitation, other_service, 
                traffic_infrastructure, payment_accepted, distance_to_public_transport,
                opening_hours_inline, special_opening_hours_inline, opening_hours,
                special_opening_hours, offers, address,  media, remote_id, 
                --div--;' . $languagePath . '.tab.relations, town, managed_by',
            ],
        ],
    ];
})(Extension::EXTENSION_KEY, 'tx_thuecat_parking_facility');
