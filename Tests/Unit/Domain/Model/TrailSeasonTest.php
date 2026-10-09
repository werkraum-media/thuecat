<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS extension "thuecat".
 *
 * Copyright (C) werkraum-media <https://werkraum-media.de/>
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

namespace WerkraumMedia\ThueCat\Tests\Unit\Domain\Model;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WerkraumMedia\ThueCat\Domain\Model\TrailSeason;

class TrailSeasonTest extends TestCase
{
    /**
     * Stored values depend on these bits, so a reordered or inserted case
     * silently rewrites the meaning of every persisted mask.
     */
    #[Test]
    public function bitsAreTheStoredContract(): void
    {
        self::assertSame(
            [
                'Jan' => 1,
                'Feb' => 2,
                'Mar' => 4,
                'Apr' => 8,
                'May' => 16,
                'Jun' => 32,
                'Jul' => 64,
                'Aug' => 128,
                'Sep' => 256,
                'Oct' => 512,
                'Nov' => 1024,
                'Dec' => 2048,
                'AllYearRound' => 4096,
            ],
            TrailSeason::bits()
        );
    }

    #[Test]
    public function resolvesAMaskToItsMembersInBitOrder(): void
    {
        // 1020 = Mar..Oct
        self::assertSame(
            ['Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'],
            array_map(
                static fn (TrailSeason $case): string => $case->value,
                TrailSeason::fromMask(1020)
            )
        );
    }

    #[Test]
    public function resolvesAnEmptyMaskToNothing(): void
    {
        self::assertSame([], TrailSeason::fromMask(0));
    }

    // AllYearRound is a bit like any other, so upstream may set it alongside months.
    #[Test]
    public function keepsAllYearRoundAlongsideIndividualMonths(): void
    {
        $mask = TrailSeason::Jan->bit() | TrailSeason::AllYearRound->bit();

        self::assertSame(
            ['Jan', 'AllYearRound'],
            array_map(
                static fn (TrailSeason $case): string => $case->value,
                TrailSeason::fromMask($mask)
            )
        );
    }
}
