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

namespace WerkraumMedia\ThueCat\Service\FilterField\OptionProvider;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Dto\FilterOptions;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Dto\FilterScope;
use WerkraumMedia\ThueCat\Service\FilterField\FilterFieldDefinition;

/**
 * Reads what one storage shape offers, given a field of that shape and the
 * scope to read it within.
 *
 * Implementations are shared and must stay stateless: everything a call needs
 * arrives as an argument, and nothing it derives may be kept. A QueryBuilder is
 * stateful and is therefore built per call, never held.
 *
 * @template T of FilterFieldDefinition
 */
#[Autoconfigure(public: true)]
#[AutoconfigureTag('search.filter.option.provider')]
interface FilterOptionProvider
{
    /**
     * Whether this provider reads the shape the given field declares. Asked
     * per field, so a new storage shape ships its own provider rather than
     * being added to a list somewhere.
     *
     * @phpstan-assert-if-true T $field
     */
    public function supports(FilterFieldDefinition $field): bool;

    /**
     * @param T $field
     */
    public function provide(FilterFieldDefinition $field, FilterScope $scope): FilterOptions;
}
