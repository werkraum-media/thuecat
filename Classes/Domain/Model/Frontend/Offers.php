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

namespace WerkraumMedia\ThueCat\Domain\Model\Frontend;

use TYPO3\CMS\Core\Type\TypeInterface;

/**
 * @implements \Iterator<int, Offer>
 */
class Offers implements TypeInterface, \Iterator, \Countable
{
    /**
     * @var string
     */
    private $serialized = '';

    /**
     * @var mixed[]
     */
    private $array = [];

    /**
     * @var int
     */
    private $position = 0;

    public function __construct(string $serialized)
    {
        $this->serialized = $serialized;
        $this->array = array_map(
            [Offer::class, 'createFromArray'],
            json_decode($serialized, true)
        );
    }

    public function __toString(): string
    {
        return $this->serialized;
    }

    public function current(): Offer
    {
        return $this->array[$this->position];
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function key(): int
    {
        return $this->position;
    }

    public function valid(): bool
    {
        return isset($this->array[$this->position]);
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function count()
    {
        return count($this->array);
    }
}
