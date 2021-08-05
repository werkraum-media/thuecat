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

namespace WerkraumMedia\ThueCat\Domain\Import\Entity\Properties;

class Offer
{
    /**
     * @var string
     */
    protected $name = '';

    /**
     * @var string
     */
    protected $description = '';

    /**
     * @var PriceSpecification[]
     */
    protected $prices = [];

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return PriceSpecification[]
     */
    public function getPrices(): array
    {
        return $this->prices;
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return PriceSpecification[]
     * @internal for mapping via Symfony component.
     */
    public function getPriceSpecification(): array
    {
        return $this->prices;
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function addPriceSpecification(PriceSpecification $priceSpecification): void
    {
        $this->prices[] = $priceSpecification;
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function removePriceSpecification(PriceSpecification $priceSpecification): void
    {
    }
}
