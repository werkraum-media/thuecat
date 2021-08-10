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

namespace WerkraumMedia\ThueCat\Domain\Import\Converter;

/**
 * Central registry of all available converters.
 */
class Registry
{
    /**
     * @var Converter[]
     */
    private $converters = [];

    public function registerConverter(Converter $converter): void
    {
        $this->converters[] = $converter;
    }

    public function getConverterBasedOnType(array $type): ?Converter
    {
        foreach ($this->converters as $converter) {
            if ($converter->canConvert($type)) {
                return $converter;
            }
        }

        return null;
    }
}
