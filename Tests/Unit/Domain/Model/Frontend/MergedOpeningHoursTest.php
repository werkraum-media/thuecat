<?php

declare(strict_types=1);

/*
 * Copyright (C) 2024 Daniel Siepmann <coding@daniel-siepmann.de>
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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\MergedOpeningHour;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\MergedOpeningHours;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\OpeningHours;
use WerkraumMedia\ThueCat\Service\DateBasedFilter;

#[CoversClass(MergedOpeningHour::class)]
#[CoversClass(MergedOpeningHours::class)]
#[CoversClass(OpeningHours::class)]
final class MergedOpeningHoursTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        GeneralUtility::addInstance(DateBasedFilter::class, new class() implements DateBasedFilter {
            public function filterOutPreviousDates(
                array $listToFilter,
                callable $provideDate
            ): array {
                return $listToFilter;
            }
        });
    }

    #[Test]
    public function mergesHoursForSameWeekDay(): void
    {
        $subject = new OpeningHours(json_encode(
            [
                0 => (object)[
                    'daysOfWeek' => [
                        'Tuesday',
                        'Monday',
                    ],
                    'closes' => '12:00:00',
                    'opens' => '08:30:00',
                ],
                1 => (object)[
                    'daysOfWeek' => [
                        'Tuesday',
                        'Monday',
                    ],
                    'closes' => '16:00:00',
                    'opens' => '13:00:00',
                ],
                2 => (object)[
                    'daysOfWeek' => [
                        'Monday',
                    ],
                    'closes' => '18:00:00',
                    'opens' => '17:30:00',
                ],
                3 => (object)[
                    'daysOfWeek' => [
                        'Wednesday',
                    ],
                    'closes' => '13:00:00',
                    'opens' => '08:30:00',
                ],
            ]
        ));

        $result = $subject->getMerged();
        foreach ($result as $openingHour) {
            self::assertInstanceOf(MergedOpeningHour::class, $openingHour);
            \xdebug_break();
        }
    }
}
