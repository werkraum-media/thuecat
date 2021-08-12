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

namespace WerkraumMedia\ThueCat\Domain\Import\Importer;

use WerkraumMedia\ThueCat\Domain\Import\Entity\MapsToType;
use WerkraumMedia\ThueCat\Domain\Import\Model\Entity;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfiguration;

interface Converter
{
    /**
     * Receives the incoming JSON-LD mapped to objects.
     * Should convert that one for the actual system, e.g. TYPO3.
     *
     * @param string $language The current language, e.g. 'de', 'en', 'fr'
     */
    public function convert(
        MapsToType $mapped,
        ImportConfiguration $configuration,
        string $language
    ): ?Entity;
}
