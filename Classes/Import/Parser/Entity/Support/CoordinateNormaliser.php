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

namespace WerkraumMedia\ThueCat\Import\Parser\Entity\Support;

/**
 * Writes a delivered coordinate as a plain decimal of at most seven decimal
 * places, about one centimetre. Upstream sends E notation and up to 30
 * decimals; neither carries more position than seven do.
 */
class CoordinateNormaliser
{
    public function normalise(string $value): string
    {
        if (!is_numeric($value)) {
            return '';
        }

        // |value| <= 180 at seven decimals needs ten significant digits; a double holds them.
        $formatted = sprintf('%.7F', round((float)$value, 7));

        return rtrim(rtrim($formatted, '0'), '.');
    }
}
