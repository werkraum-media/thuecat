<?php

namespace WerkraumMedia\ThueCat\Domain\Import\JsonLD\Parser;

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

class Address
{
    public function get(array $jsonLD): array
    {
        $address = $jsonLD['schema:address'] ?? [];
        $geo = $jsonLD['schema:geo'] ?? [];

        return [
            'street' => $this->getStreet($address),
            'zip' => $this->getZip($address),
            'city' => $this->getCity($address),
            'email' => $this->getEmail($address),
            'phone' => $this->getPhone($address),
            'fax' => $this->getFax($address),
            'geo' => $this->getGeo($geo),
        ];
    }

    private function getStreet(array $address): string
    {
        return $address['schema:streetAddress']['@value'] ?? '';
    }

    private function getZip(array $address): string
    {
        return $address['schema:postalCode']['@value'] ?? '';
    }

    private function getCity(array $address): string
    {
        return $address['schema:addressLocality']['@value'] ?? '';
    }

    private function getEmail(array $address): string
    {
        return $address['schema:email']['@value'] ?? '';
    }

    private function getPhone(array $address): string
    {
        return $address['schema:telephone']['@value'] ?? '';
    }

    private function getFax(array $address): string
    {
        return $address['schema:faxNumber']['@value'] ?? '';
    }

    private function getGeo(array $geo): array
    {
        return [
            'latitude' => floatval($geo['schema:latitude']['@value'] ?? 0.0),
            'longitude' => floatval($geo['schema:longitude']['@value'] ?? 0.0),
        ];
    }
}
