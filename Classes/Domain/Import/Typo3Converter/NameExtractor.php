<?php

declare(strict_types=1);

/*
 * Copyright (C) 2022 Daniel Siepmann <coding@daniel-siepmann.de>
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

namespace WerkraumMedia\ThueCat\Domain\Import\Typo3Converter;

use WerkraumMedia\ThueCat\Domain\Import\Entity\Properties\ForeignReference;
use WerkraumMedia\ThueCat\Domain\Import\ResolveForeignReference;

class NameExtractor
{
    /**
     * @var ResolveForeignReference
     */
    private $resolveForeignReference;

    public function __construct(
        ResolveForeignReference $resolveForeignReference
    ) {
        $this->resolveForeignReference = $resolveForeignReference;
    }

    /**
     * @param string|ForeignReference $foreignReference
     */
    public function extract(
        $foreignReference,
        string $language
    ): string {
        if (is_string($foreignReference)) {
            return $foreignReference;
        }

        if ($foreignReference instanceof ForeignReference) {
            $remote = $this->resolveForeignReference->resolve($foreignReference, $language);
            if (is_object($remote) && method_exists($remote, 'getName')) {
                return $remote->getName();
            }
        }

        return '';
    }
}
