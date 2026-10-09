<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS extension "thuecat".
 *
 * Copyright (C) werkraum-media <https://werkraum-media.de/>
 * Copyright (C) 2021 Daniel Siepmann <coding@daniel-siepmann.de>
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * For the full license text, see the LICENSE file distributed with this
 * extension.
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 */

namespace WerkraumMedia\ThueCat\Tests\Unit\Domain\Model\Frontend;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Offers;

class OffersTest extends TestCase
{
    #[Test]
    #[DataProvider('forCount')]
    public function returnsExpectedCount(string $serialized, int $expected): void
    {
        $subject = new Offers($serialized);

        self::assertCount($expected, $subject);
    }

    public static function forCount(): array
    {
        return [
            'zero' => [
                'serialized' => '{}',
                'expected' => 0,
            ],
            'one' => [
                'serialized' => json_encode([
                    [
                        'title' => '',
                        'description' => '',
                        'prices' => [
                            [
                                'title' => '',
                                'description' => '',
                                'price' => 5.0,
                                'currency' => '',
                                'rule' => '',
                            ],
                        ],
                    ],
                ]),
                'expected' => 1,
            ],
            'five' => [
                'serialized' => json_encode([
                    [
                        'title' => '',
                        'description' => '',
                        'prices' => [
                            [
                                'title' => '',
                                'description' => '',
                                'price' => 5.0,
                                'currency' => '',
                                'rule' => '',
                            ],
                        ],
                    ],
                    [
                        'title' => '',
                        'description' => '',
                        'prices' => [
                            [
                                'title' => '',
                                'description' => '',
                                'price' => 5.0,
                                'currency' => '',
                                'rule' => '',
                            ],
                        ],
                    ],
                    [
                        'title' => '',
                        'description' => '',
                        'prices' => [
                            [
                                'title' => '',
                                'description' => '',
                                'price' => 5.0,
                                'currency' => '',
                                'rule' => '',
                            ],
                        ],
                    ],
                    [
                        'title' => '',
                        'description' => '',
                        'prices' => [
                            [
                                'title' => '',
                                'description' => '',
                                'price' => 5.0,
                                'currency' => '',
                                'rule' => '',
                            ],
                        ],
                    ],
                    [
                        'title' => '',
                        'description' => '',
                        'prices' => [
                            [
                                'title' => '',
                                'description' => '',
                                'price' => 5.0,
                                'currency' => '',
                                'rule' => '',
                            ],
                        ],
                    ],
                ]),
                'expected' => 5,
            ],
        ];
    }
}
