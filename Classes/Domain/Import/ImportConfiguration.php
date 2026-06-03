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

    /**
     * Per-configuration ThueCat API key. Takes priority over the global
     * key from ExtensionConfiguration. Empty string means "not set — fall
     * back to the global key".
     */
    public function getApiKey(): string;

    /**
     * PID imported records will be written to. Also used to resolve the target
     * site, which determines the default language tag (e.g. "de") used when
     * picking localised values from the JSON-LD payload.
     */
    public function getStoragePid(): int;

    /**
     * Combined FAL folder identifier (e.g. "1:/thuecat/") imported media
     * files are written to.
     */
    public function getFileFolder(): string;

    /**
     * Host the importer fetches resources from for this configuration.
     * Implementations must never return an empty string — when no override is
     * configured they fall back to FetchData::DEFAULT_API_DOMAIN so callers
     * can rely on always having a usable URL prefix.
     */
    public function getApiDomain(): string;

    /**
     * Which extension's data structures the import populates. Currently
     * supplied by the syncScope flexform; other configuration types return
     * 'thuecat' as the historical default. Returned values mirror the
     * extension keys ('thuecat', 'events') so callers can route on them
     * directly without a mapping layer.
     */
    public function getImportTarget(): string;

    public function getUid(): ?int;

    public function getFetchLastXDays(): int;
}
