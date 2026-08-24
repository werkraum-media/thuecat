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

namespace WerkraumMedia\ThueCat\Import\Parser\Entity\TransientEntity;

use WerkraumMedia\ThueCat\Import\Parser\Entity\Support\LocalisedValueReader;

// Shared base for nested JSON-LD shapes whose rendered form is a JSON blob on
// a parent entity's column (Address, OpeningHours, …). Transients are not
// registered as `import.entity` services and not dispatched by the Parser —
// the parent owns construction, configuration, and json_encoding.
//
// Kept deliberately separate from Entity\AbstractEntity: top-level entities
// carry transients, priorities, handlesTypes(), and the DataHandler payload
// contract; transients have none of that. Only the shared value-extraction
// helpers live here.
abstract class AbstractTransientEntity
{
    abstract public function toArray(): array;

    /**
     * The one text extraction in the import layer; see LocalisedValueReader.
     * Mirrors AbstractEntity::extractValue — the two roots share no base, so
     * each exposes the collaborator to its own subclasses.
     */
    protected function extractValue(mixed $value, string $language): string
    {
        return (new LocalisedValueReader())->read($value, $language);
    }

    /**
     * Drop the `thuecat:` / `schema:` prefix from an enum URI so the stored
     * value matches the bare member name used by the frontend models.
     */
    protected function stripNamespacePrefix(string $value): string
    {
        $colon = strpos($value, ':');
        return $colon === false ? $value : substr($value, $colon + 1);
    }
}
