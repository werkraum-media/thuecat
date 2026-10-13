<?php

declare(strict_types=1);

use WerkraumMedia\ThueCat\Extension;

defined('TYPO3') or die();

return (static function (string $tableName) {
    $languagePath = Extension::getLanguagePath() . 'locallang_tca.xlf:' . $tableName;

    return [
        'ctrl' => [
            'label' => 'title',
            'label_alt' => 'length',
            'label_alt_force' => true,
            'tstamp' => 'tstamp',
            'crdate' => 'crdate',
            'delete' => 'deleted',
            'title' => $languagePath,
            'hideTable' => true,
            'enablecolumns' => [
                'disabled' => 'disable',
            ],
            'transOrigPointerField' => 'l18n_parent',
            'transOrigDiffSourceField' => 'l18n_diffsource',
            'languageField' => 'sys_language_uid',
            'translationSource' => 'l18n_source',
        ],
        'columns' => [
            'title' => [
                'label' => $languagePath . '.title',
                'config' => [
                    'type' => 'input',
                ],
            ],
            // Metres, kept as text so the source's precision survives.
            'length' => [
                'label' => $languagePath . '.length',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'input',
                    'size' => 20,
                ],
            ],
            'length_unit' => [
                'label' => $languagePath . '.length_unit',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'input',
                    'size' => 10,
                ],
            ],
            'remote_id' => [
                'label' => $languagePath . '.remote_id',
                'l10n_mode' => 'exclude',
                'config' => [
                    'type' => 'input',
                    'searchable' => false,
                ],
            ],
        ],
        'types' => [
            '0' => [
                'showitem' => 'title, length, length_unit, remote_id',
            ],
        ],
    ];
})('tx_thuecat_trail_way_type');
