<?php

declare(strict_types=1);

return  [
    'tx_thuecat_tourist_attraction' => [
        0 => [
            'uid' => '1',
            'pid' => '3',
            'title' => 'Attraktion mit Preisen',
            'offers' =>  [
                0 => (object)[
                    'prices' => [
                        0 => (object)[
                            'currency' => 'EUR',
                            'description' => '',
                            'price' => 8,
                            'rule' => 'PerGroup',
                            'title' => 'Schulklassen',
                        ],
                        1 => (object)[
                            'currency' => 'EUR',
                            'description' => '',
                            'price' => 8,
                            'rule' => 'PerPerson',
                            'title' => 'Erwachsene',
                        ],
                        2 => (object)[
                            'currency' => 'EUR',
                            'description' => '',
                            'price' => 5,
                            'rule' => 'PerPerson',
                            'title' => 'Familienkarte B',
                        ],
                        3 => (object)[
                            'currency' => 'EUR',
                            'description' => '',
                            'price' => 5,
                            'rule' => 'PerPerson',
                            'title' => 'Familienkarte A',
                        ],
                    ],
                    'description' => '',
                    'title' => 'Führungen',
                    'type' => 'GuidedTourOffer',
                ],
            ],
        ],
    ],
];
