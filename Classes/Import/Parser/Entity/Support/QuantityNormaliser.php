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
 * Converts a schema:QuantitativeValue into metres or square metres. A unit it does not know yields no value;
 * the caller reports it, as the normaliser has no import context.
 */
class QuantityNormaliser
{
    protected const FACTORS = [
        'thuecat:MTR' => 1,
        'thuecat:KTM' => 1000,
        'thuecat:MTK' => 1,
        'thuecat:HAR' => 10000,
    ];

    /**
     * @param array<string, mixed> $quantity
     */
    public function normalise(array $quantity): ?float
    {
        $factor = self::FACTORS[$this->unitOf($quantity)] ?? null;
        $value = new LocalisedValueReader()->read($quantity['schema:value'] ?? null, '');
        if ($factor === null || !is_numeric($value)) {
            return null;
        }

        // Decimal input times the factor drifts in binary (3.2 km is 3200.0000000000005 m).
        return round((float)$value * $factor, 6);
    }

    /**
     * @param array<string, mixed> $quantity
     */
    public function unitOf(array $quantity): string
    {
        return new LocalisedValueReader()->read($quantity['schema:unitCode'] ?? null, '');
    }
}
