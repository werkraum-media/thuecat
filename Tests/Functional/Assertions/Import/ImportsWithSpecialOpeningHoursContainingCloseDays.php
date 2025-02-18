<?php

declare(strict_types=1);

return [
    'tx_thuecat_tourist_attraction' => [
        0 => [
            'uid' => '1',
            'pid' => '10',
            'sys_language_uid' => '0',
            'l18n_parent' => '0',
            'l10n_source' => '0',
            'remote_id' => 'https://thuecat.org/resources/attraction-with-close-days',
            'title' => 'Contains specialOpeningHoursSpecification with close days',
            'special_opening_hours' => '[{"opens":"09:30:00","closes":"18:00:00","from":{"date":"2024-09-21 00:00:00.000000","timezone_type":3,"timezone":"UTC"},"through":{"date":"2024-09-21 00:00:00.000000","timezone_type":3,"timezone":"UTC"},"daysOfWeek":["Saturday"]}]',
            'closing_days' => '[{"from":{"date":"2024-09-20 00:00:00.000000","timezone_type":3,"timezone":"UTC"},"through":{"date":"2024-09-22 00:00:00.000000","timezone_type":3,"timezone":"UTC"},"daysOfWeek":["Friday"]},{"from":{"date":"2024-09-20 00:00:00.000000","timezone_type":3,"timezone":"UTC"},"through":{"date":"2024-09-22 00:00:00.000000","timezone_type":3,"timezone":"UTC"},"daysOfWeek":["Sunday"]}]',
        ],
    ],
];
