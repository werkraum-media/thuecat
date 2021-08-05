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

namespace WerkraumMedia\ThueCat\Domain\Import\EntityMapper;

use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer as SymfonyArrayDenormalizer;

class ArrayDenormalizer extends SymfonyArrayDenormalizer
{
    public function denormalize(
        $data,
        string $type,
        string $format = null,
        array $context = []
    ): array {
        return parent::denormalize(
            $this->transformSingleEntryToMultiEntry($data),
            $type,
            $format,
            $context
        );
    }

    /**
     * We sometimes expect an array of object but only get a single object with different structure.
     *
     * This method detects this and transforms it into an array with this single object to stay compatible.
     *
     * E.g. schema:image might be an array or single image.
     * Our objects always expects an array and serializer would break.
     */
    private function transformSingleEntryToMultiEntry(array $data): array
    {
        // If we got strings, we know this is a single object which needs transformation.
        if (self::hasOnlyNumericKeys($data) === false) {
            $data = [$data];
        }

        return $data;
    }

    public static function hasOnlyNumericKeys(array $data): bool
    {
        // Each object is identified by an numeric index
        // Strings are only used as index for object properties.
        $differences = array_diff(
            array_keys($data),
            array_filter(array_keys($data), 'is_int')
        );

        return $differences === [];
    }
}
