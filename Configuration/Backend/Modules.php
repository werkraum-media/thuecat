<?php

declare(strict_types=1);

use WerkraumMedia\ThueCat\Controller\Backend\ConfigurationController;
use WerkraumMedia\ThueCat\Controller\Backend\ImportController;
use WerkraumMedia\ThueCat\Extension;

return [
    'thuecat_thuecat' => [
        'icon' => Extension::getIconPath() . 'ModuleGroup.svg',
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
        'icon' => Extension::getIconPath() . 'ModuleConfigurations.svg',
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
        'icon' => Extension::getIconPath() . 'ModuleImports.svg',
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
