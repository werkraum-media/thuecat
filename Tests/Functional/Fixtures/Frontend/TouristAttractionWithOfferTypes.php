<?php

declare(strict_types=1);

return  [
    'tx_thuecat_tourist_attraction' => [
        0 => [
            'uid' => '1',
            'pid' => '3',
            'title' => 'Attraktion mit Angebotstypen',
            'offers' =>  [
                0 => (object)[
                    'description' => '',
                    'prices' => [
                        0 => (object)[
                            'currency' => 'EUR',
                            'description' => '',
                            'price' => 8,
                            'rule' => 'PerGroup',
                            'title' => 'Schulklassen',
                        ],
                    ],
                    'title' => 'Führungen',
                    'type' => 'GuidedTourOffer',
                ],
                1 => (object)[
                    'description' => '',
                    'prices' => [
                        0 => (object)[
                            'currency' => 'EUR',
                            'description' => '',
                            'price' => 8,
                            'rule' => 'PerGroup',
                            'title' => 'Schulklassen',
                        ],
                    ],
                    'title' => 'Verkostung',
                    'type' => 'Tasting',
                ],
                2 => (object)[
                    'description' => '',
                    'prices' => [
                        0 => (object)[
                            'currency' => 'EUR',
                            'description' => '',
                            'price' => 8,
                            'rule' => 'PerGroup',
                            'title' => 'Schulklassen',
                        ],
                    ],
                    'title' => 'Eintritt 1',
                    'type' => 'EntryOffer',
                ],
                3 => (object)[
                    'description' => '',
                    'prices' => [
                        0 => (object)[
                            'currency' => 'EUR',
                            'description' => '',
                            'price' => 8,
                            'rule' => 'PerGroup',
                            'title' => 'Schulklassen',
                        ],
                    ],
                    'title' => 'Eintritt 2',
                    'type' => 'EntryOffer',
                ],
                4 => (object)[
                    'description' => '',
                    'prices' => [
                        0 => (object)[
                            'currency' => 'EUR',
                            'description' => '',
                            'price' => 8,
                            'rule' => 'PerGroup',
                            'title' => 'Schulklassen',
                        ],
                    ],
                    'title' => 'Parkgebühr',
                    'type' => 'ParkingFee',
                ],
            ],
        ],
    ],
];
