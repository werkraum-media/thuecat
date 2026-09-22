<?php

declare(strict_types=1);

defined('TYPO3') || die();

// @phpstan-ignore offsetAccess.nonOffsetAccessible, offsetAccess.nonOffsetAccessible, offsetAccess.nonOffsetAccessible (we put up with TCA Array for now)
$GLOBALS['TCA']['tx_events_domain_model_organizer']['columns']['remote_id'] = [
    'label' => 'Remote ID',
    'config' => [
        'type' => 'input',
        'readOnly' => true,
        'searchable' => false,
    ],
];

// @phpstan-ignore offsetAccess.nonOffsetAccessible, offsetAccess.nonOffsetAccessible, offsetAccess.nonOffsetAccessible (we put up with TCA Array for now)
$showitem = $GLOBALS['TCA']['tx_events_domain_model_organizer']['types'][1]['showitem'] ?? '';
if (is_string($showitem)) {
    // @phpstan-ignore offsetAccess.nonOffsetAccessible, offsetAccess.nonOffsetAccessible, offsetAccess.nonOffsetAccessible
    $GLOBALS['TCA']['tx_events_domain_model_organizer']['types'][1]['showitem'] = str_replace(
        'email',
        'email,remote_id',
        $showitem
    );
}
