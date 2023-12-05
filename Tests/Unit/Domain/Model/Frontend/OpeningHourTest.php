<?php

declare(strict_types=1);

/*
 * Copyright (C) 2022 Daniel Siepmann <coding@daniel-siepmann.de>
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

namespace WerkraumMedia\ThueCat\Tests\Unit\Domain\Model\Frontend;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\OpeningHour;

class OpeningHourTest extends TestCase
{
    #[Test]
    public function returnsReducedOpens(): void
    {
        $subject = OpeningHour::createFromArray([
            'opens' => '14:13:12',
        ]);

        self::assertSame(
            '14:13',
            $subject->getOpens()
        );
    }

    #[Test]
    public function returnsOpensForEmptyString(): void
    {
        $subject = OpeningHour::createFromArray([]);

        self::assertSame(
            '',
            $subject->getOpens()
        );
    }

    #[Test]
    public function returnsReducedCloses(): void
    {
        $subject = OpeningHour::createFromArray([
            'closes' => '14:13:12',
        ]);

        self::assertSame(
            '14:13',
            $subject->getCloses()
        );
    }

    #[Test]
    public function returnsClosesForEmptyString(): void
    {
        $subject = OpeningHour::createFromArray([]);

        self::assertSame(
            '',
            $subject->getCloses()
        );
    }

    #[Test]
    public function returnsThatThisIsOnlyASingleDay(): void
    {
        $subject = OpeningHour::createFromArray([
            'from' => [
                'date' => '2022-11-28 00:00:00.000000',
                'timezone_type' => 3,
                'timezone' => 'UTC',
            ],
            'through' => [
                'date' => '2022-11-28 00:00:00.000000',
                'timezone_type' => 3,
                'timezone' => 'UTC',
            ],
        ]);

        self::assertTrue($subject->isSingleDay());
    }

    #[Test]
    public function returnsThatThisIsATimeframe(): void
    {
        $subject = OpeningHour::createFromArray([
            'from' => [
                'date' => '2022-11-28 00:00:00.000000',
                'timezone_type' => 3,
                'timezone' => 'UTC',
            ],
            'through' => [
                'date' => '2022-11-29 00:00:00.000000',
                'timezone_type' => 3,
                'timezone' => 'UTC',
            ],
        ]);

        self::assertFalse($subject->isSingleDay());
    }
}
