<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Unit\Domain\Model\Frontend;

/*
 * Copyright (C) 2021 Daniel Siepmann <coding@daniel-siepmann.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301, USA.
 */

use Countable;
use Iterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Offers;

class OffersTest extends TestCase
{
    #[Test]
    public function canBeCreated(): void
    {
        $subject = new Offers('{}');

        self::assertInstanceOf(Offers::class, $subject);
    }

    #[Test]
    public function isCountable(): void
    {
        $subject = new Offers('{}');

        self::assertInstanceOf(Countable::class, $subject);
    }

    #[Test]
    public function isIterator(): void
    {
        $subject = new Offers('{}');

        self::assertInstanceOf(Iterator::class, $subject);
    }

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
