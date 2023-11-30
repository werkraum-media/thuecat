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
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Offer;

class OfferTest extends TestCase
{
    #[Test]
    public function canBeCreatedWithLegacyTypeAsString(): void
    {
        $subject = Offer::createFromArray([
            'type' => 'LegacyType',

            'title' => 'Example Title',
            'description' => 'Example Description',
            'prices' => [],
        ]);

        self::assertInstanceOf(
            Offer::class,
            $subject
        );
        self::assertSame('LegacyType', $subject->getType());
    }

    #[Test]
    public function canBeCreatedWithSingleType(): void
    {
        $subject = Offer::createFromArray([
            'types' => ['ParkingFee'],

            'title' => 'Example Title',
            'description' => 'Example Description',
            'prices' => [],
        ]);

        self::assertInstanceOf(
            Offer::class,
            $subject
        );
        self::assertSame('ParkingFee', $subject->getType());
    }

    #[Test]
    public function canBeCreatedWithMultipleTypes(): void
    {
        $subject = Offer::createFromArray([
            'types' => ['Childcare', 'CourseOffer'],

            'title' => 'Example Title',
            'description' => 'Example Description',
            'prices' => [],
        ]);

        self::assertInstanceOf(
            Offer::class,
            $subject
        );
        self::assertSame('CourseOffer', $subject->getType());
    }

    #[Test]
    public function canBeCreatedWithoutType(): void
    {
        $subject = Offer::createFromArray([
            'title' => 'Example Title',
            'description' => 'Example Description',
            'prices' => [],
        ]);

        self::assertInstanceOf(
            Offer::class,
            $subject
        );
        self::assertSame('', $subject->getType());
    }
}
