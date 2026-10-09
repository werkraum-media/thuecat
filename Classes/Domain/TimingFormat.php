<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS extension "thuecat".
 *
 * Copyright (C) werkraum-media <https://werkraum-media.de/>
 * Copyright (C) 2023 Daniel Siepmann <coding@daniel-siepmann.de>
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
    protected static function getTimingParts(string $string): array
    {
        $parts = explode(':', $string);
        return [
            'hour' => $parts[0],
            'minutes' => $parts[1] ?? '',
            'seconds' => $parts[2] ?? '',
        ];
    }
}
