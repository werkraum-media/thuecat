<?php

declare(strict_types=1);

/*
 * Copyright (C) 2021 Daniel Siepmann <coding@daniel-siepmann.de>
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

namespace WerkraumMedia\ThueCat\Domain\Model\Frontend;

use TYPO3\CMS\Core\Utility\ArrayUtility;

class Offer
{
    /**
     * @param string[] $types
     * @param mixed[] $prices
     */
    private function __construct(
        private readonly string $title,
        private array $types,
        private readonly string $description,
        private readonly array $prices
    ) {
    }

    public static function createFromArray(array $rawData): Offer
    {
        $prices = [];

        foreach (ArrayUtility::sortArraysByKey($rawData['prices'], 'title') as $price) {
            $prices[] = Price::createFromArray($price);
        }

        $types = $rawData['types'] ?? $rawData['type'] ?? [];
        // Handle old legacy saved values which were a single string saves as 'type' instead of 'types'.
        if (is_string($types)) {
            $types = [$types];
        }

        return new self(
            $rawData['title'],
            $types,
            $rawData['description'],
            $prices
        );
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getType(): string
    {
        $offerTypes = array_filter($this->types, function (string $type) {
            return str_contains($type, 'Offer');
        });
        // Ensure clean index
        $offerTypes = array_values($offerTypes);

        if ($offerTypes !== []) {
            return $offerTypes[0];
        }

        return $this->types[0] ?? '';
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getPrices(): array
    {
        return $this->prices;
    }
}
