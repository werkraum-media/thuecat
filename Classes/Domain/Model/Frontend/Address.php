<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Domain\Model\Frontend;

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

use TYPO3\CMS\Core\Type\TypeInterface;

class Address implements TypeInterface
{
    private string $serialized;
    private array $data;

    public function __construct(string $serialized)
    {
        $this->serialized = $serialized;
        $this->data = json_decode($serialized, true);
    }

    public function getStreet(): string
    {
        return $this->data['street'] ?? '';
    }

    public function getZip(): string
    {
        return $this->data['zip'] ?? '';
    }

    public function getCity(): string
    {
        return $this->data['city'] ?? '';
    }

    public function getEmail(): string
    {
        return $this->data['email'] ?? '';
    }

    public function getPhone(): string
    {
        return $this->data['phone'] ?? '';
    }

    public function getFax(): string
    {
        return $this->data['fax'] ?? '';
    }

    public function getLatitute(): float
    {
        return $this->data['geo']['latitude'] ?? 0.0;
    }

    public function getLongitude(): float
    {
        return $this->data['geo']['longitude'] ?? 0.0;
    }

    public function __toString(): string
    {
        return $this->serialized;
    }
}
