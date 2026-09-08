<?php

declare(strict_types=1);

use WerkraumMedia\ThueCat\Controller\Backend\ImportController;

return [
    'thuecat_thuecat' => [
        'iconIdentifier' => 'thuecat_modules',
        'position' => ['after' => 'content'],
        'labels' => 'LLL:EXT:thuecat/Resources/Private/Language/locallang_mod.xlf',
        'extensionName' => 'Thuecat',
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
            ],
        ],
    ],
];
