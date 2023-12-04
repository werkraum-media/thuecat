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

use WerkraumMedia\ThueCat\Domain\Import\Entity\Properties\ForeignReference;
use WerkraumMedia\ThueCat\Domain\Import\Entity\Shared\ManagedBy;

class Base extends Minimum
{
    use ManagedBy;

    protected ForeignReference $photo;

    /**
     * Images of this Thing.
     *
     * @var ForeignReference[]
     */
    protected array $images = [];

    public function getPhoto(): ?ForeignReference
    {
        return $this->photo;
    }

    /**
     * @return ForeignReference[]
     */
    public function getImages(): array
    {
        return $this->images;
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function setPhoto(ForeignReference $photo): void
    {
        $this->photo = $photo;
    }

    /**
     * @internal for mapping via Symfony component.
     *
     * @return ForeignReference[]
     */
    public function getImage(): array
    {
        return $this->images;
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function addImage(ForeignReference $image): void
    {
        $this->images[] = $image;
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function removeImage(ForeignReference $image): void
    {
    }
}
