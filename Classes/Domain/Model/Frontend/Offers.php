<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS extension "thuecat".
 *
 * Copyright (C) werkraum-media <https://werkraum-media.de/>
 * Copyright (C) 2021 Daniel Siepmann <coding@daniel-siepmann.de>
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * For the full license text, see the LICENSE file distributed with this
 * extension.
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 */

namespace WerkraumMedia\ThueCat\Domain\Model\Frontend;

use Countable;
use Iterator;
use TYPO3\CMS\Core\Type\TypeInterface;

/**
 * @implements Iterator<int, Offer>
 */
class Offers implements TypeInterface, Iterator, Countable
{
    /**
     * @var mixed[]
     */
    protected array $array = [];

    protected int $position = 0;

    public function __construct(
        protected readonly string $serialized
    ) {
        $array = json_decode($serialized, true);
        if (is_array($array)) {
            $array = array_map([Offer::class, 'createFromArray'], $array);
            usort($array, function (Offer $offerA, Offer $offerB) {
                return $offerA->getType() <=> $offerB->getType();
            });
            $this->array = $array;
        }
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

    public function count(): int
    {
        return count($this->array);
    }
}
