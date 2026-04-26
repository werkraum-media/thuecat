<?php

declare(strict_types=1);

/*
 * Copyright (C) 2024 werkraum-media
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

final class ResolverContext
{
    /**
     * remote_id → current outer key for the in-flight import.
     *
     * Lifetime spans every Resolver round for a single
     * Importer::importConfiguration call. Values evolve:
     *   - first sighting of a new remote_id: NEW… placeholder
     *   - after DataHandler runs: promoted to the assigned uid (string)
     *   - pre-existing rows: the uid string from the first sighting
     * Kept on the context (not on the Resolver, which is a DI singleton)
     * so concurrent imports cannot cross-contaminate each other.
     *
     * @var array<string, string>
     */
    public array $remoteIdToKey = [];

    public function __construct(
        public readonly int $storagePid,
        public readonly string $language = 'de',
        public readonly ?string $apiKey = null,
    ) {
    }

    /**
     * Rewrite NEW… placeholders in $remoteIdToKey to the uids assigned by
     * DataHandler in the previous round. After the call the map only holds
     * uid strings for any remote_id whose row has hit the DB.
     *
     * @param array<string, int|string> $substNEWwithIDs
     */
    public function promoteNewKeys(array $substNEWwithIDs): void
    {
        foreach ($this->remoteIdToKey as $remoteId => $key) {
            if (isset($substNEWwithIDs[$key])) {
                $this->remoteIdToKey[$remoteId] = (string)$substNEWwithIDs[$key];
            }
        }
    }
}
