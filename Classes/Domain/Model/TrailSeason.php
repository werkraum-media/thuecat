<?php

declare(strict_types=1);

/*
 * Copyright (C) 2026 werkraum-media
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

namespace WerkraumMedia\ThueCat\Domain\Model;

/**
 * The members a trail's season bitmask is built from, shared by the import that
 * writes the mask and the frontend that reads it.
 *
 * Case order is the bit order and both are a stored contract: the position of a
 * case decides its bit, and the TCA checkbox items are rendered in the same
 * sequence. Append new members, never reorder or remove.
 */
enum TrailSeason: string
{
    case Jan = 'Jan';
    case Feb = 'Feb';
    case Mar = 'Mar';
    case Apr = 'Apr';
    case May = 'May';
    case Jun = 'Jun';
    case Jul = 'Jul';
    case Aug = 'Aug';
    case Sep = 'Sep';
    case Oct = 'Oct';
    case Nov = 'Nov';
    case Dec = 'Dec';
    case AllYearRound = 'AllYearRound';

    public function bit(): int
    {
        return 1 << array_search($this, self::cases(), true);
    }

    public function isSetIn(int $mask): bool
    {
        return ($mask & $this->bit()) !== 0;
    }

    /** Suffix under `tx_thuecat_trail.season.` in locallang_tca.xlf. */
    public function labelKey(): string
    {
        return $this->value;
    }

    /**
     * The members a mask carries, in bit order.
     *
     * @return list<self>
     */
    public static function fromMask(int $mask): array
    {
        return array_values(array_filter(
            self::cases(),
            static fn (self $case): bool => $case->isSetIn($mask)
        ));
    }

    /**
     * Bit per member, keyed by name.
     *
     * @return array<string, int>
     */
    public static function bits(): array
    {
        $bits = [];
        foreach (self::cases() as $case) {
            $bits[$case->value] = $case->bit();
        }

        return $bits;
    }
}
