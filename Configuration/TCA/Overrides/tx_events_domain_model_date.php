<?php

declare(strict_types=1);

// Extend ext:events' Date table with the remote_id column the ThueCat importer
// uses for upsert. Date has no native id concept in ext:events (rows are
// recreated each import); for our pipeline we synthesize a deterministic
// remote_id from the parent event id + start time.
defined('TYPO3') || die();

// @phpstan-ignore offsetAccess.nonOffsetAccessible, offsetAccess.nonOffsetAccessible, offsetAccess.nonOffsetAccessible (we put up with TCA Array for now)
$GLOBALS['TCA']['tx_events_domain_model_date']['columns']['remote_id'] = [
    'label' => 'Remote ID',
    'config' => [
        'type' => 'input',
        'readOnly' => true,
        'searchable' => false,
    ],
];
