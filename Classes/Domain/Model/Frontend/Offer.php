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

class Offer
{
    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $description;

    /**
     * @var mixed[]
     */
    private $prices;

    private function __construct(
        string $title,
        string $description,
        array $prices
    ) {
        $this->title = $title;
        $this->description = $description;
        $this->prices = $prices;
    }

    /**
     * @return Offer
     */
    public static function createFromArray(array $rawData)
    {
        $prices = [];
        foreach ($rawData['prices'] as $price) {
            $prices[] = Price::createFromArray($price);
        }

        return new self(
            $rawData['title'],
            $rawData['description'],
            $prices
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

    public function getPrices(): array
    {
        return $this->prices;
    }
}
