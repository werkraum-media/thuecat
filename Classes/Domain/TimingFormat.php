<?php

declare(strict_types=1);

/*
 * Copyright (C) 2023 Daniel Siepmann <coding@daniel-siepmann.de>
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

namespace WerkraumMedia\ThueCat\Domain;

class TimingFormat
{
    /**
     * Returns timing in default format.
     *
     * @return string
     */
    public static function format(string $timing): string
    {
        $parts = self::getTimingParts($timing);

        if ($parts['hour'] === '' || $parts['minutes'] === '') {
            return '';
        }

        return $parts['hour'] . ':' . $parts['minutes'];
    }

    /**
     * Converts the string representationg of a time HH:MM:SS into an array.
     *
     * @return string[]
     */
    private static function getTimingParts(string $string): array
    {
        $parts = explode(':', $string);
        return [
            'hour' => $parts[0] ?? '',
            'minutes' => $parts[1] ?? '',
            'seconds' => $parts[2] ?? '',
        ];
    }
}
