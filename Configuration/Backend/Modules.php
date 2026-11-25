<?php

declare(strict_types=1);

use WerkraumMedia\ThueCat\Controller\Backend\ImportController;
use WerkraumMedia\ThueCat\Controller\Backend\RemovalController;

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
        'path' => '/module/thuecat/imports',
        'labels' => 'LLL:EXT:thuecat/Resources/Private/Language/locallang_mod_imports.xlf',
        'routes' => [
            '_default' => [
                'target' => ImportController::class . '::indexAction',
            ],
            'start' => [
                'target' => ImportController::class . '::startAction',
                'methods' => ['POST'],
            ],
            'stop' => [
                'target' => ImportController::class . '::stopAction',
                'methods' => ['POST'],
            ],
        ],
    ],
    // No iconIdentifier: the client delivers the module icons.
    'thuecat_removal' => [
        'parent' => 'thuecat_thuecat',
        'position' => ['after' => 'thuecat_imports'],
        'access' => 'user',
        'path' => '/module/thuecat/removal',
        'labels' => 'LLL:EXT:thuecat/Resources/Private/Language/locallang_mod_removal.xlf',
        'routes' => [
            '_default' => [
                'target' => RemovalController::class . '::indexAction',
            ],
            'confirm' => [
                'target' => RemovalController::class . '::confirmAction',
            ],
            'start' => [
                'target' => RemovalController::class . '::startAction',
                'methods' => ['POST'],
            ],
        ],
    ],
];
