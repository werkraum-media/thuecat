<?php

declare(strict_types=1);

/*
 * Copyright (C) 2026 werkraum-media
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 */

namespace WerkraumMedia\ThueCat\Domain\Model\Frontend\Dto;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * What one filter field offers. The name is the demand property and the view
 * key.
 *
 * @implements IteratorAggregate<int, FilterOption>
 */
final class FilterOptions implements IteratorAggregate, Countable
{
    /**
     * @param FilterOption[] $options
     */
    public function __construct(
        private readonly string $name,
        private readonly array $options,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return FilterOption[]
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->options);
    }

    public function count(): int
    {
        return count($this->options);
    }
}
