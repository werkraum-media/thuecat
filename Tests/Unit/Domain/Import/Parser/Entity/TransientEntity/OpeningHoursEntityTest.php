<?php

declare(strict_types=1);

/*
 * Copyright (C) 2024 werkraum-media
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

namespace WerkraumMedia\ThueCat\Tests\Unit\Domain\Import\Parser\Entity\TransientEntity;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WerkraumMedia\ThueCat\Domain\Import\Parser\Entity\TransientEntity\OpeningHoursEntity;

class OpeningHoursEntityTest extends TestCase
{
    #[Test]
    public function extractsOpensClosesAndValidityFromTypedValues(): void
    {
        $node = [
            'schema:opens' => ['@type' => 'schema:Time', '@value' => '10:00:00'],
            'schema:closes' => ['@type' => 'schema:Time', '@value' => '18:00:00'],
            'schema:validFrom' => ['@type' => 'schema:Date', '@value' => '2021-03-01'],
            'schema:validThrough' => ['@type' => 'schema:Date', '@value' => '2021-12-31'],
            'schema:dayOfWeek' => [
                ['@type' => 'schema:DayOfWeek', '@value' => 'schema:Saturday'],
                ['@type' => 'schema:DayOfWeek', '@value' => 'schema:Sunday'],
            ],
        ];

        $entity = new OpeningHoursEntity();
        $entity->configure($node);

        self::assertSame([
            'opens' => '10:00:00',
            'closes' => '18:00:00',
            'daysOfWeek' => ['Saturday', 'Sunday'],
            'from' => ['date' => '2021-03-01'],
            'through' => ['date' => '2021-12-31'],
        ], $entity->toArray());
    }

    #[Test]
    public function acceptsSingleDayOfWeekObjectAsWellAsList(): void
    {
        // opening-hours-to-filter.json fixture carries one spec with a single
        // schema:dayOfWeek object (not wrapped in a list). Both shapes must
        // produce a flat list of bare day names.
        $node = [
            'schema:opens' => ['@type' => 'schema:Time', '@value' => '13:00:00'],
            'schema:closes' => ['@type' => 'schema:Time', '@value' => '17:00:00'],
            'schema:dayOfWeek' => ['@type' => 'schema:DayOfWeek', '@value' => 'schema:Sunday'],
        ];

        $entity = new OpeningHoursEntity();
        $entity->configure($node);

        self::assertSame(['Sunday'], $entity->toArray()['daysOfWeek']);
    }

    #[Test]
    public function omitsFromAndThroughWhenAbsent(): void
    {
        // Legacy OpeningHour::createFromArray uses isset() to decide whether to
        // build a DateTime — emitting missing keys (rather than null stubs) keeps
        // that contract intact.
        $node = [
            'schema:opens' => ['@type' => 'schema:Time', '@value' => '09:00:00'],
            'schema:closes' => ['@type' => 'schema:Time', '@value' => '17:00:00'],
            'schema:dayOfWeek' => ['@type' => 'schema:DayOfWeek', '@value' => 'schema:Monday'],
        ];

        $entity = new OpeningHoursEntity();
        $entity->configure($node);

        $result = $entity->toArray();
        self::assertArrayNotHasKey('from', $result);
        self::assertArrayNotHasKey('through', $result);
    }
}