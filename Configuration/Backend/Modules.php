<?php

declare(strict_types=1);

use WerkraumMedia\ThueCat\Controller\Backend\ConfigurationController;
use WerkraumMedia\ThueCat\Controller\Backend\ImportController;

return [
    'thuecat_thuecat' => [
        'iconIdentifier' => 'thuecat_modules',
        'position' => [
            'after' => 'web',
            'before' => 'file',
        ],
        'labels' => 'LLL:EXT:thuecat/Resources/Private/Language/locallang_mod.xlf',
        'extensionName' => 'Thuecat',
    ],
    'thuecat_configurations' => [
        'parent' => 'thuecat_thuecat',
        'access' => 'user',
        'iconIdentifier' => 'thuecat_module_configurations',
        'labels' => 'LLL:EXT:thuecat/Resources/Private/Language/locallang_mod_configurations.xlf',
        'extensionName' => 'Thuecat',
        'controllerActions' => [
            ConfigurationController::class => [
                'index',
            ],
            ImportController::class => [
                'import',
            ],
        ],
    ],
    'thuecat_imports' => [
        'parent' => 'thuecat_thuecat',
        'access' => 'user',
        'iconIdentifier' => 'thuecat_module_imports',
        'labels' => 'LLL:EXT:thuecat/Resources/Private/Language/locallang_mod_imports.xlf',
        'extensionName' => 'Thuecat',
        'controllerActions' => [
            ImportController::class => [
                'index',
                'import',
            ],
        ],
    ],
];
