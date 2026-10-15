<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

// Extend sys_category with the remote_id column the ThueCat importer uses to
// upsert imported categories. The value is prefixed by the import source field
// (e.g. "type:thuecat:CultureEvent", "keyword:schema:accessible") so categories
// from different import mechanisms never collide, and matching survives editor
// renames of the title. Column DDL is auto-generated from this TCA.
defined('TYPO3') || die();

// @phpstan-ignore offsetAccess.nonOffsetAccessible, offsetAccess.nonOffsetAccessible, offsetAccess.nonOffsetAccessible (we put up with TCA Array for now)
$GLOBALS['TCA']['sys_category']['columns']['remote_id'] = [
    'label' => 'Remote ID',
    'config' => [
        'type' => 'input',
        'readOnly' => true,
        'searchable' => false,
    ],
];

// The URL segment of a category or keyword in routed list filters.
// @phpstan-ignore offsetAccess.nonOffsetAccessible (we put up with TCA Array for now)
$GLOBALS['TCA']['sys_category']['columns']['slug'] = [
    'label' => 'LLL:EXT:thuecat/Resources/Private/Language/locallang_tca.xlf:sys_category.slug',
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
];
ExtensionManagementUtility::addToAllTCAtypes('sys_category', 'slug', '', 'after:title');
