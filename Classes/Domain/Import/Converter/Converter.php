<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Domain\Import\Converter;

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

use WerkraumMedia\ThueCat\Domain\Import\Model\EntityCollection;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfiguration;

interface Converter
{
    /**
     * A single type is an array of different types.
     * All types together identify a specific entity and possible converter.
     */
    public function canConvert(array $type): bool;

    /**
     * A single JSONLD entity can have multiple languages.
     * That may result in multiple entities in TYPO3.
     */
    public function convert(array $jsonLD, ImportConfiguration $configuration): EntityCollection;
}
