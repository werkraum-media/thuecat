<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS extension "thuecat".
 *
 * Copyright (C) werkraum-media <https://werkraum-media.de/>
 * Copyright (C) 2021 Daniel Siepmann <coding@daniel-siepmann.de>
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

namespace WerkraumMedia\ThueCat\Domain\Model\Frontend;

use TYPO3\CMS\Core\Utility\GeneralUtility;

class Price
{
    protected function __construct(
        protected readonly string $title,
        protected readonly string $description,
        protected readonly float $price,
        protected readonly string $currency,
        protected readonly array $rules
    ) {
    }

    /**
     * @return Price
     */
    public static function createFromArray(array $rawData)
    {
        return new self(
            $rawData['title'],
            $rawData['description'],
            $rawData['price'],
            $rawData['currency'],
            GeneralUtility::trimExplode(',', $rawData['rule'], true)
        );
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getRules(): array
    {
        return $this->rules;
    }
}
