<?php

declare(strict_types=1);

use WerkraumMedia\Example\TestingDateTimeAspectMiddleware;

return [
    'frontend' => [
        'werkraummedia/example/testing-date-time-aspect' => [
            'target' => TestingDateTimeAspectMiddleware::class,
            'before' => [
                'typo3/cms-frontend/timetracker',
            ],
        ],
    ],
];
