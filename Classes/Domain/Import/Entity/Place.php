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

namespace WerkraumMedia\ThueCat\Domain\Import\Entity;

use WerkraumMedia\ThueCat\Domain\Import\Entity\Properties\Address;
use WerkraumMedia\ThueCat\Domain\Import\Entity\Properties\Geo;
use WerkraumMedia\ThueCat\Domain\Import\Entity\Properties\OpeningHour;
use WerkraumMedia\ThueCat\Domain\Import\Entity\Shared\ContainedInPlace;
use WerkraumMedia\ThueCat\Domain\Import\Entity\Shared\Organization;

class Place extends Base
{
    use Organization;
    use ContainedInPlace;

    /**
     * @var Address
     */
    protected $address;

    /**
     * @var Geo
     */
    protected $geo;

    /**
     * @var OpeningHour[]
     */
    protected $openingHours = [];

    public function getAddress(): ?Address
    {
        return $this->address;
    }

    public function getGeo(): ?Geo
    {
        return $this->geo;
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function setAddress(Address $address): void
    {
        $this->address = $address;
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function setGeo(Geo $geo): void
    {
        $this->geo = $geo;
    }

    /**
     * @return OpeningHour[]
     * @internal for mapping via Symfony component.
     */
    public function getOpeningHoursSpecification(): array
    {
        return $this->openingHours;
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function addOpeningHoursSpecification(OpeningHour $openingHour): void
    {
        $this->openingHours[] = $openingHour;
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function removeOpeningHoursSpecification(OpeningHour $openingHour): void
    {
    }
}
