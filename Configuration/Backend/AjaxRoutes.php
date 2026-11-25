<?php

declare(strict_types=1);

use WerkraumMedia\ThueCat\Controller\Backend\ImportController;

return [
    'thuecat_imports_status' => [
        'path' => '/thuecat/imports/status',
        'target' => ImportController::class . '::statusAction',
    ],
];
