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

namespace WerkraumMedia\ThueCat\Domain\Import\Entity\Shared;

use WerkraumMedia\ThueCat\Domain\Import\Entity\Properties\ForeignReference;

trait ContainedInPlace
{
    /**
     * @var ForeignReference[]
     */
    protected $containedInPlace = [];

    /**
     * @return ForeignReference[]
     */
    public function getContainedInPlaces(): array
    {
        return $this->containedInPlace;
    }

    /**
     * @return ForeignReference[]
     *
     * @internal for mapping via Symfony component.
     */
    public function getContainedInPlace(): array
    {
        return $this->containedInPlace;
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function addContainedInPlace(ForeignReference $place): void
    {
        $this->containedInPlace[] = $place;
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function removeContainedInPlace(ForeignReference $place): void
    {
    }
}
