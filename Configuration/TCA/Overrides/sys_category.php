<?php

declare(strict_types=1);

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
