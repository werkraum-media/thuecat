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

use WerkraumMedia\ThueCat\Domain\Import\Entity\Minimum;
use WerkraumMedia\ThueCat\Domain\Import\EntityMapper\PropertyValues;

class PriceSpecification extends Minimum
{
    /**
     * @var float
     */
    protected $price = 0.00;

    /**
     * E.g. 'EUR'
     * ThueCat specific format.
     *
     * @var string
     */
    protected $currency = '';

    /**
     * E.g. 'PerPerson'
     * ThueCat specific property.
     *
     * @var array
     */
    protected $calculationRules = [];

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * @return string[]
     */
    public function getCalculationRules(): array
    {
        return $this->calculationRules;
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function setPrice(string $price): void
    {
        $this->price = (float)$price;
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function setPriceCurrency(string $currency): void
    {
        $this->currency = PropertyValues::removePrefixFromEntry($currency);
    }

    /**
     * @internal for mapping via Symfony component.
     *
     * @param string|array $calculationRule
     */
    public function setCalculationRule($calculationRule): void
    {
        if (is_string($calculationRule)) {
            $calculationRule = [$calculationRule];
        }
        $this->calculationRules = PropertyValues::removePrefixFromEntries($calculationRule);
    }
}
