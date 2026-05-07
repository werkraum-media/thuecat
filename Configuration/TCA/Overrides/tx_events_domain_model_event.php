<?php

declare(strict_types=1);

// Extend ext:events' Event table with the remote_id column the ThueCat importer
// uses for upsert by JSON-LD @id. Coexists with ext:events' native global_id —
// they index different things (URI vs sha256 of address parts on Location).
defined('TYPO3') || die();

$GLOBALS['TCA']['tx_events_domain_model_event']['columns']['remote_id'] = [
    'label' => 'Remote ID',
    'config' => [
        'type' => 'input',
        'readOnly' => true,
        'searchable' => false,
    ],
];
$GLOBALS['TCA']['tx_events_domain_model_event']['columns']['thuecat_import_configuration'] = [
    'exclude' => true,
    'label' => 'ThueCat Import Configuration',
    'config' => [
        'type' => 'select',
        'renderType' => 'selectSingle',
        'foreign_table' => 'tx_thuecat_import_configuration',
        'items' => [
            [
                'value' => '0',
                'label' => 0,
            ],
        ],
        'readOnly' => true,
    ],
];
$GLOBALS['TCA']['tx_events_domain_model_event']['palettes']['source']['showitem'] = 'source_name, source_url, --linebreak--,import_configuration,thuecat_import_configuration';
$GLOBALS['TCA']['tx_events_domain_model_event']['palettes']['remote_identifier'] = [

    'label' => 'Remote Identifier',
    'showitem' => 'global_id, remote_id',
];
$showitem = $GLOBALS['TCA']['tx_events_domain_model_event']['types'][1]['showitem'] ?? '';
if (is_string($showitem)) {
    $GLOBALS['TCA']['tx_events_domain_model_event']['types'][1]['showitem'] = str_replace(
        'global_id',
        '--palette--;;remote_identifier',
        $showitem
    );
}
