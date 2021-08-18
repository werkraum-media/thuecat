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

namespace WerkraumMedia\ThueCat\Domain\Import;

interface ImportConfiguration
{
    /**
     * Defines type of configuration, e.g.:
     * - static
     * - syncScope
     *
     * A UrlProvider is necessary for the type in order to process the configuration.
     */
    public function getType(): string;

    /**
     * Return URLs to import, full path to resources.
     *
     * @return string[]
     */
    public function getUrls(): array;

    /**
     * Defines a limited set of types to process, e.g.:
     * - thuecat:Town
     *
     * The import will only process resources of this type.
     * Empty array will allow all.
     *
     * @return string[]
     */
    public function getAllowedTypes(): array;
}
