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

use WerkraumMedia\ThueCat\Domain\Import\EntityMapper\PropertyValues;
use WerkraumMedia\ThueCat\Domain\Import\Entity\Minimum;

class Offer extends Minimum
{
    /**
     * @var string[]
     */
    protected $offerTypes = [];

    /**
     * @var PriceSpecification[]
     */
    protected $prices = [];

    /**
     * @return string[]
     */
    public function getOfferTypes(): array
    {
        return $this->offerTypes;
    }

    /**
     * @internal for mapping via Symfony component.
     * @param string|array $offerType
     */
    public function setOfferType($offerType): void
    {
        if (is_string($offerType)) {
            $offerType = [$offerType];
        }

        $this->offerTypes = array_map(
            [PropertyValues::class, 'removePrefixFromEntry'],
            $offerType
        );
    }

    /**
     * @return PriceSpecification[]
     */
    public function getPrices(): array
    {
        return $this->prices;
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
