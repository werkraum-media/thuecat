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

class Media implements TypeInterface
{
    private string $serialized;
    private array $data;

    public function __construct(string $serialized)
    {
        $this->serialized = $serialized;
        $this->data = json_decode($serialized, true);
    }

    public function getMainImage(): array
    {
        foreach ($this->data as $media) {
            if (
                $media['type'] === 'image'
                && $media['mainImage'] === true
            ) {
                return $media;
            }
        }

        return [];
    }

    public function getImages(): array
    {
        return array_filter($this->data, function (array $media) {
            return $media['type'] === 'image';
        });
    }

    public function __toString(): string
    {
        return $this->serialized;
    }
}
