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

class Address
{
    /**
     * @var string
     */
    protected $streetAddress = '';

    /**
     * @var string
     */
    protected $addressLocality = '';

    /**
     * @var string
     */
    protected $postalCode = '';

    /**
     * @var string
     */
    protected $telephone = '';

    /**
     * @var string
     */
    protected $faxNumber = '';

    /**
     * @var string
     */
    protected $email = '';

    public function getStreetAddress(): string
    {
        return $this->streetAddress;
    }

    public function getAddressLocality(): string
    {
        return $this->addressLocality;
    }

    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    public function getTelephone(): string
    {
        return $this->telephone;
    }

    public function getFaxNumber(): string
    {
        return $this->faxNumber;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function setStreetAddress(string $streetAddress): void
    {
        $this->streetAddress = $streetAddress;
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function setAddressLocality(string $addressLocality): void
    {
        $this->addressLocality = $addressLocality;
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function setPostalCode(string $postalCode): void
    {
        $this->postalCode = $postalCode;
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function setTelephone(string $telephone): void
    {
        $this->telephone = $telephone;
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function setFaxNumber(string $faxNumber): void
    {
        $this->faxNumber = $faxNumber;
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }
}
